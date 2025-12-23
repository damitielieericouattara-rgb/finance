<?php
// ========================================
// FONCTIONS MÉTIER
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
        error_log("Erreur création notification: " . $e->getMessage());
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
        error_log("Erreur récupération notifications: " . $e->getMessage());
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
        return $result['count'] ?? 0;
    } catch (Exception $e) {
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
        error_log("Erreur marquage notification: " . $e->getMessage());
        return false;
    }
}

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
        error_log("Erreur récupération solde: " . $e->getMessage());
        return 0;
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
        error_log("Erreur stats dashboard: " . $e->getMessage());
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
        error_log("Erreur récupération transactions: " . $e->getMessage());
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
        error_log("Erreur récupération transaction: " . $e->getMessage());
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
        error_log("Erreur création transaction: " . $e->getMessage());
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
        error_log("Erreur validation transaction: " . $e->getMessage());
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
        error_log("Erreur refus transaction: " . $e->getMessage());
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
        error_log("Erreur notification admins: " . $e->getMessage());
    }
}

/**
 * Obtenir les données pour les graphiques
 */
function getChartData($period = 'month') {
    try {
        $db = getDB();
        
        $dateFilter = match($period) {
            'week' => "DATE(t.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)",
            'month' => "DATE(t.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
            'year' => "DATE(t.created_at) >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)",
            default => "DATE(t.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
        };
        
        $stmt = $db->query("
            SELECT 
                DATE(created_at) as date,
                type,
                SUM(amount) as total
            FROM transactions
            WHERE status = 'validee' AND $dateFilter
            GROUP BY DATE(created_at), type
            ORDER BY date ASC
        ");
        
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Erreur données graphiques: " . $e->getMessage());
        return [];
    }
}

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
        error_log("Erreur récupération utilisateurs: " . $e->getMessage());
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
            INSERT INTO users (username, email, password, full_name, role_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([$username, $email, $hashedPassword, $fullName, $roleId]);
    } catch (Exception $e) {
        error_log("Erreur création utilisateur: " . $e->getMessage());
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
        error_log("Erreur mise à jour utilisateur: " . $e->getMessage());
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
        error_log("Erreur suppression utilisateur: " . $e->getMessage());
        return false;
    }
}
?>