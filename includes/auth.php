<?php
// ========================================
// FONCTIONS D'AUTHENTIFICATION
// ========================================

/**
 * Vérifier les tentatives de connexion
 */
function checkLoginAttempts($email) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT COUNT(*) as attempts 
        FROM activity_logs 
        WHERE action = 'LOGIN_FAILED' 
        AND details LIKE ? 
        AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
    ");
    $stmt->execute(["%$email%", LOGIN_LOCKOUT_TIME]);
    $result = $stmt->fetch();
    
    return $result['attempts'] < MAX_LOGIN_ATTEMPTS;
}

/**
 * Valider le format d'email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valider la force du mot de passe
 */
function validatePassword($password) {
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        return false;
    }
    
    // Au moins une majuscule, une minuscule et un chiffre
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    
    return true;
}

/**
 * Générer un token de réinitialisation
 */
function generateResetToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Vérifier un token de réinitialisation
 */
function verifyResetToken($token) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT id, username, full_name, email 
        FROM users 
        WHERE reset_token = ? 
        AND reset_token_expires > NOW() 
        AND is_active = 1
    ");
    $stmt->execute([$token]);
    return $stmt->fetch();
}

/**
 * Nettoyer les anciens tokens expirés
 */
function cleanupExpiredTokens() {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            UPDATE users 
            SET reset_token = NULL, reset_token_expires = NULL 
            WHERE reset_token_expires < NOW()
        ");
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Erreur nettoyage tokens: " . $e->getMessage());
    }
}

/**
 * Créer une session sécurisée
 */
function createSecureSession($userId, $username, $email, $fullName, $role, $roleId) {
    // Régénérer l'ID de session pour éviter la fixation
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['full_name'] = $fullName;
    $_SESSION['role'] = $role;
    $_SESSION['role_id'] = $roleId;
    $_SESSION['LAST_ACTIVITY'] = time();
    $_SESSION['CREATED_AT'] = time();
    $_SESSION['USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $_SESSION['IP_ADDRESS'] = $_SERVER['REMOTE_ADDR'] ?? '';
}

/**
 * Détruire une session en toute sécurité
 */
function destroySecureSession() {
    $_SESSION = [];
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
}

/**
 * Vérifier la validité de la session
 */
function validateSession() {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Vérifier le user agent
    if (isset($_SESSION['USER_AGENT']) && $_SESSION['USER_AGENT'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
        destroySecureSession();
        return false;
    }
    
    // Vérifier le timeout
    if (!checkSessionTimeout()) {
        destroySecureSession();
        return false;
    }
    
    return true;
}