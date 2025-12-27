<?php
// Définir les constantes
define('APP_ROOT', dirname(__DIR__));
define('APP_ENV', 'production');

// Inclure la configuration
require_once APP_ROOT . '/config/config.php';

// Logger la déconnexion si l'utilisateur est connecté
if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    logActivity($userId, 'LOGOUT', 'users', $userId, 'Déconnexion');
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