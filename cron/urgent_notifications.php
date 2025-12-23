<?php
/**
 * SCRIPT CRON POUR LES NOTIFICATIONS URGENTES
 * À exécuter toutes les 5 minutes via cron
 * Crontab: *//*5 * * * * php /path/to/cron/urgent_notifications.php
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

try {
    $db = getDB();
    
    // Récupérer toutes les transactions urgentes en attente
    $stmt = $db->query("
        SELECT t.id, t.user_id, t.amount, t.description, t.created_at,
               t.last_urgent_notification, t.is_urgent_notified,
               u.full_name as user_name
        FROM transactions t
        LEFT JOIN users u ON t.user_id = u.id
        WHERE t.status = 'en_attente' 
        AND t.urgency = 'urgent'
        AND (
            t.last_urgent_notification IS NULL 
            OR TIMESTAMPDIFF(SECOND, t.last_urgent_notification, NOW()) >= " . URGENT_NOTIFICATION_INTERVAL . "
        )
    ");
    
    $urgentTransactions = $stmt->fetchAll();
    
    if (!empty($urgentTransactions)) {
        // Récupérer tous les admins actifs
        $adminStmt = $db->prepare("SELECT id, full_name, email FROM users WHERE role_id = 1 AND is_active = 1");
        $adminStmt->execute();
        $admins = $adminStmt->fetchAll();
        
        foreach ($urgentTransactions as $transaction) {
            // Calculer depuis combien de temps la demande est en attente
            $createdTime = new DateTime($transaction['created_at']);
            $now = new DateTime();
            $interval = $createdTime->diff($now);
            $waitingTime = '';
            
            if ($interval->h > 0) {
                $waitingTime = $interval->h . ' heure' . ($interval->h > 1 ? 's' : '');
            } else {
                $waitingTime = $interval->i . ' minute' . ($interval->i > 1 ? 's' : '');
            }
            
            // Créer des notifications pour tous les admins
            foreach ($admins as $admin) {
                createNotification(
                    $admin['id'],
                    '🔴 URGENT - Demande en attente depuis ' . $waitingTime,
                    "Demande urgente de {$transaction['user_name']} : " . formatAmount($transaction['amount']) . " - {$transaction['description']}",
                    'urgent',
                    $transaction['id']
                );
                
                // Envoyer un email si configuré
                $emailSubject = "⚠️ DEMANDE URGENTE EN ATTENTE - " . SITE_NAME;
                $emailBody = "
                    <html>
                    <body style='font-family: Arial, sans-serif;'>
                        <div style='max-width: 600px; margin: 0 auto; padding: 20px; background-color: #FEF2F2; border-left: 4px solid #EF4444;'>
                            <h2 style='color: #DC2626;'>⚠️ DEMANDE URGENTE EN ATTENTE</h2>
                            <p><strong>Utilisateur:</strong> {$transaction['user_name']}</p>
                            <p><strong>Montant:</strong> " . formatAmount($transaction['amount']) . "</p>
                            <p><strong>Description:</strong> {$transaction['description']}</p>
                            <p><strong>En attente depuis:</strong> $waitingTime</p>
                            <p style='margin-top: 30px;'>
                                <a href='" . SITE_URL . "/admin/transactions.php?id={$transaction['id']}' 
                                   style='background-color: #DC2626; color: white; padding: 12px 24px; 
                                          text-decoration: none; border-radius: 5px; display: inline-block;'>
                                    Traiter maintenant
                                </a>
                            </p>
                        </div>
                    </body>
                    </html>
                ";
                
                sendEmail($admin['email'], $emailSubject, $emailBody, true);
            }
            
            // Mettre à jour la dernière notification
            $updateStmt = $db->prepare("
                UPDATE transactions 
                SET last_urgent_notification = NOW(), 
                    is_urgent_notified = 1 
                WHERE id = ?
            ");
            $updateStmt->execute([$transaction['id']]);
            
            echo "Notification envoyée pour la transaction #{$transaction['id']}\n";
        }
        
        echo "Total: " . count($urgentTransactions) . " notification(s) urgente(s) envoyée(s)\n";
    } else {
        echo "Aucune notification urgente à envoyer\n";
    }
    
} catch (Exception $e) {
    error_log("Erreur dans le script de notifications urgentes: " . $e->getMessage());
    echo "ERREUR: " . $e->getMessage() . "\n";
}
?>