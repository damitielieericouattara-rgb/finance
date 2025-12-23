<?php
// ========================================
// CONFIGURATION GÉNÉRALE
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

// Constantes de l'application
define('SITE_NAME', 'FinanceFlow');
define('SITE_URL', 'http://localhost/financial-management');
define('SESSION_TIMEOUT', 1800); // 30 minutes d'inactivité

// Chemins
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads/');
define('PDF_PATH', ROOT_PATH . '/pdf/generated/');
define('EXPORT_PATH', ROOT_PATH . '/exports/');

// Email configuration (à configurer selon votre serveur)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'noreply@financeflow.com');
define('SMTP_PASS', 'your_password');
define('FROM_EMAIL', 'noreply@financeflow.com');
define('FROM_NAME', 'FinanceFlow');

// Paramètres de notification urgente
define('URGENT_NOTIFICATION_INTERVAL', 300); // 5 minutes en secondes (300)
define('MAX_URGENT_NOTIFICATIONS', 20); // Nombre maximum de notifications répétées

// Paramètres de sécurité
define('PASSWORD_MIN_LENGTH', 8);
define('RESET_TOKEN_EXPIRY', 3600); // 1 heure en secondes
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Créer les dossiers nécessaires s'ils n'existent pas
$directories = [UPLOAD_PATH, PDF_PATH, EXPORT_PATH];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Charger la connexion à la base de données
require_once ROOT_PATH . '/config/database.php';

// Fonction pour obtenir la connexion DB
function getDB() {
    $database = new Database();
    return $database->getConnection();
}

// Vérifier la session et le timeout
function checkSessionTimeout() {
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        return false;
    }
    $_SESSION['LAST_ACTIVITY'] = time();
    return true;
}

// Vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']) && checkSessionTimeout();
}

// Vérifier si l'utilisateur est admin
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Rediriger si non connecté
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/auth/login.php');
        exit();
    }
}

// Rediriger si non admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/user/dashboard.php');
        exit();
    }
}

// Protection CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Fonction de sécurité pour nettoyer les entrées
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Fonction pour logger les activités
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

// Fonction pour envoyer des emails (structure de base)
function sendEmail($to, $subject, $body, $isHTML = true) {
    // Configuration basique - à adapter selon votre système d'envoi
    $headers = "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . FROM_EMAIL . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    
    if ($isHTML) {
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    }
    
    return mail($to, $subject, $body, $headers);
}

// Fonction pour formater les montants
function formatAmount($amount) {
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

// Fonction pour formater les dates
function formatDate($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}

// Fonction pour formater les dates et heures
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    return date($format, strtotime($datetime));
}

// Générer un code de reçu unique
function generateReceiptNumber() {
    return 'REC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

// Messages de succès/erreur
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, // success, error, warning, info
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

// Fonction pour obtenir les couleurs de statut
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
?>