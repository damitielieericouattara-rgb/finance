<?php
/**
 * Script CRON pour vérifier et notifier les transactions urgentes
 * À exécuter toutes les 30 minutes
 * 
 * Crontab : */30 * * * * php /var/www/html/finance/cron/check_urgent_transactions.php
 */

// Définir le chemin racine
define('ROOT_PATH', dirname(__DIR__));

// Charger la configuration
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/includes/functions.php';

// Forcer l'exécution même si appelé depuis le navigateur
if (php_sapi_name() !== 'cli') {
    // Vérifier qu'on est en localhost pour des raisons de sécurité
    if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
        die('Access denied');
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Vérification des transactions urgentes...\n";

try {
    $db = getDB();
    
    // 1. NOTIFIER POUR TOUTES LES TRANSACTIONS EN ATTENTE
    $pendingCount = countPendingTransactions();
    echo "Transactions en attente : {$pendingCount}\n";
    
    if ($pendingCount > 0) {
        // Récupérer tous les admins actifs
        $stmt = $db->query("SELECT id, full_name, email FROM users WHERE role_id = 1 AND is_active = 1");
        $admins = $stmt->fetchAll();
        echo "Admins trouvés : " . count($admins) . "\n";
        
        foreach ($admins as $admin) {
            // Vérifier si notification récente existe (moins de 30 minutes)
            $checkStmt = $db->prepare("
                SELECT id FROM notifications 
                WHERE user_id = ? 
                AND title LIKE '%transaction%en attente%'
                AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
            ");
            $checkStmt->execute([$admin['id']]);
            
            if ($checkStmt->rowCount() == 0) {
                // Créer la notification
                $result = createNotification(
                    $admin['id'],
                    "⚠️ {$pendingCount} transaction(s) à traiter !",
                    "Vous avez actuellement {$pendingCount} transaction(s) en attente de validation. Veuillez les traiter rapidement pour assurer un bon service.",
                    'warning',
                    null
                );
                
                if ($result) {
                    echo "✓ Notification envoyée à {$admin['full_name']}\n";
                } else {
                    echo "✗ Erreur lors de l'envoi à {$admin['full_name']}\n";
                }
            } else {
                echo "- Notification déjà envoyée récemment à {$admin['full_name']}\n";
            }
        }
    }
    
    // 2. NOTIFIER POUR LES TRANSACTIONS TRÈS URGENTES (plus de 2h)
    $urgentStmt = $db->query("
        SELECT t.id, t.user_id, t.amount, t.description, t.created_at,
               u.full_name as user_name, u.email as user_email
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        WHERE t.status = 'en_attente' 
        AND t.created_at < DATE_SUB(NOW(), INTERVAL 2 HOUR)
        ORDER BY t.created_at ASC
    ");
    $urgentTransactions = $urgentStmt->fetchAll();
    
    echo "Transactions urgentes (>2h) : " . count($urgentTransactions) . "\n";
    
    if (!empty($urgentTransactions)) {
        $admins = $db->query("SELECT id, full_name FROM users WHERE role_id = 1 AND is_active = 1")->fetchAll();
        
        foreach ($urgentTransactions as $trans) {
            $hours = round((time() - strtotime($trans['created_at'])) / 3600, 1);
            
            foreach ($admins as $admin) {
                // Vérifier si notification urgente déjà envoyée pour cette transaction
                $checkStmt = $db->prepare("
                    SELECT id FROM notifications 
                    WHERE user_id = ? 
                    AND transaction_id = ?
                    AND type = 'error'
                    AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                ");
                $checkStmt->execute([$admin['id'], $trans['id']]);
                
                if ($checkStmt->rowCount() == 0) {
                    $result = createNotification(
                        $admin['id'],
                        "🚨 URGENT: Transaction #{$trans['id']} en attente depuis {$hours}h !",
                        "La transaction de {$trans['user_name']} (" . formatAmount($trans['amount']) . ") attend validation depuis {$hours} heures. Action immédiate requise !",
                        'error',
                        $trans['id']
                    );
                    
                    if ($result) {
                        echo "✓ Notification urgente envoyée pour transaction #{$trans['id']}\n";
                    }
                }
            }
        }
    }
    
    // 3. NOTIFIER POUR LES TRANSACTIONS CRITIQUES (plus de 6h)
    $criticalStmt = $db->query("
        SELECT t.id, t.amount, t.created_at, u.full_name as user_name
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        WHERE t.status = 'en_attente' 
        AND t.created_at < DATE_SUB(NOW(), INTERVAL 6 HOUR)
    ");
    $criticalTransactions = $criticalStmt->fetchAll();
    
    if (!empty($criticalTransactions)) {
        echo "⚠️ ALERTE: " . count($criticalTransactions) . " transaction(s) critiques (>6h)\n";
        
        $admins = $db->query("SELECT id, full_name FROM users WHERE role_id = 1 AND is_active = 1")->fetchAll();
        
        foreach ($criticalTransactions as $trans) {
            $hours = round((time() - strtotime($trans['created_at'])) / 3600, 1);
            
            foreach ($admins as $admin) {
                // Notification critique toutes les 2 heures
                $checkStmt = $db->prepare("
                    SELECT id FROM notifications 
                    WHERE user_id = ? 
                    AND transaction_id = ?
                    AND title LIKE '%CRITIQUE%'
                    AND created_at > DATE_SUB(NOW(), INTERVAL 2 HOUR)
                ");
                $checkStmt->execute([$admin['id'], $trans['id']]);
                
                if ($checkStmt->rowCount() == 0) {
                    createNotification(
                        $admin['id'],
                        "🔴 CRITIQUE: Transaction #{$trans['id']} bloquée depuis {$hours}h !",
                        "ATTENTION IMMÉDIATE REQUISE ! La transaction de {$trans['user_name']} est en attente depuis {$hours} heures. Cela impacte la qualité du service.",
                        'error',
                        $trans['id']
                    );
                    echo "✓ Notification critique envoyée pour transaction #{$trans['id']}\n";
                }
            }
        }
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] Vérification terminée avec succès\n";
    echo str_repeat("=", 60) . "\n";
    
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    error_log("Erreur check_urgent_transactions: " . $e->getMessage());
}
?>