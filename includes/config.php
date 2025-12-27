<?php
/**
 * FICHIER DE REDIRECTION
 * Ce fichier redirige vers la vraie configuration dans config/config.php
 * pour éviter les erreurs de chemin
 */

// Empêcher l'accès direct
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

if (!defined('APP_ENV')) {
    define('APP_ENV', 'production');
}

// Inclure la vraie configuration
require_once APP_ROOT . '/config/config.php';

// Inclure les fonctions si pas déjà fait
if (!function_exists('getDB')) {
    require_once APP_ROOT . '/includes/functions.php';
}