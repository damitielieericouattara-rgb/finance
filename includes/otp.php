<?php
// ========================================
// SYSTÈME OTP (One-Time Password) - VERSION CORRIGÉE
// Pour vérification email lors de l'inscription et reset password
// ========================================

/**
 * Générer un code OTP à 6 chiffres
 */
function generateOTP() {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Envoyer un OTP par email - VERSION CORRIGÉE AVEC DEBUG
 */
function sendOTP($email, $type = 'registration') {
    $code = generateOTP();
    $expiresAt = date('Y-m-d H:i:s', time() + OTP_EXPIRY);
    
    try {
        $db = getDB();
        
        // Supprimer les anciens OTP pour cet email
        $deleteStmt = $db->prepare("DELETE FROM email_verifications WHERE email = ? AND type = ?");
        $deleteStmt->execute([$email, $type]);
        
        // Insérer le nouveau OTP
        $insertStmt = $db->prepare("
            INSERT INTO email_verifications (email, code, type, expires_at)
            VALUES (?, ?, ?, ?)
        ");
        $insertStmt->execute([$email, $code, $type, $expiresAt]);
        
        // Envoyer l'email
        $subject = ($type === 'registration') 
            ? "Code de vérification - " . SITE_NAME 
            : "Code de réinitialisation - " . SITE_NAME;
        
        $body = getOTPEmailTemplate($code, $type);
        
        if (sendEmail($email, $subject, $body, true)) {
            // Détecter l'environnement de développement
            $isLocalhost = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']) 
                           || in_array($_SERVER['SERVER_ADDR'] ?? '', ['localhost', '127.0.0.1']);
            
            // En mode développement, logger le code et le retourner
            if ($isLocalhost) {
                error_log("🔑 CODE OTP POUR $email : $code (Type: $type)");
            }
            
            return array(
                'success' => true,
                'message' => "Un code de vérification a été envoyé à votre adresse email.",
                'debug_code' => $isLocalhost ? $code : null // Retourner le code seulement en dev
            );
        } else {
            throw new Exception("Erreur lors de l'envoi de l'email");
        }
        
    } catch (Exception $e) {
        error_log("Erreur sendOTP: " . $e->getMessage());
        return array(
            'success' => false,
            'message' => "Une erreur est survenue lors de l'envoi du code."
        );
    }
}

/**
 * Vérifier un code OTP
 */
function verifyOTP($email, $code, $type = 'registration') {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT * FROM email_verifications
            WHERE email = ? AND code = ? AND type = ? AND expires_at > NOW() AND verified = 0
        ");
        $stmt->execute([$email, $code, $type]);
        $verification = $stmt->fetch();
        
        if ($verification) {
            // Marquer comme vérifié
            $updateStmt = $db->prepare("
                UPDATE email_verifications 
                SET verified = 1 
                WHERE id = ?
            ");
            $updateStmt->execute([$verification['id']]);
            
            return array(
                'success' => true,
                'message' => "Code vérifié avec succès."
            );
        } else {
            return array(
                'success' => false,
                'message' => "Code invalide ou expiré."
            );
        }
        
    } catch (Exception $e) {
        error_log("Erreur verifyOTP: " . $e->getMessage());
        return array(
            'success' => false,
            'message' => "Une erreur est survenue lors de la vérification."
        );
    }
}

/**
 * Vérifier si un email a été vérifié récemment
 */
function isEmailVerified($email, $type = 'registration') {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT * FROM email_verifications
            WHERE email = ? AND type = ? AND verified = 1 
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$email, $type]);
        
        return $stmt->fetch() !== false;
        
    } catch (Exception $e) {
        error_log("Erreur isEmailVerified: " . $e->getMessage());
        return false;
    }
}

/**
 * Template HTML pour l'email OTP
 */
function getOTPEmailTemplate($code, $type) {
    $title = ($type === 'registration') ? 'Vérification de votre email' : 'Réinitialisation de mot de passe';
    $message = ($type === 'registration') 
        ? 'Bienvenue ! Veuillez utiliser le code ci-dessous pour vérifier votre adresse email et activer votre compte.'
        : 'Vous avez demandé la réinitialisation de votre mot de passe. Utilisez le code ci-dessous pour continuer.';
    
    $siteName = defined('SITE_NAME') ? SITE_NAME : 'Gestion Financière';
    $currentYear = date('Y');
    
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
                background: linear-gradient(135deg, #059669 0%, #047857 100%);
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
            .otp-code {
                background-color: #f0fdf4;
                border: 2px solid #059669;
                border-radius: 8px;
                padding: 20px;
                text-align: center;
                margin: 30px 0;
            }
            .otp-code .code {
                font-size: 36px;
                font-weight: bold;
                color: #059669;
                letter-spacing: 8px;
                font-family: 'Courier New', monospace;
            }
            .warning {
                background-color: #fef3c7;
                border-left: 4px solid #f59e0b;
                padding: 15px;
                margin: 20px 0;
                border-radius: 4px;
            }
            .footer {
                background-color: #f9fafb;
                padding: 20px;
                text-align: center;
                color: #6b7280;
                font-size: 14px;
            }
            p {
                line-height: 1.6;
                color: #374151;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔐 {$title}</h1>
            </div>
            
            <div class='content'>
                <p>{$message}</p>
                
                <div class='otp-code'>
                    <p style='margin: 0 0 10px 0; color: #6b7280; font-size: 14px;'>Votre code de vérification :</p>
                    <div class='code'>{$code}</div>
                </div>
                
                <div class='warning'>
                    <strong>⚠️ Important :</strong>
                    <ul style='margin: 10px 0; padding-left: 20px;'>
                        <li>Ce code expire dans <strong>10 minutes</strong></li>
                        <li>Ne partagez jamais ce code avec qui que ce soit</li>
                        <li>Si vous n'avez pas demandé ce code, ignorez cet email</li>
                    </ul>
                </div>
                
                <p style='margin-top: 30px;'>
                    Si vous avez des questions, n'hésitez pas à nous contacter.
                </p>
            </div>
            
            <div class='footer'>
                <p style='margin: 0;'>
                    © {$currentYear} {$siteName}. Tous droits réservés.
                </p>
                <p style='margin: 10px 0 0 0;'>
                    Cet email a été envoyé automatiquement, merci de ne pas y répondre.
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
}

/**
 * Nettoyer les OTP expirés (à appeler régulièrement)
 */
function cleanupExpiredOTP() {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM email_verifications WHERE expires_at < NOW()");
        $stmt->execute();
        
        return $stmt->rowCount();
    } catch (Exception $e) {
        error_log("Erreur cleanupExpiredOTP: " . $e->getMessage());
        return 0;
    }
}