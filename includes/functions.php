<?php
/**
 * FONCTIONS MÉTIER - VERSION CORRIGÉE
 * Ne redéclare PAS les fonctions de config.php
 */

// ========================================
// FONCTIONS DE TRANSACTIONS
// ========================================

/**
 * Obtenir les transactions avec filtres
 */
if (!function_exists('getTransactions')) {
    function getTransactions($pdo, $filters = []) {
        try {
            $sql = "SELECT t.*, 
                           u.full_name as user_full_name, 
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
            
            if (!empty($filters['search'])) {
                $sql .= " AND (t.description LIKE ? OR u.full_name LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $order = $filters['order'] ?? 'created_at DESC';
            $sql .= " ORDER BY " . $order;
            
            if (!empty($filters['limit'])) {
                $sql .= " LIMIT " . intval($filters['limit']);
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Erreur getTransactions: " . $e->getMessage());
            return [];
        }
    }
}

/**
 * Obtenir une transaction par ID
 */
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

/**
 * Créer une transaction
 */
if (!function_exists('createTransaction')) {
    function createTransaction($pdo, $data) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO transactions (user_id, type, amount, description, required_date, urgency)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $success = $stmt->execute([
                $data['user_id'],
                $data['type'],
                $data['amount'],
                $data['reason'], // Correspond au champ 'reason' du formulaire
                $data['request_date'],
                $data['urgency'] ?? 'normal'
            ]);
            
            if ($success) {
                $transactionId = $pdo->lastInsertId();
                
                // Notifier les admins
                $adminStmt = $pdo->prepare("SELECT id FROM users WHERE role_id = 1 AND is_active = 1");
                $adminStmt->execute();
                $admins = $adminStmt->fetchAll();
                
                $type = $data['urgency'] === 'urgent' ? 'urgent' : 'info';
                $title = $data['urgency'] === 'urgent' ? '🔴 Nouvelle demande URGENTE' : 'Nouvelle demande';
                
                foreach ($admins as $admin) {
                    createNotification(
                        $admin['id'],
                        $title,
                        "Nouvelle demande de transaction nécessite validation.",
                        $type,
                        $transactionId
                    );
                }
                
                return ['success' => true, 'transaction_id' => $transactionId];
            }
            
            return ['success' => false, 'message' => 'Erreur lors de la création'];
        } catch (Exception $e) {
            error_log("Erreur createTransaction: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

/**
 * Valider une transaction
 */
if (!function_exists('validateTransaction')) {
    function validateTransaction($pdo, $transactionId, $adminId, $notes = null) {
        try {
            $transaction = getTransactionById($transactionId);
            if (!$transaction) {
                return ['success' => false, 'message' => 'Transaction introuvable'];
            }
            
            if ($transaction['status'] !== 'en_attente') {
                return ['success' => false, 'message' => 'Transaction déjà traitée'];
            }
            
            // Vérifier le solde pour les sorties
            if ($transaction['type'] === 'sortie') {
                $balance = getCurrentBalance();
                if ($balance < $transaction['amount']) {
                    return ['success' => false, 'message' => 'Solde insuffisant'];
                }
            }
            
            $receiptNumber = generateReceiptNumber();
            
            $stmt = $pdo->prepare("
                UPDATE transactions 
                SET status = 'validee', 
                    validated_by = ?,
                    validated_at = NOW(),
                    admin_comment = ?,
                    receipt_number = ?
                WHERE id = ?
            ");
            
            $success = $stmt->execute([$adminId, $notes, $receiptNumber, $transactionId]);
            
            if ($success) {
                // Notifier l'utilisateur
                createNotification(
                    $transaction['user_id'],
                    'Transaction validée',
                    "Votre transaction de " . formatAmount($transaction['amount']) . " a été validée.",
                    'success',
                    $transactionId
                );
                
                return ['success' => true, 'message' => 'Transaction validée'];
            }
            
            return ['success' => false, 'message' => 'Erreur lors de la validation'];
        } catch (Exception $e) {
            error_log("Erreur validateTransaction: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

/**
 * Rejeter une transaction
 */
if (!function_exists('rejectTransaction')) {
    function rejectTransaction($pdo, $transactionId, $adminId, $notes) {
        try {
            $transaction = getTransactionById($transactionId);
            if (!$transaction) {
                return ['success' => false, 'message' => 'Transaction introuvable'];
            }
            
            $stmt = $pdo->prepare("
                UPDATE transactions 
                SET status = 'rejetee', 
                    validated_by = ?,
                    validated_at = NOW(),
                    admin_comment = ?
                WHERE id = ?
            ");
            
            $success = $stmt->execute([$adminId, $notes, $transactionId]);
            
            if ($success) {
                createNotification(
                    $transaction['user_id'],
                    'Transaction refusée',
                    "Votre transaction a été refusée. Motif: " . $notes,
                    'warning',
                    $transactionId
                );
                
                return ['success' => true, 'message' => 'Transaction rejetée'];
            }
            
            return ['success' => false, 'message' => 'Erreur lors du rejet'];
        } catch (Exception $e) {
            error_log("Erreur rejectTransaction: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

/**
 * Compter les transactions en attente
 */
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
// FONCTIONS STATISTIQUES
// ========================================

/**
 * Obtenir les statistiques du tableau de bord
 */
if (!function_exists('getDashboardStats')) {
    function getDashboardStats($pdo) {
        try {
            $stats = [
                'current_balance' => getCurrentBalance(),
                'pending_count' => 0,
                'validated_today' => 0,
                'validated_today_amount' => 0,
                'active_users' => 0
            ];
            
            // Transactions en attente
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM transactions WHERE status = 'en_attente'");
            $result = $stmt->fetch();
            $stats['pending_count'] = $result['count'] ?? 0;
            
            // Validées aujourd'hui
            $stmt = $pdo->query("
                SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total 
                FROM transactions 
                WHERE status = 'validee' AND DATE(validated_at) = CURDATE()
            ");
            $result = $stmt->fetch();
            $stats['validated_today'] = $result['count'] ?? 0;
            $stats['validated_today_amount'] = $result['total'] ?? 0;
            
            // Utilisateurs actifs
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
            $result = $stmt->fetch();
            $stats['active_users'] = $result['count'] ?? 0;
            
            return $stats;
        } catch (Exception $e) {
            error_log("Erreur getDashboardStats: " . $e->getMessage());
            return [
                'current_balance' => 0,
                'pending_count' => 0,
                'validated_today' => 0,
                'validated_today_amount' => 0,
                'active_users' => 0
            ];
        }
    }
}

// ========================================
// FONCTIONS UTILISATEURS
// ========================================

/**
 * Obtenir tous les utilisateurs
 */
if (!function_exists('getAllUsers')) {
    function getAllUsers() {
        try {
            $db = getDB();
            $stmt = $db->query("
                SELECT u.*, 
                       r.name as role,
                       CASE WHEN u.role_id = 1 THEN 'admin' ELSE 'user' END as role_name 
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

/**
 * Obtenir un utilisateur par ID
 */
if (!function_exists('getUserById')) {
    function getUserById($id) {
        try {
            $db = getDB();
            $stmt = $db->prepare("
                SELECT u.*, r.name as role
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

/**
 * Créer un utilisateur
 */
if (!function_exists('createUser')) {
    function createUser($pdo, $email, $password, $fullName, $role) {
        try {
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Cet email est déjà utilisé'];
            }
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $roleId = ($role === 'admin') ? 1 : 2;
            
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, full_name, role_id, email_verified)
                VALUES (?, ?, ?, ?, ?, 1)
            ");
            
            $username = strtolower(str_replace(' ', '', $fullName));
            
            if ($stmt->execute([$username, $email, $hashedPassword, $fullName, $roleId])) {
                return ['success' => true, 'message' => 'Utilisateur créé avec succès'];
            }
            
            return ['success' => false, 'message' => 'Erreur lors de la création'];
        } catch (Exception $e) {
            error_log("Erreur createUser: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

/**
 * Supprimer un utilisateur
 */
if (!function_exists('deleteUser')) {
    function deleteUser($pdo, $userId) {
        try {
            // Ne pas supprimer le super admin
            if ($userId == 1) {
                return ['success' => false, 'message' => 'Impossible de supprimer le super admin'];
            }
            
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$userId])) {
                return ['success' => true, 'message' => 'Utilisateur supprimé'];
            }
            
            return ['success' => false, 'message' => 'Erreur lors de la suppression'];
        } catch (Exception $e) {
            error_log("Erreur deleteUser: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

/**
 * Compter les transactions d'un utilisateur
 */
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