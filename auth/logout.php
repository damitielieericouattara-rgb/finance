<?php
define('APP_ROOT', __DIR__);
define('APP_ENV', 'production');
require_once 'includes/config.php';

// Logger la déconnexion si l'utilisateur est connecté
if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    logActivity($pdo, $userId, 'logout', 'Déconnexion');
}

// Détruire la session
session_unset();
session_destroy();

// Supprimer le cookie de session
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Rediriger vers la page de connexion
header('Location: login.php');
exit;