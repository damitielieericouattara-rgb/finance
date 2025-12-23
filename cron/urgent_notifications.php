<?php
/**
 * SCRIPT CRON POUR LES NOTIFICATIONS URGENTES
 * À exécuter toutes les 10 minutes via cron
 * Crontab: */10 * * * * php /path/to/cron/urgent_notifications.php 
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/notifications_service.php';

echo "================================================\n";
echo "SCRIPT DE NOTIFICATIONS URGENTES\n";
echo "Démarré le : " . date('Y-m-d H:i:s') . "\n";
echo "================================================\n\n";

try {
    $db = getDB();
    
    // Récupérer toutes les transactions urgentes en attente
    $stmt = $db->query("
        SELECT t.id, t.user_id, t.amount, t.description, t.required_date, t.created_at,
               t.last_urgent_notification, t.urgent_notification_count,
               u.full_name as user_name, u.email as user_email
        FROM transactions t
        LEFT JOIN users u ON t.user_id = u.id
        WHERE t.status = 'en_attente' 
        AND t.urgency = 'urgent'
        AND (
            t.last_urgent_notification IS NULL 
            OR TIMESTAMPDIFF(SECOND, t.last_urgent_notification, NOW()) >= " . URGENT_NOTIFICATION_INTERVAL . "
        )
        AND t.urgent_notification_count < " . MAX_URGENT_NOTIFICATIONS . "
        ORDER BY t.created_at ASC
    ");
    
    $urgentTransactions = $stmt->fetchAll();
    
    if (empty($urgentTransactions)) {
        echo "✓ Aucune notification urgente à envoyer\n";
        exit(0);
    }
    
    echo "Trouvé " . count($urgentTransactions) . " demande(s) urgente(s) en attente\n\n";
    
    // Récupérer tous les admins actifs
    $adminStmt = $db->prepare("
        SELECT id, full_name, email, phone, whatsapp 
        FROM users 
        WHERE role_id = 1 AND is_active = 1
    ");
    $adminStmt->execute();
    $admins = $adminStmt->fetchAll();
    
    if (empty($admins)) {
        echo "⚠️ ATTENTION : Aucun administrateur actif trouvé\n";
        exit(1);
    }
    
    echo "Administrateurs actifs : " . count($admins) . "\n\n";
    
    $totalNotificationsSent = 0;
    $totalSMSSent = 0;
    $totalWhatsAppSent = 0;
    
    foreach ($urgentTransactions as $transaction) {
        echo "----------------------------------------------------\n";
        echo "Transaction #" . $transaction['id'] . "\n";
        echo "Utilisateur : " . $transaction['user_name'] . "\n";
        echo "Montant : " . formatAmount($transaction['amount']) . "\n";
        echo "Description : " . substr($transaction['description'], 0, 50) . "...\n";
        
        // Calculer depuis combien de temps la demande est en attente
        $createdTime = new DateTime($transaction['created_at']);
        $requiredDate = new DateTime($transaction['required_date']);
        $now = new DateTime();
        
        $waitingInterval = $createdTime->diff($now);
        $waitingTime = '';
        
        if ($waitingInterval->d > 0) {
            $waitingTime = $waitingInterval->d . ' jour' . ($waitingInterval->d > 1 ? 's' : '') . ' ';
        }
        if ($waitingInterval->h > 0) {
            $waitingTime .= $waitingInterval->h . ' heure' . ($waitingInterval->h > 1 ? 's' : '') . ' ';
        }
        $waitingTime .= $waitingInterval->i . ' minute' . ($waitingInterval->i > 1 ? 's' : '');
        
        echo "En attente depuis : $waitingTime\n";
        echo "Date requise : " . $requiredDate->format('d/m/Y') . "\n";
        echo "Notifications envoyées : " . $transaction['urgent_notification_count'] . "/" . MAX_URGENT_NOTIFICATIONS . "\n";
        
        // Vérifier si la date requise est proche
        $daysUntilRequired = $now->diff($requiredDate)->days;
        $isDateClose = $daysUntilRequired <= 2;
        
        if ($isDateClose) {
            echo "⚠️ ALERTE : Date requise dans $daysUntilRequired jour(s) !\n";
        }
        
        // Construire les messages
        $title = "🔴 URGENT - Demande en attente depuis $waitingTime";
        
        $message = "Demande urgente de {$transaction['user_name']} :\n";
        $message .= "Montant : " . formatAmount($transaction['amount']) . "\n";
        $message .= "Description : {$transaction['description']}\n";
        $message .= "Date requise : " . $requiredDate->format('d/m/Y') . "\n";
        
        if ($isDateClose) {
            $message .= "\n⚠️ ATTENTION : Fonds nécessaires dans $daysUntilRequired jour(s) !";
        }
        
        $shortMessage = "Demande urgente de {$transaction['user_name']} : " . formatAmount($transaction['amount']) . " - Date requise: " . $requiredDate->format('d/m/Y');
        
        // Envoyer les notifications à tous les admins
        foreach ($admins as $admin) {
            echo "  → Envoi à " . $admin['full_name'] . " :\n";
            
            // 1. Notification web (base de données)
            $webResult = sendBrowserNotification(
                $admin['id'],
                $title,
                $message,
                [
                    'type' => 'urgent',
                    'transaction_id' => $transaction['id']
                ]
            );
            
            if ($webResult) {
                echo "     ✓ Notification web\n";
                $totalNotificationsSent++;
            }
            
            // 2. Email
            $emailBody = getNotificationEmailTemplate($admin['full_name'], $title, $message, 'urgent');
            $emailBody .= "
                <div style='margin-top: 30px; padding: 20px; background-color: #fef2f2; border-left: 4px solid #dc2626; border-radius: 4px;'>
                    <p style='margin: 0; color: #991b1b; font-weight: bold;'>
                        Cette demande nécessite votre attention immédiate !
                    </p>
                </div>
                <p style='text-align: center; margin-top: 30px;'>
                    <a href='" . SITE_URL . "/admin/transactions.php?id={$transaction['id']}' 
                       style='display: inline-block; background-color: #dc2626; color: white; padding: 15px 40px; 
                              text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;'>
                        🚨 TRAITER MAINTENANT
                    </a>
                </p>
            ";
            
            if (sendEmail($admin['email'], $title . " - " . SITE_NAME, $emailBody, true)) {
                echo "     ✓ Email\n";
            }
            
            // 3. SMS (si numéro configuré)
            if (!empty($admin['phone'])) {
                if (sendSMS($admin['phone'], $shortMessage)) {
                    echo "     ✓ SMS\n";
                    $totalSMSSent++;
                } else {
                    echo "     ✗ SMS (erreur)\n";
                }
            } else {
                echo "     - SMS (pas de numéro)\n";
            }
            
            // 4. WhatsApp (si numéro configuré)
            if (!empty($admin['whatsapp'])) {
                if (sendWhatsApp($admin['whatsapp'], $message)) {
                    echo "     ✓ WhatsApp\n";
                    $totalWhatsAppSent++;
                } else {
                    echo "     ✗ WhatsApp (erreur)\n";
                }
            } else {
                echo "     - WhatsApp (pas de numéro)\n";
            }
        }
        
        // Mettre à jour la dernière notification et le compteur
        $updateStmt = $db->prepare("
            UPDATE transactions 
            SET last_urgent_notification = NOW(), 
                urgent_notification_count = urgent_notification_count + 1,
                is_urgent_notified = 1
            WHERE id = ?
        ");
        $updateStmt->execute([$transaction['id']]);
        
        echo "✓ Transaction mise à jour\n";
    }
    
    echo "\n================================================\n";
    echo "RÉSUMÉ\n";
    echo "================================================\n";
    echo "Transactions traitées : " . count($urgentTransactions) . "\n";
    echo "Notifications web envoyées : $totalNotificationsSent\n";
    echo "SMS envoyés : $totalSMSSent\n";
    echo "WhatsApp envoyés : $totalWhatsAppSent\n";
    echo "================================================\n";
    echo "Terminé le : " . date('Y-m-d H:i:s') . "\n";
    echo "================================================\n";
    
} catch (Exception $e) {
    echo "\n❌ ERREUR CRITIQUE\n";
    echo "Message : " . $e->getMessage() . "\n";
    echo "Fichier : " . $e->getFile() . "\n";
    echo "Ligne : " . $e->getLine() . "\n";
    echo "================================================\n";
    
    error_log("Erreur dans le script de notifications urgentes: " . $e->getMessage());
    exit(1);
}   