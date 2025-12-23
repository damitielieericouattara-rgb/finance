<?php
// ========================================
// SERVICE DE NOTIFICATIONS
// SMS, WhatsApp, Navigateur, Email
// ========================================

/**
 * Envoyer une notification SMS via Twilio
 */
function sendSMS($phone, $message) {
    try {
        // Vérifier que le numéro est valide
        if (empty($phone)) {
            return false;
        }
        
        // Configuration Twilio
        $accountSid = TWILIO_ACCOUNT_SID;
        $authToken = TWILIO_AUTH_TOKEN;
        $twilioNumber = TWILIO_PHONE_NUMBER;
        
        // URL de l'API Twilio
        $url = "https://api.twilio.com/2010-04-01/Accounts/$accountSid/Messages.json";
        
        // Données à envoyer
        $data = [
            'From' => $twilioNumber,
            'To' => $phone,
            'Body' => $message
        ];
        
        // Initialiser cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "$accountSid:$authToken");
        
        // Exécuter la requête
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Vérifier le résultat
        if ($httpCode === 200 || $httpCode === 201) {
            logActivity(null, 'SMS_SENT', null, null, "SMS envoyé à $phone");
            return true;
        } else {
            error_log("Erreur SMS Twilio: " . $result);
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Erreur sendSMS: " . $e->getMessage());
        return false;
    }
}

/**
 * Envoyer un message WhatsApp
 */
function sendWhatsApp($whatsapp, $message) {
    try {
        // Vérifier que le numéro est valide
        if (empty($whatsapp)) {
            return false;
        }
        
        // Option 1 : Via Twilio WhatsApp API
        $accountSid = TWILIO_ACCOUNT_SID;
        $authToken = TWILIO_AUTH_TOKEN;
        $twilioWhatsApp = 'whatsapp:' . TWILIO_PHONE_NUMBER;
        
        $url = "https://api.twilio.com/2010-04-01/Accounts/$accountSid/Messages.json";
        
        $data = [
            'From' => $twilioWhatsApp,
            'To' => 'whatsapp:' . $whatsapp,
            'Body' => $message
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "$accountSid:$authToken");
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 || $httpCode === 201) {
            logActivity(null, 'WHATSAPP_SENT', null, null, "WhatsApp envoyé à $whatsapp");
            return true;
        } else {
            error_log("Erreur WhatsApp: " . $result);
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Erreur sendWhatsApp: " . $e->getMessage());
        return false;
    }
}

/**
 * Envoyer une notification navigateur (Web Push)
 */
function sendBrowserNotification($userId, $title, $body, $data = []) {
    try {
        // Créer une notification dans la base
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, title, message, type, transaction_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $title,
            $body,
            $data['type'] ?? 'info',
            $data['transaction_id'] ?? null
        ]);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Erreur sendBrowserNotification: " . $e->getMessage());
        return false;
    }
}

/**
 * Notifier un utilisateur (tous les canaux)
 */
function notifyUser($userId, $title, $message, $type = 'info', $transactionId = null, $sendExternal = true) {
    try {
        $db = getDB();
        
        // Récupérer les infos utilisateur
        $stmt = $db->prepare("SELECT phone, whatsapp, email, full_name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        // 1. Notification web (base de données)
        sendBrowserNotification($userId, $title, $message, [
            'type' => $type,
            'transaction_id' => $transactionId
        ]);
        
        // 2. Email
        $emailBody = getNotificationEmailTemplate($user['full_name'], $title, $message, $type);
        sendEmail($user['email'], $title . " - " . SITE_NAME, $emailBody, true);
        
        // 3. SMS et WhatsApp (seulement si demandé)
        if ($sendExternal) {
            $shortMessage = substr($message, 0, 160); // Limiter pour SMS
            
            if (!empty($user['phone'])) {
                sendSMS($user['phone'], $shortMessage);
            }
            
            if (!empty($user['whatsapp'])) {
                sendWhatsApp($user['whatsapp'], $message);
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Erreur notifyUser: " . $e->getMessage());
        return false;
    }
}

/**
 * Notifier tous les administrateurs
 */
function notifyAllAdmins($title, $message, $type = 'info', $transactionId = null) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("SELECT id FROM users WHERE role_id = 1 AND is_active = 1");
        $stmt->execute();
        $admins = $stmt->fetchAll();
        
        foreach ($admins as $admin) {
            notifyUser($admin['id'], $title, $message, $type, $transactionId, true);
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Erreur notifyAllAdmins: " . $e->getMessage());
        return false;
    }
}

/**
 * Template email pour notifications
 */
function getNotificationEmailTemplate($userName, $title, $message, $type) {
    $colors = [
        'info' => '#3B82F6',
        'success' => '#059669',
        'warning' => '#F59E0B',
        'urgent' => '#DC2626'
    ];
    
    $icons = [
        'info' => 'ℹ️',
        'success' => '✅',
        'warning' => '⚠️',
        'urgent' => '🔴'
    ];
    
    $color = $colors[$type] ?? $colors['info'];
    $icon = $icons[$type] ?? $icons['info'];
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f5f5f5;
                margin: 0;
                padding: 0;
            }
            .container {
                max-width: 600px;
                margin: 40px auto;
                background-color: white;
                border-radius: 12px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                overflow: hidden;
            }
            .header {
                background-color: {$color};
                padding: 30px;
                text-align: center;
            }
            .header h1 {
                color: white;
                margin: 0;
                font-size: 24px;
            }
            .content {
                padding: 40px 30px;
            }
            .message-box {
                background-color: #f9fafb;
                border-left: 4px solid {$color};
                padding: 20px;
                margin: 20px 0;
                border-radius: 4px;
            }
            .button {
                display: inline-block;
                background-color: {$color};
                color: white;
                padding: 12px 30px;
                text-decoration: none;
                border-radius: 6px;
                margin-top: 20px;
            }
            .footer {
                background-color: #f9fafb;
                padding: 20px;
                text-align: center;
                color: #6b7280;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>{$icon} {$title}</h1>
            </div>
            
            <div class='content'>
                <p>Bonjour <strong>{$userName}</strong>,</p>
                
                <div class='message-box'>
                    <p style='margin: 0; color: #374151;'>{$message}</p>
                </div>
                
                <p style='margin-top: 30px;'>
                    <a href='" . SITE_URL . "' class='button'>Accéder à l'application</a>
                </p>
            </div>
            
            <div class='footer'>
                <p style='margin: 0;'>
                    © " . date('Y') . " " . SITE_NAME . ". Tous droits réservés.
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
}

/**
 * Envoyer une notification urgente répétée
 */
function sendUrgentNotification($transactionId) {
    try {
        $db = getDB();
        
        // Récupérer la transaction
        $stmt = $db->prepare("
            SELECT t.*, u.full_name, u.email
            FROM transactions t
            LEFT JOIN users u ON t.user_id = u.id
            WHERE t.id = ?
        ");
        $stmt->execute([$transactionId]);
        $transaction = $stmt->fetch();
        
        if (!$transaction) {
            return false;
        }
        
        // Calculer le temps d'attente
        $createdTime = new DateTime($transaction['created_at']);
        $now = new DateTime();
        $interval = $createdTime->diff($now);
        
        $waitingTime = '';
        if ($interval->d > 0) {
            $waitingTime = $interval->d . ' jour(s) ';
        }
        if ($interval->h > 0) {
            $waitingTime .= $interval->h . ' heure(s) ';
        }
        $waitingTime .= $interval->i . ' minute(s)';
        
        $title = "🔴 URGENT - Demande en attente depuis $waitingTime";
        $message = "Demande urgente de {$transaction['full_name']} : " . formatAmount($transaction['amount']) . " - {$transaction['description']}";
        
        // Notifier tous les admins
        notifyAllAdmins($title, $message, 'urgent', $transactionId);
        
        // Mettre à jour le compteur
        $updateStmt = $db->prepare("
            UPDATE transactions 
            SET last_urgent_notification = NOW(), 
                urgent_notification_count = urgent_notification_count + 1 
            WHERE id = ?
        ");
        $updateStmt->execute([$transactionId]);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Erreur sendUrgentNotification: " . $e->getMessage());
        return false;
    }
}