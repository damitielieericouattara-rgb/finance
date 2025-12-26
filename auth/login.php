<?php
define('APP_ROOT', __DIR__);
define('APP_ENV', 'production');
require_once 'includes/config.php';

// Si déjà connecté, rediriger
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit;
}

$error = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Connexion réussie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                
                // Logger l'activité
                logActivity($pdo, $user['id'], 'login', 'Connexion réussie');
                
                // Rediriger selon le rôle
                if ($user['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: user/dashboard.php');
                }
                exit;
            } else {
                $error = 'Email ou mot de passe incorrect.';
                
                // Logger la tentative échouée
                if ($user) {
                    logActivity($pdo, $user['id'], 'login_failed', 'Tentative de connexion échouée');
                }
            }
        } catch (PDOException $e) {
            $error = 'Erreur de connexion. Veuillez réessayer.';
            logError('Login error: ' . $e->getMessage());
        }
    }
}

$timeout = isset($_GET['timeout']) ? true : false;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#3B82F6',
                        secondary: '#10B981',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen flex items-center justify-center transition-colors duration-300">
    <div class="max-w-md w-full mx-4">
        <!-- Toggle Thème -->
        <div class="flex justify-end mb-4">
            <button id="themeToggle" class="p-2 rounded-lg bg-white dark:bg-gray-800 shadow-lg hover:shadow-xl transition-all">
                <!-- Icône Soleil (mode clair) -->
                <svg class="h-6 w-6 text-gray-800 dark:text-yellow-400 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <!-- Icône Lune (mode sombre) -->
                <svg class="h-6 w-6 text-gray-800 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
            </button>
        </div>

        <!-- Card de connexion -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 transition-colors duration-300">
            <!-- Logo et titre -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-primary rounded-full mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Connexion</h1>
                <p class="text-gray-600 dark:text-gray-400"><?php echo APP_NAME; ?></p>
            </div>

            <?php if ($timeout): ?>
            <div class="mb-4 p-4 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-700 rounded-lg">
                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                    Votre session a expiré. Veuillez vous reconnecter.
                </p>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg">
                <p class="text-sm text-red-800 dark:text-red-200"><?php echo escape($error); ?></p>
            </div>
            <?php endif; ?>

            <!-- Formulaire -->
            <form method="POST" action="" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Email
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           required 
                           value="<?php echo escape($_POST['email'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-colors"
                           placeholder="votre.email@exemple.com">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Mot de passe
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required 
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-colors"
                           placeholder="••••••••">
                </div>

                <button type="submit" 
                        class="w-full bg-primary hover:bg-blue-600 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200 shadow-lg hover:shadow-xl">
                    Se connecter
                </button>
            </form>

            <!-- Comptes de test -->
            <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 dark:text-gray-400 text-center mb-3">Comptes de test :</p>
                <div class="space-y-2 text-xs text-gray-600 dark:text-gray-400">
                    <div class="flex justify-between items-center bg-gray-50 dark:bg-gray-700/50 p-2 rounded">
                        <span class="font-medium">Admin:</span>
                        <span class="font-mono">admin@financialapp.com / Admin@123</span>
                    </div>
                    <div class="flex justify-between items-center bg-gray-50 dark:bg-gray-700/50 p-2 rounded">
                        <span class="font-medium">User:</span>
                        <span class="font-mono">user1@financialapp.com / User@123</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Version <?php echo APP_VERSION; ?> • © <?php echo date('Y'); ?>
            </p>
        </div>
    </div>

    <!-- Script thème -->
    <script src="assets/js/theme.js"></script>
</body>
</html>