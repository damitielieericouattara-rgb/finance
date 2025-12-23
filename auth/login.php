<?php
require_once '../config/config.php';

// Si déjà connecté, rediriger
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../user/dashboard.php');
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = cleanInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $selectedRole = cleanInput($_POST['role'] ?? '');
    
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } elseif (empty($selectedRole)) {
        $error = 'Veuillez sélectionner votre rôle';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("
                SELECT u.*, r.name as role_name 
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id 
                WHERE u.email = ? AND u.is_active = 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Vérifier que le rôle sélectionné correspond au compte
                if ($user['role_name'] !== $selectedRole) {
                    $error = 'Le rôle sélectionné ne correspond pas à votre compte';
                    logActivity(null, 'LOGIN_FAILED', 'users', null, "Tentative avec mauvais rôle pour: $email");
                } else {
                    // Connexion réussie
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role_name'];
                    $_SESSION['role_id'] = $user['role_id'];
                    $_SESSION['LAST_ACTIVITY'] = time();
                    
                    // Mettre à jour la dernière connexion
                    $updateStmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $updateStmt->execute([$user['id']]);
                    
                    // Logger l'activité
                    logActivity($user['id'], 'LOGIN', 'users', $user['id'], 'Connexion réussie en tant que ' . $user['role_name']);
                    
                    // Rediriger selon le rôle
                    if ($user['role_name'] === 'admin') {
                        header('Location: ../admin/dashboard.php');
                    } else {
                        header('Location: ../user/dashboard.php');
                    }
                    exit();
                }
            } else {
                $error = 'Email ou mot de passe incorrect';
                logActivity(null, 'LOGIN_FAILED', 'users', null, "Tentative échouée pour: $email");
            }
        } catch (Exception $e) {
            error_log("Erreur login: " . $e->getMessage());
            $error = 'Une erreur est survenue. Veuillez réessayer.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-green-50 to-green-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <!-- Logo et titre -->
        <div class="text-center mb-8">
            <div class="flex justify-center mb-4">
                <svg class="h-16 w-16 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900">Connexion</h1>
            <p class="text-gray-600 mt-2">Accédez à votre espace Gestion Financière</p>
        </div>

        <!-- Formulaire de connexion -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                    <div class="flex">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <p class="ml-3 text-sm text-red-700"><?php echo $error; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="space-y-6">
                    <!-- Sélection du rôle -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            Je me connecte en tant que *
                        </label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="role" value="admin" required
                                       class="peer sr-only"
                                       <?php echo (($_POST['role'] ?? '') === 'admin') ? 'checked' : ''; ?>>
                                <div class="border-2 border-gray-300 rounded-lg p-4 text-center peer-checked:border-purple-500 peer-checked:bg-purple-50 transition">
                                    <svg class="mx-auto h-8 w-8 text-purple-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                    <span class="font-semibold">Administrateur</span>
                                </div>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" name="role" value="user" required
                                       class="peer sr-only"
                                       <?php echo (($_POST['role'] ?? '') === 'user') ? 'checked' : ''; ?>>
                                <div class="border-2 border-gray-300 rounded-lg p-4 text-center peer-checked:border-green-500 peer-checked:bg-green-50 transition">
                                    <svg class="mx-auto h-8 w-8 text-green-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <span class="font-semibold">Utilisateur</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Adresse email
                        </label>
                        <input type="email" id="email" name="email" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                               placeholder="votre@email.com"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Mot de passe
                        </label>
                        <input type="password" id="password" name="password" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                               placeholder="••••••••">
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input type="checkbox" id="remember" name="remember"
                                   class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                            <label for="remember" class="ml-2 block text-sm text-gray-700">
                                Se souvenir de moi
                            </label>
                        </div>
                        <a href="forgot-password.php" class="text-sm text-green-600 hover:text-green-700 font-medium">
                            Mot de passe oublié ?
                        </a>
                    </div>

                    <button type="submit"
                            class="w-full bg-gradient-to-r from-green-600 to-green-700 text-white py-3 px-4 rounded-lg font-semibold hover:from-green-700 hover:to-green-800 focus:ring-4 focus:ring-green-300 transition transform hover:scale-105">
                        Se connecter
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Pas encore de compte ?
                    <a href="register.php" class="text-green-600 hover:text-green-700 font-semibold">
                        Créer un compte
                    </a>
                </p>
            </div>

            <!-- Comptes de test -->
            <!-- <div class="mt-6 pt-6 border-t border-gray-200">
                <p class="text-xs text-gray-500 text-center mb-3">Comptes de test disponibles :</p>
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="bg-purple-50 p-3 rounded-lg border border-purple-200">
                        <p class="font-semibold text-purple-700 mb-1">👑 Admin</p>
                        <p class="text-gray-600">admin@financialapp.com</p>
                        <p class="text-gray-600">Admin@123</p>
                        <p class="text-purple-600 text-[10px] mt-1">Rôle: Administrateur</p>
                    </div>
                    <div class="bg-green-50 p-3 rounded-lg border border-green-200">
                        <p class="font-semibold text-green-700 mb-1">👤 User</p>
                        <p class="text-gray-600">user1@financialapp.com</p>
                        <p class="text-gray-600">User@123</p>
                        <p class="text-green-600 text-[10px] mt-1">Rôle: Utilisateur</p>
                    </div>
                </div>
            </div> -->
        </div>

        <div class="text-center mt-6">
            <a href="../index.php" class="text-sm text-gray-600 hover:text-green-600 transition">
                ← Retour à l'accueil
            </a>
        </div>
    </div>
</body>
</html>