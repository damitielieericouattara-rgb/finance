<?php
/**
 * CONFIGURATION PRINCIPALE DE L'APPLICATION
 * Configuration de la base de données et paramètres globaux
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
define('MAX_UPLOAD_SIZE', 5242880); // 5MB

// Configuration des sessions
define('SESSION_LIFETIME', 1800); // 30 minutes
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('SESSION_NAME', 'FINANCIAL_APP_SESSION');

// Configuration de sécurité
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LENGTH', 8);
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Configuration email
define('SMTP_ENABLED', false);
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'votre-email@gmail.com');
define('SMTP_PASS', 'votre-mot-de-passe');
define('SMTP_FROM_EMAIL', 'noreply@financialapp.com');
define('SMTP_FROM_NAME', 'Financial App');

// Configuration OTP
define('OTP_EXPIRY', 600); // 10 minutes

// Configuration réinitialisation mot de passe
define('RESET_TOKEN_EXPIRY', 3600); // 1 heure

// Configuration notifications
define('NOTIFICATION_POLL_INTERVAL', 30000); // 30 secondes
define('URGENT_NOTIFICATION_INTERVAL', 600); // 10 minutes
define('MAX_URGENT_NOTIFICATIONS', 5);

// Configuration Twilio (pour SMS/WhatsApp)
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
    ini_set('log_errors', 1);
    ini_set('error_log', APP_ROOT . '/logs/php_errors.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', APP_ROOT . '/logs/php_errors.log');
}

// Créer le dossier logs si nécessaire
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

// Fonction pour obtenir la connexion PDO (pour compatibilité)
function getDB() {
    global $pdo;
    return $pdo;
}

// Démarrer la session si pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
    
    // Régénérer l'ID de session périodiquement pour la sécurité
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    // Vérifier le timeout de session
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

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Fonction pour vérifier si l'utilisateur est admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Fonction pour obtenir l'utilisateur connecté
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

// Fonction pour vérifier le timeout de session
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

// Fonction pour générer un token CSRF
function generateCsrfToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

// Fonction pour vérifier un token CSRF
function verifyCsrfToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Fonction pour protéger contre XSS
function escape($value) {
    if (is_null($value)) {
        return '';
    }
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Fonction pour nettoyer les entrées
function cleanInput($data) {
    if (is_null($data)) {
        return '';
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Fonction pour logger les erreurs
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

// Fonction pour logger les activités
function logActivity($userId, $action, $tableName = null, $recordId = null, $details = null) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
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

// Fonction pour envoyer un email
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

// Protection contre les attaques
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

// Charger les fonctions métier
$functionsFile = APP_ROOT . '/includes/functions.php';
if (file_exists($functionsFile)) {
    require_once $functionsFile;
} else {
    // Essayer un chemin alternatif
    $altPath = dirname(__FILE__) . '/../includes/functions.php';
    if (file_exists($altPath)) {
        require_once $altPath;
    } else {
        error_log("ERREUR CRITIQUE: Le fichier functions.php n'existe pas!");
        error_log("Chemin recherché 1: " . $functionsFile);
        error_log("Chemin recherché 2: " . $altPath);
        die("Erreur de configuration: Fichier functions.php introuvable.");
    }
}