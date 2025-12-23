<?php
require_once '../config/config.php';

if (isLoggedIn()) {
    logActivity($_SESSION['user_id'], 'LOGOUT', 'users', $_SESSION['user_id'], 'Déconnexion');
}

session_unset();
session_destroy();

header('Location: ../index.php');
exit();
?>