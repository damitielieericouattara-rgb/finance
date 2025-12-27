<?php
/**
 * FONCTIONS MÉTIER - VERSION CORRIGÉE COMPLÈTE
 * NE redéclare PAS les fonctions de config.php
 */

// ========================================
// FONCTIONS DE GESTION DES MESSAGES FLASH
// ========================================

if (!function_exists('setFlashMessage')) {
    function setFlashMessage($type, $message) {
        $_SESSION['flash_message'] = [
            'type' => $type,
            'message' => $message
        ];
    }
}

if (!function_exists('getFlashMessage')) {
    function getFlashMessage() {
        if (isset($_SESSION['flash_message'])) {
            $flash = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            return $flash;
        }
        return null;
    }
}

// ========================================
// FONCTIONS DE TRANSACTIONS
// ========================================

if (!function_exists('getTransactions')) {
    function getTransactions($filters = [], $page = 1, $perPage = 20) {
        try {
            $db = getDB();
            $sql = "SELECT t.*, 
                           u.full_name as user_name, 
                           u.email as user_email,
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
            
            $sql .= " ORDER BY t.created_at DESC";
            
            if ($perPage > 0) {
                $offset = ($page - 1) * $perPage;
                $sql .= " LIMIT $perPage OFFSET $offset";
            }
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Erreur getTransactions: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('getTransactionById')) {
    function getTransactionById($id) {
        try {
            $db = getDB();
            $stmt = $db->prepare("
                SELECT t.*, 
                       u.full_name as user_name, 
                       u.email as user_email,
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
}

if (!function_exists('createTransaction')) {
    function createTransaction($userId, $type, $amount, $description, $requiredDate, $urgency = 'normal') {
        try {
            $db = getDB();
            $stmt = $db->prepare("
                INSERT INTO transactions (user_id, type, amount, description, required_date, urgency)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $success = $stmt->execute([
                $userId,
                $type,
                $amount,
                $description,
                $requiredDate,
                $urgency
            ]);
            
            if ($success) {
                $transactionId = $db->lastInsertId();
                
                // Notifier les admins
                $adminStmt = $db->prepare("SELECT id FROM users WHERE role_id = 1 AND is_active = 1");
                $adminStmt->execute();
                $admins = $adminStmt->fetchAll();
                
                $notifType = $urgency === 'urgent' ? 'urgent' : 'info';
                $title = $urgency === 'urgent' ? '🔴 Nouvelle demande URGENTE' : 'Nouvelle demande';
                
                foreach ($admins as $admin) {
                    createNotification(
                        $admin['id'],
                        $title,
                        "Nouvelle demande de " . formatAmount($amount) . " nécessite validation.",
                        $notifType,
                        $transactionId
                    );
                }
                
                return $transactionId;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Erreur createTransaction: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('validateTransaction')) {
    function validateTransaction($transactionId, $adminId, $comment = null) {
        try {
            $db = getDB();
            
            $transaction = getTransactionById($transactionId);
            if (!$transaction) {
                return false;
            }
            
            if ($transaction['status'] !== 'en_attente') {
                return false;
            }
            
            // Vérifier le solde pour les sorties
            if ($transaction['type'] === 'sortie') {
                $balance = getCurrentBalance();
                if ($balance < $transaction['amount']) {
                    return false;
                }
            }
            
            $receiptNumber = generateReceiptNumber();
            
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
}

if (!function_exists('rejectTransaction')) {
    function rejectTransaction($transactionId, $adminId, $comment) {
        try {
            $db = getDB();
            
            $transaction = getTransactionById($transactionId);
            if (!$transaction) {
                return false;
            }
            
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
                createNotification(
                    $transaction['user_id'],
                    'Transaction refusée',
                    "Votre transaction a été refusée. Motif: " . $comment,
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
}

if (!function_exists('countTransactions')) {
    function countTransactions($filters = []) {
        try {
            $db = getDB();
            $sql = "SELECT COUNT(*) as count FROM transactions WHERE 1=1";
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
            
            return $result ? intval($result['count']) : 0;
        } catch (Exception $e) {
            error_log("Erreur countTransactions: " . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('countPendingTransactions')) {
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
}

// ========================================
// FONCTIONS UTILISATEURS
// ========================================

if (!function_exists('getAllUsers')) {
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
}

if (!function_exists('getUserById')) {
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
}

if (!function_exists('createUser')) {
    function createUser($username, $email, $password, $fullName, $roleId = 2) {
        try {
            $db = getDB();
            
            // Vérifier si l'email existe déjà
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                return false;
            }
            
            // Vérifier si le username existe déjà
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                return false;
            }
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("
                INSERT INTO users (username, email, password, full_name, role_id, email_verified, is_active)
                VALUES (?, ?, ?, ?, ?, 1, 1)
            ");
            
            return $stmt->execute([$username, $email, $hashedPassword, $fullName, $roleId]);
        } catch (Exception $e) {
            error_log("Erreur createUser: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('updateUser')) {
    function updateUser($id, $username, $email, $fullName, $roleId, $isActive) {
        try {
            $db = getDB();
            
            if ($id === 1) {
                return false; // Ne pas modifier le super admin
            }
            
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
}

if (!function_exists('deleteUser')) {
    function deleteUser($id) {
        try {
            $db = getDB();
            
            if ($id === 1) {
                return false; // Ne pas supprimer le super admin
            }
            
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Erreur deleteUser: " . $e->getMessage());
            return false;
        }
    }
}

// ========================================
// FONCTIONS STATISTIQUES
// ========================================

if (!function_exists('getDashboardStats')) {
    function getDashboardStats() {
        try {
            $db = getDB();
            
            $stats = [
                'current_balance' => getCurrentBalance(),
                'pending_count' => 0,
                'validated_count' => 0,
                'rejected_count' => 0,
                'total_transactions' => 0,
                'month_income' => 0,
                'month_expenses' => 0
            ];
            
            // Transactions en attente
            $stmt = $db->query("SELECT COUNT(*) as count FROM transactions WHERE status = 'en_attente'");
            $result = $stmt->fetch();
            $stats['pending_count'] = $result ? intval($result['count']) : 0;
            
            // Validées
            $stmt = $db->query("SELECT COUNT(*) as count FROM transactions WHERE status = 'validee'");
            $result = $stmt->fetch();
            $stats['validated_count'] = $result ? intval($result['count']) : 0;
            
            // Refusées
            $stmt = $db->query("SELECT COUNT(*) as count FROM transactions WHERE status = 'refusee'");
            $result = $stmt->fetch();
            $stats['rejected_count'] = $result ? intval($result['count']) : 0;
            
            // Total
            $stats['total_transactions'] = $stats['pending_count'] + $stats['validated_count'] + $stats['rejected_count'];
            
            // Revenus du mois
            $stmt = $db->query("
                SELECT COALESCE(SUM(amount), 0) as total 
                FROM transactions 
                WHERE type = 'entree' 
                AND status = 'validee' 
                AND MONTH(validated_at) = MONTH(CURRENT_DATE) 
                AND YEAR(validated_at) = YEAR(CURRENT_DATE)
            ");
            $result = $stmt->fetch();
            $stats['month_income'] = $result ? floatval($result['total']) : 0;
            
            // Dépenses du mois
            $stmt = $db->query("
                SELECT COALESCE(SUM(amount), 0) as total 
                FROM transactions 
                WHERE type = 'sortie' 
                AND status = 'validee' 
                AND MONTH(validated_at) = MONTH(CURRENT_DATE) 
                AND YEAR(validated_at) = YEAR(CURRENT_DATE)
            ");
            $result = $stmt->fetch();
            $stats['month_expenses'] = $result ? floatval($result['total']) : 0;
            
            return $stats;
        } catch (Exception $e) {
            error_log("Erreur getDashboardStats: " . $e->getMessage());
            return [
                'current_balance' => 0,
                'pending_count' => 0,
                'validated_count' => 0,
                'rejected_count' => 0,
                'total_transactions' => 0,
                'month_income' => 0,
                'month_expenses' => 0
            ];
        }
    }
}

// ========================================
// FONCTIONS DE LABELS
// ========================================

if (!function_exists('getStatusLabel')) {
    function getStatusLabel($status) {
        $labels = [
            'en_attente' => 'En attente',
            'validee' => 'Validée',
            'refusee' => 'Refusée'
        ];
        return $labels[$status] ?? $status;
    }
}