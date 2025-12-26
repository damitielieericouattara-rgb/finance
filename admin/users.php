<?php
define('APP_ROOT', dirname(__DIR__));
define('APP_ENV', 'production');
require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$user = getCurrentUser();
$message = '';
$messageType = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $email = trim($_POST['email'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';
        
        if (empty($email) || empty($fullName) || empty($password)) {
            $message = 'Tous les champs sont requis.';
            $messageType = 'error';
        } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
            $message = 'Le mot de passe doit contenir au moins ' . PASSWORD_MIN_LENGTH . ' caractères.';
            $messageType = 'error';
        } else {
            $result = createUser($pdo, $email, $password, $fullName, $role);
            if ($result['success']) {
                $message = 'Utilisateur créé avec succès.';
                $messageType = 'success';
            } else {
                $message = $result['message'];
                $messageType = 'error';
            }
        }
    } elseif ($action === 'toggle_status') {
        $userId = $_POST['user_id'] ?? null;
        if ($userId) {
            $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$userId]);
            $message = 'Statut mis à jour.';
            $messageType = 'success';
        }
    } elseif ($action === 'delete') {
        $userId = $_POST['user_id'] ?? null;
        if ($userId && $userId != $user['id']) {
            $result = deleteUser($pdo, $userId);
            if ($result['success']) {
                $message = 'Utilisateur supprimé.';
                $messageType = 'success';
            } else {
                $message = $result['message'];
                $messageType = 'error';
            }
        }
    }
}

$users = getAllUsers($pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - <?php echo APP_NAME; ?></title>
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
<body class="bg-gray-100 dark:bg-gray-900 transition-colors duration-300">
    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-lg transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <svg class="h-8 w-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="ml-2 text-xl font-bold text-gray-900 dark:text-white">Admin</span>
                    </div>
                    <div class="hidden md:ml-6 md:flex md:space-x-4">
                        <a href="dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            Tableau de bord
                        </a>
                        <a href="transactions.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            Transactions
                        </a>
                        <a href="balance.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            Solde Global
                        </a>
                        <a href="users.php" class="px-3 py-2 rounded-md text-sm font-medium bg-primary text-white">
                            Utilisateurs
                        </a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <a href="notifications.php" class="relative p-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span data-notification-badge class="hidden absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full h-5 w-5 items-center justify-center">0</span>
                        </a>
                    </div>
                    
                    <button id="themeToggle" class="p-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                        <svg class="h-6 w-6 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <svg class="h-6 w-6 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </button>
                    
                    <div class="flex items-center">
                        <span class="text-sm text-gray-700 dark:text-gray-300 mr-2"><?php echo escape($user['full_name']); ?></span>
                        <a href="../logout.php" class="text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">
                            Déconnexion
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700' : 'bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700'; ?>">
            <p class="text-sm <?php echo $messageType === 'success' ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'; ?>">
                <?php echo escape($message); ?>
            </p>
        </div>
        <?php endif; ?>

        <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Gestion des Utilisateurs</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    <?php echo count($users); ?> utilisateur(s) au total
                </p>
            </div>
            <button onclick="openCreateModal()" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-600 shadow-lg">
                <svg class="inline-block h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nouvel utilisateur
            </button>
        </div>

        <!-- Liste des utilisateurs -->
        <div class="px-4 mt-6">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Nom complet</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Rôle</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Créé le</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                <?php echo escape($u['full_name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                <?php echo escape($u['email']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $u['role'] === 'admin' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'; ?>">
                                    <?php echo ucfirst($u['role']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $u['is_active'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'; ?>">
                                    <?php echo $u['is_active'] ? 'Actif' : 'Inactif'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                <?php echo formatDate($u['created_at']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm space-x-3">
                                <?php if ($u['id'] != $user['id']): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <button type="submit" class="text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300">
                                        <?php echo $u['is_active'] ? 'Désactiver' : 'Activer'; ?>
                                    </button>
                                </form>
                                <button onclick="confirmDelete(<?php echo $u['id']; ?>, '<?php echo escape($u['full_name']); ?>')" 
                                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                    Supprimer
                                </button>
                                <?php else: ?>
                                <span class="text-gray-400 dark:text-gray-600">Vous</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal création -->
    <div id="createModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Nouvel utilisateur</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nom complet</label>
                        <input type="text" name="full_name" required 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                        <input type="email" name="email" required 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Mot de passe</label>
                        <input type="password" name="password" required minlength="8"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rôle</label>
                        <select name="role" 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="user">Utilisateur</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeCreateModal()" 
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        Annuler
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-600">
                        Créer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal suppression -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Confirmer la suppression</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                Êtes-vous sûr de vouloir supprimer l'utilisateur <strong id="deleteUserName"></strong> ?
            </p>
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" id="deleteUserId">
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeDeleteModal()" 
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        Annuler
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Supprimer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/theme.js"></script>
    <script src="../assets/js/notifications_system.js"></script>
    <script>
        if (window.NotificationManager) {
            window.NotificationManager.init(<?php echo $user['id']; ?>);
        }

        function openCreateModal() {
            document.getElementById('createModal').classList.remove('hidden');
        }

        function closeCreateModal() {
            document.getElementById('createModal').classList.add('hidden');
        }

        function confirmDelete(userId, userName) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUserName').textContent = userName;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeCreateModal();
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>