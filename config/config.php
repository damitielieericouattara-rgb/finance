<?php
/**
 * CONFIGURATION PRINCIPALE - VERSION CORRIGÉE FINALE
 */

// Empêcher l'accès direct
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

if (!defined('APP_ENV')) {
    define('APP_ENV', 'production');
}

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'financial_management');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configuration de l'application
define('APP_NAME', 'Système de Gestion Financière');
define('APP_VERSION', '2.0.0');
define('APP_URL', 'http://localhost/finance');
define('SITE_NAME', 'Gestion Financière');
define('SITE_URL', 'http://localhost/finance');

// Configuration des chemins
define('ROOT_PATH', APP_ROOT);
define('UPLOAD_DIR', APP_ROOT . '/uploads');
define('EXPORT_DIR', APP_ROOT . '/exports');
define('EXPORT_PATH', APP_ROOT . '/exports');
define('PDF_DIR', APP_ROOT . '/pdf');
define('MAX_UPLOAD_SIZE', 5242880);

// Configuration des sessions
define('SESSION_LIFETIME', 1800);
define('SESSION_TIMEOUT', 1800);
define('SESSION_NAME', 'FINANCIAL_APP_SESSION');

// Configuration de sécurité
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LENGTH', 8);
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900);

// Configuration email
define('SMTP_ENABLED', false);
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'votre-email@gmail.com');
define('SMTP_PASS', 'votre-mot-de-passe');
define('SMTP_FROM_EMAIL', 'noreply@financialapp.com');
define('SMTP_FROM_NAME', 'Financial App');

// Configuration OTP
define('OTP_EXPIRY', 600);

// Configuration réinitialisation mot de passe
define('RESET_TOKEN_EXPIRY', 3600);
define('REMEMBER_ME_EXPIRY', 2592000);

// Configuration notifications
define('NOTIFICATION_POLL_INTERVAL', 30000);
define('URGENT_NOTIFICATION_INTERVAL', 600);
define('MAX_URGENT_NOTIFICATIONS', 5);

// Configuration Twilio
define('TWILIO_ACCOUNT_SID', 'votre_account_sid');
define('TWILIO_AUTH_TOKEN', 'votre_auth_token');
define('TWILIO_PHONE_NUMBER', '+1234567890');

// Configuration code admin
define('ADMIN_CODE', 'ADMIN2025');

// Timezone
date_default_timezone_set('Africa/Abidjan');

// Gestion des erreurs
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
}
ini_set('log_errors', 1);
ini_set('error_log', APP_ROOT . '/logs/php_errors.log');

// Créer le dossier logs
$logDir = APP_ROOT . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}

// Connexion à la base de données
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
    die("Erreur de connexion à la base de données. Veuillez vérifier votre configuration.");
}

/**
 * Fonction pour obtenir la connexion PDO
 */
function getDB() {
    global $pdo;
    return $pdo;
}

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
    
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
        session_unset();
        session_destroy();
        if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
            header('Location: ' . APP_URL . '/auth/login.php?timeout=1');
            exit;
        }
    }
    $_SESSION['last_activity'] = time();
}

/**
 * Vérifier si l'utilisateur est connecté
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Vérifier si l'utilisateur est admin
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Obtenir l'utilisateur connecté
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['email'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
        'role' => $_SESSION['role'] ?? 'user'
    ];
}

/**
 * Vérifier le timeout de session
 */
function checkSessionTimeout() {
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
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

/**
 * Protéger contre XSS
 */
function escape($value) {
    if (is_null($value)) {
        return '';
    }
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Nettoyer les entrées
 */
function cleanInput($data) {
    if (is_null($data)) {
        return '';
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Logger les erreurs
 */
function logError($message, $context = []) {
    $logFile = APP_ROOT . '/logs/error.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? json_encode($context) : '';
    $logMessage = "[$timestamp] $message $contextStr" . PHP_EOL;
    
    @file_put_contents($logFile, $logMessage, FILE_APPEND);
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
        logError("Erreur logActivity: " . $e->getMessage());
    }
}

/**
 * Envoyer un email
 */
function sendEmail($to, $subject, $body, $isHtml = false) {
    if (!SMTP_ENABLED) {
        error_log("Email non envoyé (SMTP désactivé) - To: $to, Subject: $subject");
        return false;
    }
    
    $headers = [
        'From' => SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>',
        'Reply-To' => SMTP_FROM_EMAIL,
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
 * Protections d'accès
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/auth/login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . APP_URL . '/user/dashboard.php');
        exit;
    }
}

/**
 * Formater un montant
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
 * Générer un numéro de reçu
 */
function generateReceiptNumber() {
    return 'REC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
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
        error_log("Erreur getCurrentBalance: " . $e->getMessage());
        return 0;
    }
}

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
 * Obtenir le badge HTML d'un statut
 */
function getStatusBadge($status) {
    $badges = [
        'en_attente' => '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200">⏳ En attente</span>',
        'validee' => '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">✅ Validée</span>',
        'refusee' => '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">❌ Refusée</span>',
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

// Charger les fonctions métier supplémentaires
$functionsFile = APP_ROOT . '/includes/functions.php';
if (file_exists($functionsFile)) {
    require_once $functionsFile;
}