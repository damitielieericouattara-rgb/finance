<?php
/**
 * CONFIGURATION PRINCIPALE DE L'APPLICATION
 * Configuration de la base de données et paramètres globaux
 */

// Empêcher l'accès direct
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
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
define('APP_URL', 'http://localhost/financial_app');

// Configuration des sessions
define('SESSION_LIFETIME', 1800); // 30 minutes
define('SESSION_NAME', 'FINANCIAL_APP_SESSION');

// Configuration des uploads
define('UPLOAD_DIR', APP_ROOT . '/uploads');
define('EXPORT_DIR', APP_ROOT . '/exports');
define('PDF_DIR', APP_ROOT . '/pdf');
define('MAX_UPLOAD_SIZE', 5242880); // 5MB

// Configuration des notifications
define('NOTIFICATION_POLL_INTERVAL', 30000); // 30 secondes en millisecondes
define('URGENT_TRANSACTION_REMINDER_INTERVAL', 600); // 10 minutes en secondes

// Configuration email (SMTP)
define('SMTP_ENABLED', false); // Activer/désactiver les emails
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'votre-email@gmail.com');
define('SMTP_PASS', 'votre-mot-de-passe');
define('SMTP_FROM_EMAIL', 'noreply@financialapp.com');
define('SMTP_FROM_NAME', 'Financial App');

// Configuration de sécurité
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LENGTH', 8);
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Timezone
date_default_timezone_set('Africa/Abidjan');

// Gestion des erreurs en développement
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
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
    die("Erreur de connexion à la base de données : " . $e->getMessage());
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
        header('Location: /login.php?timeout=1');
        exit;
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
        'email' => $_SESSION['email'],
        'full_name' => $_SESSION['full_name'],
        'role' => $_SESSION['role']
    ];
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
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Fonction pour logger les erreurs
function logError($message, $context = []) {
    $logFile = APP_ROOT . '/logs/error.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? json_encode($context) : '';
    $logMessage = "[$timestamp] $message $contextStr" . PHP_EOL;
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Charger les fonctions métier
require_once APP_ROOT . '/includes/functions.php';