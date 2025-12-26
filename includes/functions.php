<?php
/**
 * FONCTIONS MÉTIER - VERSION COMPLÈTE ET CORRIGÉE
 * Contient TOUTES les fonctions nécessaires à l'application
 */

// ========================================
// FONCTIONS DE STATUT ET BADGES
// ========================================

/**
 * Obtenir le badge HTML d'un statut
 */
function getStatusBadge($status) {
    $badges = [
        'en_attente' => '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200">⏳ En attente</span>',
        'validee' => '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">✅ Validée</span>',
        'refusee' => '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">❌ Refusée</span>',
        'annulee' => '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">🚫 Annulée</span>',
    ];
    
    return $badges[$status] ?? '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">' . htmlspecialchars($status) . '</span>';
}

/**
 * Obtenir le label textuel d'un statut
 */
function getStatusLabel($status) {
    $labels = [
        'en_attente' => 'En attente',
        'validee' => 'Validée',
        'refusee' => 'Refusée',
        'annulee' => 'Annulée',
    ];
    
    return $labels[$status] ?? $status;
}

/**
 * Obtenir la couleur d'un statut
 */
function getStatusColor($status) {
    $colors = [
        'en_attente' => 'yellow',
        'validee' => 'green',
        'refusee' => 'red',
        'annulee' => 'gray',
    ];
    
    return $colors[$status] ?? 'gray';
}

/**
 * Obtenir le label d'un type de transaction
 */
function getTypeLabel($type) {
    $labels = [
        'entree' => 'Entrée',
        'sortie' => 'Sortie'
    ];
    
    return $labels[$type] ?? $type;
}

/**
 * Obtenir le label d'urgence
 */
function getUrgencyLabel($urgency) {
    return $urgency === 'urgent' ? 'Urgent' : 'Normal';
}

// ========================================
// FONCTIONS DE FORMATAGE
// ========================================

/**
 * Formater un montant en FCFA
 */
function formatAmount($amount) {
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

/**
 * Formater une date
 */
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '-';
    
    try {
        $dateObj = new DateTime($date);
        return $dateObj->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

/**
 * Formater une date/heure
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if (empty($datetime)) return '-';
    
    try {
        $date = new DateTime($datetime);
        return $date->format($format);
    } catch (Exception $e) {
        return $datetime;
    }
}

/**
 * Nettoyer les entrées utilisateur
 */
// function cleanInput($data) {
//     $data = trim($data);
//     $data = stripslashes($data);
//     $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
//     return $data;
// }

/**
 * Générer un numéro de reçu
 */
function generateReceiptNumber() {
    return 'REC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

// ========================================
// FONCTIONS DE SOLDE
// ========================================

/**
 * Obtenir le solde global actuel
 */
function getCurrentBalance() {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT balance FROM global_balance WHERE id = 1");
        $result = $stmt->fetch();
        return $result ? floatval($result['balance']) : 0;
    } catch (Exception $e) {
        error_log("Erreur getCurrentBalance: " . $e->getMessage());
        return 0;
    }
}

// ========================================
// FONCTIONS DE NOTIFICATIONS
// ========================================

/**
 * Créer une notification
 */
function createNotification($userId, $title, $message, $type = 'info', $transactionId = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, transaction_id, title, message, type)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$userId, $transactionId, $title, $message, $type]);
    } catch (Exception $e) {
        error_log("Erreur createNotification: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtenir les notifications non lues d'un utilisateur
 */
function getUnreadNotifications($userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? AND is_read = 0 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Erreur getUnreadNotifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Compter les notifications non lues
 */
function countUnreadNotifications($userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM notifications 
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result ? intval($result['count']) : 0;
    } catch (Exception $e) {
        error_log("Erreur countUnreadNotifications: " . $e->getMessage());
        return 0;
    }
}

/**
 * Marquer une notification comme lue
 */
function markNotificationAsRead($notificationId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        return $stmt->execute([$notificationId]);
    } catch (Exception $e) {
        error_log("Erreur markNotificationAsRead: " . $e->getMessage());
        return false;
    }
}

/**
 * Compter les transactions en attente (urgentes)
 */
function countPendingTransactions() {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT COUNT(*) as count FROM transactions WHERE status = 'en_attente'");
        $result = $stmt->fetch();
        return $result ? intval($result['count']) : 0;
    } catch (Exception $e) {
        error_log("Erreur countPendingTransactions: " . $e->getMessage());
        return 0;
    }
}

/**
 * Notifier les admins pour transactions urgentes
 */
function notifyAdminsPendingTransactions() {
    try {
        $pendingCount = countPendingTransactions();
        
        if ($pendingCount > 0) {
            $db = getDB();
            
            // Récupérer tous les admins
            $stmt = $db->query("SELECT id FROM users WHERE role_id = 1 AND is_active = 1");
            $admins = $stmt->fetchAll();
            
            foreach ($admins as $admin) {
                // Vérifier s'il n'y a pas déjà une notification similaire récente (moins de 1h)
                $checkStmt = $db->prepare("
                    SELECT id FROM notifications 
                    WHERE user_id = ? 
                    AND title LIKE '%transactions en attente%'
                    AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                ");
                $checkStmt->execute([$admin['id']]);
                
                if ($checkStmt->rowCount() == 0) {
                    createNotification(
                        $admin['id'],
                        "⚠️ {$pendingCount} transaction(s) en attente !",
                        "Il y a actuellement {$pendingCount} transaction(s) qui nécessitent votre validation. Veuillez les traiter rapidement.",
                        'warning',
                        null
                    );
                }
            }
        }
    } catch (Exception $e) {
        error_log("Erreur notifyAdminsPendingTransactions: " . $e->getMessage());
    }
}

/**
 * Vérifier et notifier pour les transactions urgentes
 */
function checkUrgentTransactions() {
    try {
        $db = getDB();
        
        // Transactions en attente depuis plus de 2 heures
        $stmt = $db->query("
            SELECT t.id, t.user_id, u.full_name, t.amount, t.created_at
            FROM transactions t
            JOIN users u ON t.user_id = u.id
            WHERE t.status = 'en_attente' 
            AND t.created_at < DATE_SUB(NOW(), INTERVAL 2 HOUR)
        ");
        $urgentTransactions = $stmt->fetchAll();
        
        if (!empty($urgentTransactions)) {
            // Récupérer tous les admins
            $adminStmt = $db->query("SELECT id FROM users WHERE role_id = 1 AND is_active = 1");
            $admins = $adminStmt->fetchAll();
            
            foreach ($urgentTransactions as $trans) {
                foreach ($admins as $admin) {
                    // Vérifier si notification déjà envoyée
                    $checkStmt = $db->prepare("
                        SELECT id FROM notifications 
                        WHERE user_id = ? 
                        AND transaction_id = ?
                        AND title LIKE '%URGENT%'
                    ");
                    $checkStmt->execute([$admin['id'], $trans['id']]);
                    
                    if ($checkStmt->rowCount() == 0) {
                        $hours = round((time() - strtotime($trans['created_at'])) / 3600, 1);
                        createNotification(
                            $admin['id'],
                            "🚨 URGENT: Transaction en attente depuis {$hours}h",
                            "La transaction #{$trans['id']} de {$trans['full_name']} ({$trans['amount']} FCFA) attend validation depuis {$hours} heures !",
                            'urgent',
                            $trans['id']
                        );
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Erreur checkUrgentTransactions: " . $e->getMessage());
    }
}

// ========================================
// FONCTIONS DE TRANSACTIONS
// ========================================

/**
 * Obtenir les transactions avec pagination
 */
function getTransactions($filters = [], $page = 1, $perPage = 20) {
    try {
        $db = getDB();
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT t.*, u.full_name as user_name, u.email as user_email,
                       v.full_name as validator_name
                FROM transactions t
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN users v ON t.validated_by = v.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $sql .= " AND t.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND t.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['type'])) {
            $sql .= " AND t.type = ?";
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['urgency'])) {
            $sql .= " AND t.urgency = ?";
            $params[] = $filters['urgency'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(t.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(t.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY t.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Erreur getTransactions: " . $e->getMessage());
        return [];
    }
}

/**
 * Compter le nombre total de transactions
 */
function countTransactions($filters = []) {
    try {
        $db = getDB();
        
        $sql = "SELECT COUNT(*) as total FROM transactions WHERE 1=1";
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $sql .= " AND user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['type'])) {
            $sql .= " AND type = ?";
            $params[] = $filters['type'];
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return $result['total'] ?? 0;
    } catch (Exception $e) {
        error_log("Erreur countTransactions: " . $e->getMessage());
        return 0;
    }
}

/**
 * Obtenir une transaction par ID
 */
function getTransactionById($id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT t.*, u.full_name as user_name, u.email as user_email,
                   v.full_name as validator_name
            FROM transactions t
            LEFT JOIN users u ON t.user_id = u.id
            LEFT JOIN users v ON t.validated_by = v.id
            WHERE t.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Erreur getTransactionById: " . $e->getMessage());
        return null;
    }
}

/**
 * Créer une nouvelle transaction
 */
function createTransaction($userId, $type, $amount, $description, $requiredDate, $urgency) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO transactions (user_id, type, amount, description, required_date, urgency)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $success = $stmt->execute([$userId, $type, $amount, $description, $requiredDate, $urgency]);
        
        if ($success) {
            $transactionId = $db->lastInsertId();
            
            // Notifier tous les admins
            notifyAdminsNewTransaction($transactionId, $urgency);
            
            return $transactionId;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Erreur createTransaction: " . $e->getMessage());
        return false;
    }
}

/**
 * Valider une transaction
 */
function validateTransaction($transactionId, $adminId, $comment = null) {
    try {
        $db = getDB();
        
        // Récupérer la transaction
        $transaction = getTransactionById($transactionId);
        if (!$transaction) {
            return false;
        }
        
        // Générer un numéro de reçu
        $receiptNumber = generateReceiptNumber();
        
        // Mettre à jour la transaction
        $stmt = $db->prepare("
            UPDATE transactions 
            SET status = 'validee', 
                validated_by = ?,
                validated_at = NOW(),
                admin_comment = ?,
                receipt_number = ?
            WHERE id = ?
        ");
        
        $success = $stmt->execute([$adminId, $comment, $receiptNumber, $transactionId]);
        
        if ($success) {
            // Notifier l'utilisateur
            createNotification(
                $transaction['user_id'],
                'Transaction validée',
                "Votre transaction de " . formatAmount($transaction['amount']) . " a été validée.",
                'success',
                $transactionId
            );
            
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Erreur validateTransaction: " . $e->getMessage());
        return false;
    }
}

/**
 * Refuser une transaction
 */
function rejectTransaction($transactionId, $adminId, $comment) {
    try {
        $db = getDB();
        
        // Récupérer la transaction
        $transaction = getTransactionById($transactionId);
        if (!$transaction) {
            return false;
        }
        
        // Mettre à jour la transaction
        $stmt = $db->prepare("
            UPDATE transactions 
            SET status = 'refusee', 
                validated_by = ?,
                validated_at = NOW(),
                admin_comment = ?
            WHERE id = ?
        ");
        
        $success = $stmt->execute([$adminId, $comment, $transactionId]);
        
        if ($success) {
            // Notifier l'utilisateur
            createNotification(
                $transaction['user_id'],
                'Transaction refusée',
                "Votre transaction de " . formatAmount($transaction['amount']) . " a été refusée. Motif: " . $comment,
                'warning',
                $transactionId
            );
            
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Erreur rejectTransaction: " . $e->getMessage());
        return false;
    }
}

/**
 * Notifier tous les admins d'une nouvelle transaction
 */
function notifyAdminsNewTransaction($transactionId, $urgency) {
    try {
        $db = getDB();
        
        // Récupérer tous les admins
        $stmt = $db->prepare("SELECT id FROM users WHERE role_id = 1 AND is_active = 1");
        $stmt->execute();
        $admins = $stmt->fetchAll();
        
        $type = $urgency === 'urgent' ? 'urgent' : 'info';
        $title = $urgency === 'urgent' ? '🔴 Nouvelle demande URGENTE' : 'Nouvelle demande de transaction';
        
        foreach ($admins as $admin) {
            createNotification(
                $admin['id'],
                $title,
                "Une nouvelle demande de transaction nécessite votre attention.",
                $type,
                $transactionId
            );
        }
    } catch (Exception $e) {
        error_log("Erreur notifyAdminsNewTransaction: " . $e->getMessage());
    }
}

// ========================================
// FONCTIONS UTILISATEURS
// ========================================

/**
 * Obtenir tous les utilisateurs
 */
function getAllUsers() {
    try {
        $db = getDB();
        $stmt = $db->query("
            SELECT u.*, r.name as role_name 
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.id 
            ORDER BY u.created_at DESC
        ");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Erreur getAllUsers: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtenir un utilisateur par ID
 */
function getUserById($id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT u.*, r.name as role_name 
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.id 
            WHERE u.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Erreur getUserById: " . $e->getMessage());
        return null;
    }
}

/**
 * Créer un utilisateur
 */
function createUser($username, $email, $password, $fullName, $roleId) {
    try {
        $db = getDB();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("
            INSERT INTO users (username, email, password, full_name, role_id, email_verified)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        
        return $stmt->execute([$username, $email, $hashedPassword, $fullName, $roleId]);
    } catch (Exception $e) {
        error_log("Erreur createUser: " . $e->getMessage());
        return false;
    }
}

/**
 * Mettre à jour un utilisateur
 */
function updateUser($id, $username, $email, $fullName, $roleId, $isActive) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            UPDATE users 
            SET username = ?, email = ?, full_name = ?, role_id = ?, is_active = ?
            WHERE id = ?
        ");
        
        return $stmt->execute([$username, $email, $fullName, $roleId, $isActive, $id]);
    } catch (Exception $e) {
        error_log("Erreur updateUser: " . $e->getMessage());
        return false;
    }
}

/**
 * Supprimer un utilisateur
 */
function deleteUser($id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role_id != 1");
        return $stmt->execute([$id]);
    } catch (Exception $e) {
        error_log("Erreur deleteUser: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtenir les statistiques du dashboard
 */
function getDashboardStats($period = 'day') {
    try {
        $db = getDB();
        $stmt = $db->prepare("CALL get_dashboard_stats(?)");
        $stmt->execute([$period]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Erreur getDashboardStats: " . $e->getMessage());
        return [
            'current_balance' => 0,
            'pending_count' => 0,
            'validated_count' => 0,
            'rejected_count' => 0,
            'total_entrees' => 0,
            'total_sorties' => 0
        ];
    }
}

/**
 * Messages flash
 */
// function setFlashMessage($type, $message) {
//     $_SESSION['flash_message'] = [
//         'type' => $type,
//         'message' => $message
//     ];
// }

// function getFlashMessage() {
//     if (isset($_SESSION['flash_message'])) {
//         $flash = $_SESSION['flash_message'];
//         unset($_SESSION['flash_message']);
//         return $flash;
//     }
//     return null;
// }