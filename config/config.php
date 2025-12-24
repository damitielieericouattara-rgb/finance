<?php
// ========================================
// CONFIGURATION GÉNÉRALE - VERSION CORRIGÉE
// ========================================

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Configuration du fuseau horaire
date_default_timezone_set('Africa/Abidjan');

// Configuration des erreurs (désactiver en production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ========================================
// CONSTANTES DE L'APPLICATION
// ========================================
define('SITE_NAME', 'Gestion Financière');
define('SITE_URL', 'http://localhost/finance');
define('SESSION_TIMEOUT', 1800); // 30 minutes d'inactivité

// ========================================
// CODE ADMINISTRATEUR FIXE
// ========================================
define('ADMIN_CODE', 'Administration'); // Code fixe pour inscription admin

// ========================================
// CHEMINS
// ========================================
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads/');
define('PDF_PATH', ROOT_PATH . '/pdf/generated/');
define('EXPORT_PATH', ROOT_PATH . '/exports/');

// ========================================
// EMAIL CONFIGURATION
// ========================================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'noreply@financeflow.com');
define('SMTP_PASS', 'your_password');
define('FROM_EMAIL', 'noreply@financeflow.com');
define('FROM_NAME', 'Gestion Financière');

// ========================================
// SMS & WHATSAPP CONFIGURATION
// ========================================
define('TWILIO_ACCOUNT_SID', 'your_account_sid');
define('TWILIO_AUTH_TOKEN', 'your_auth_token');
define('TWILIO_PHONE_NUMBER', '+1234567890');
define('WHATSAPP_API_URL', 'https://api.whatsapp.com/send');
define('WHATSAPP_API_TOKEN', 'your_whatsapp_token');

// ========================================
// PARAMÈTRES DE NOTIFICATION URGENTE
// ========================================
define('URGENT_NOTIFICATION_INTERVAL', 600); // 10 minutes
define('MAX_URGENT_NOTIFICATIONS', 50);

// ========================================
// PARAMÈTRES DE SÉCURITÉ
// ========================================
define('PASSWORD_MIN_LENGTH', 8);
define('OTP_EXPIRY', 600); // 10 minutes
define('RESET_TOKEN_EXPIRY', 3600); // 1 heure
define('REMEMBER_ME_EXPIRY', 2592000); // 30 jours
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// ========================================
// CRÉATION DES DOSSIERS NÉCESSAIRES
// ========================================
$directories = [UPLOAD_PATH, PDF_PATH, EXPORT_PATH];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// ========================================
// CHARGER LA CONNEXION DB
// ========================================
require_once ROOT_PATH . '/config/database.php';

// ========================================
// FONCTION POUR OBTENIR LA CONNEXION DB
// ========================================
function getDB() {
    $database = new Database();
    return $database->getConnection();
}

// ========================================
// GESTION DE SESSION
// ========================================

function checkSessionTimeout() {
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        return false;
    }
    $_SESSION['LAST_ACTIVITY'] = time();
    return true;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && checkSessionTimeout();
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/auth/login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/user/dashboard.php');
        exit();
    }
}

// ========================================
// PROTECTION CSRF
// ========================================

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ========================================
// FONCTIONS DE SÉCURITÉ
// ========================================

function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

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
        error_log("Erreur log activité: " . $e->getMessage());
    }
}

// ========================================
// FONCTIONS D'ENVOI EMAIL - CORRIGÉE
// ========================================

/**
 * Envoyer un email (compatible localhost)
 */
function sendEmail($to, $subject, $body, $isHTML = true) {
    // Détecter l'environnement de développement
    $isLocalhost = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']) 
                   || in_array($_SERVER['SERVER_ADDR'] ?? '', ['localhost', '127.0.0.1']);
    
    if ($isLocalhost) {
        // MODE DÉVELOPPEMENT : Logger l'email au lieu de l'envoyer
        error_log("========== EMAIL SIMULÉ ==========");
        error_log("Destinataire: $to");
        error_log("Sujet: $subject");
        error_log("Corps: " . strip_tags($body));
        error_log("==================================");
        
        // Retourner true pour simuler un envoi réussi
        return true;
    }
    
    // MODE PRODUCTION : Utiliser la vraie fonction mail()
    $headers = "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . FROM_EMAIL . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    
    if ($isHTML) {
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    }
    
    return mail($to, $subject, $body, $headers);
}

// ========================================
// FONCTIONS DE FORMATAGE
// ========================================

function formatAmount($amount) {
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

function formatDate($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}

function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    return date($format, strtotime($datetime));
}

function generateReceiptNumber() {
    return 'REC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

// ========================================
// MESSAGES FLASH
// ========================================

function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

// ========================================
// FONCTIONS DE LABELS
// ========================================

function getStatusColor($status) {
    $colors = [
        'en_attente' => 'yellow',
        'validee' => 'green',
        'refusee' => 'red'
    ];
    return $colors[$status] ?? 'gray';
}

function getStatusLabel($status) {
    $labels = [
        'en_attente' => 'En attente',
        'validee' => 'Validée',
        'refusee' => 'Refusée'
    ];
    return $labels[$status] ?? $status;
}

function getTypeLabel($type) {
    return $type === 'entree' ? 'Entrée' : 'Sortie';
}

function getUrgencyLabel($urgency) {
    return $urgency === 'urgent' ? 'Urgent' : 'Normal';
}

// ========================================
// CHARGER LES FONCTIONS MÉTIER
// ========================================
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/otp.php';
require_once ROOT_PATH . '/includes/notifications_service.php';