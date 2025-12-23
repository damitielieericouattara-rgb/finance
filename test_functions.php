<?php
/**
 * SCRIPT DE TEST - À placer à la racine du projet
 * Accéder via : http://localhost/finance/test_functions.php
 */

require_once 'config/config.php';

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Test des fonctions</title>
    <style>
        body { font-family: Arial; margin: 40px; background: #f5f5f5; }
        .test { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        h1 { color: #059669; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>🧪 Test des fonctions - Gestion Financière</h1>
";

// Test 1 : Connexion à la base de données
echo "<div class='test'>";
echo "<h2>✅ Test 1 : Connexion à la base de données</h2>";
try {
    $db = getDB();
    echo "<p class='success'>✓ Connexion réussie</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Erreur : " . $e->getMessage() . "</p>";
    exit();
}
echo "</div>";

// Test 2 : Fonction createNotification
echo "<div class='test'>";
echo "<h2>✅ Test 2 : Fonction createNotification()</h2>";
if (function_exists('createNotification')) {
    echo "<p class='success'>✓ Fonction createNotification existe</p>";
    
    // Test d'utilisation
    try {
        $testResult = createNotification(
            1, // user_id admin
            'Test notification',
            'Ceci est un test',
            'info',
            null
        );
        if ($testResult) {
            echo "<p class='success'>✓ Notification de test créée avec succès</p>";
        } else {
            echo "<p class='error'>✗ Erreur lors de la création</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Erreur : " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='error'>✗ Fonction createNotification n'existe pas</p>";
}
echo "</div>";

// Test 3 : Autres fonctions importantes
echo "<div class='test'>";
echo "<h2>✅ Test 3 : Fonctions essentielles</h2>";

$functions = [
    'getUnreadNotifications',
    'countUnreadNotifications',
    'getCurrentBalance',
    'getDashboardStats',
    'getTransactions',
    'createTransaction',
    'validateTransaction',
    'rejectTransaction',
    'getAllUsers',
    'createUser'
];

foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "<p class='success'>✓ $func()</p>";
    } else {
        echo "<p class='error'>✗ $func() manquante</p>";
    }
}
echo "</div>";

// Test 4 : Vérifier la structure de la base
echo "<div class='test'>";
echo "<h2>✅ Test 4 : Structure de la base de données</h2>";
try {
    $tables = ['users', 'roles', 'transactions', 'notifications', 'global_balance', 'activity_logs'];
    
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✓ Table $table existe</p>";
        } else {
            echo "<p class='error'>✗ Table $table manquante</p>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Erreur : " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 5 : Comptes de test
echo "<div class='test'>";
echo "<h2>✅ Test 5 : Comptes de test</h2>";
try {
    $stmt = $db->query("SELECT id, email, full_name, role_id FROM users WHERE email IN ('admin@financialapp.com', 'user1@financialapp.com')");
    $users = $stmt->fetchAll();
    
    if (count($users) >= 2) {
        echo "<p class='success'>✓ Comptes de test présents (" . count($users) . ")</p>";
        foreach ($users as $user) {
            echo "<p>• {$user['full_name']} ({$user['email']}) - Rôle ID: {$user['role_id']}</p>";
        }
    } else {
        echo "<p class='error'>✗ Comptes de test manquants</p>";
    }
    
    // Test des mots de passe
    echo "<h3>Test des mots de passe :</h3>";
    foreach ($users as $user) {
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $result = $stmt->fetch();
        
        $testPassword = $user['role_id'] == 1 ? 'Admin@123' : 'User@123';
        
        if (password_verify($testPassword, $result['password'])) {
            echo "<p class='success'>✓ {$user['email']} : Mot de passe OK</p>";
        } else {
            echo "<p class='error'>✗ {$user['email']} : Mot de passe incorrect</p>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Erreur : " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 6 : Solde initial
echo "<div class='test'>";
echo "<h2>✅ Test 6 : Solde global</h2>";
try {
    $balance = getCurrentBalance();
    echo "<p class='success'>✓ Solde actuel : " . formatAmount($balance) . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Erreur : " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 7 : Configuration
echo "<div class='test'>";
echo "<h2>✅ Test 7 : Configuration</h2>";
echo "<pre>";
echo "SITE_NAME : " . SITE_NAME . "\n";
echo "SITE_URL : " . SITE_URL . "\n";
echo "ROOT_PATH : " . ROOT_PATH . "\n";
echo "SESSION_TIMEOUT : " . SESSION_TIMEOUT . " secondes\n";
echo "PASSWORD_MIN_LENGTH : " . PASSWORD_MIN_LENGTH . "\n";
echo "</pre>";
echo "</div>";

echo "
<div class='test' style='background: #d1fae5; border: 2px solid #059669;'>
    <h2 style='color: #059669;'>🎉 Tests terminés !</h2>
    <p>Si tous les tests sont verts, l'application est prête.</p>
    <p><strong>Prochaine étape :</strong> <a href='auth/login.php'>Se connecter</a></p>
</div>
</body>
</html>
";