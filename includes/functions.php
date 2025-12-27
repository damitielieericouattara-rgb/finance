<?php
/**
 * FONCTIONS MÉTIER - VERSION COMPLÈTE
 * Toutes les fonctions nécessaires à l'application
 */

// ========================================
// FONCTIONS DE SESSION ET AUTHENTIFICATION
// ========================================

/**
 * Vérifier si l'utilisateur est connecté
 */
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

/**
 * Vérifier si l'utilisateur est admin
 */
if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}

/**
 * Obtenir l'utilisateur connecté
 */
if (!function_exists('getCurrentUser')) {
    function getCurrentUser() {
        if (!isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'email' => $_SESSION['email'] ?? '',
            'full_name' => $_SESSION['full_name'] ?? '',
            'role' => $_SESSION['role'] ?? 'user'
        ];
    }
}

/**
 * Obtenir la connexion PDO
 */
if (!function_exists('getDB')) {
    function getDB() {
        global $pdo;
        if (!isset($pdo)) {
            try {
                $pdo = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $e) {
                error_log("Erreur de connexion à la base de données : " . $e->getMessage());
                die("Erreur de connexion à la base de données.");
            }
        }
        return $pdo;
    }
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
function cleanInput($data) {
    if (is_null($data)) return '';
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Échapper pour affichage HTML
 */
function escape($value) {
    if (is_null($value)) return '';
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

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

/**
 * Vérifier si le solde est à zéro
 */
function isBalanceZero() {
    return getCurrentBalance() <= 0;
}

// ========================================
// FONCTIONS DE STATUT
// ========================================

/**
 * Obtenir le badge HTML d'un statut
 */
function getStatusBadge($status) {
    $badges = [
        'en_attente' => '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200">⏳ En attente</span>',
        'validee' => '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">✅ Validée</span>',
        'rejetee' => '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">❌ Rejetée</span>',
    ];
    
    return $badges[$status] ?? '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">' . htmlspecialchars($status) . '</span>';
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
 * Obtenir les notifications non lues
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

// ========================================
// FONCTIONS DE TRANSACTIONS
// ========================================

/**
 * Obtenir les transactions avec filtres
 */
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

/**
 * Obtenir une transaction par ID
 */
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

/**
 * Créer une transaction
 */
function createTransaction($pdo, $data) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO transactions (user_id, type, amount, reason, request_date, urgency)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $success = $stmt->execute([
            $data['user_id'],
            $data['type'],
            $data['amount'],
            $data['reason'],
            $data['request_date'],
            $data['urgency'] ?? 'normal'
        ]);
        
        if ($success) {
            $transactionId = $pdo->lastInsertId();
            
            // Notifier les admins
            $adminStmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' AND is_active = 1");
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

/**
 * Valider une transaction
 */
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

/**
 * Rejeter une transaction
 */
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

// ========================================
// FONCTIONS STATISTIQUES
// ========================================

/**
 * Obtenir les statistiques du tableau de bord
 */
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
            SELECT u.*, 
                   CASE WHEN u.role = 'admin' THEN 'admin' ELSE 'user' END as role_name 
            FROM users u 
            ORDER BY u.created_at DESC
        ");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Erreur getAllUsers: " . $e->getMessage());
        return [];
    }
}

/**
 * Logger les activités
 */
function logActivity($userId, $action, $tableName = null, $recordId = null, $details = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, action, table_name, record_id, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $action,
            $tableName,
            $recordId,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        error_log("Erreur logActivity: " . $e->getMessage());
    }
}

/**
 * Envoyer un email
 */
function sendEmail($to, $subject, $body, $isHtml = false) {
    if (!defined('SMTP_ENABLED') || !SMTP_ENABLED) {
        error_log("Email non envoyé (SMTP désactivé) - To: $to, Subject: $subject");
        return false;
    }
    
    $headers = [
        'From' => (defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Financial App') . ' <' . (defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@financialapp.com') . '>',
        'Reply-To' => defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@financialapp.com',
        'X-Mailer' => 'PHP/' . phpversion(),
    ];
    
    if ($isHtml) {
        $headers['MIME-Version'] = '1.0';
        $headers['Content-type'] = 'text/html; charset=UTF-8';
    }
    
    $headerStr = '';
    foreach ($headers as $key => $value) {
        $headerStr .= "$key: $value\r\n";
    }
    
    return mail($to, $subject, $body, $headerStr);
}

/**
 * Générer un token CSRF
 */
function generateCsrfToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Vérifier un token CSRF
 */
function verifyCsrfToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}