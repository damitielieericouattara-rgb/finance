<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireAdmin();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
            
            if ($action === 'create') {
                $username = cleanInput($_POST['username']);
                $email = cleanInput($_POST['email']);
                $password = $_POST['password'];
                $fullName = cleanInput($_POST['full_name']);
                $roleId = intval($_POST['role_id']);
                
                // Validation
                if (empty($username) || empty($email) || empty($password) || empty($fullName)) {
                    throw new Exception("Tous les champs sont requis");
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Email invalide");
                }
                
                if (strlen($password) < PASSWORD_MIN_LENGTH) {
                    throw new Exception("Mot de passe trop court (min. " . PASSWORD_MIN_LENGTH . " caractères)");
                }
                
                if (!in_array($roleId, [1, 2])) {
                    throw new Exception("Rôle invalide");
                }
                
                if (createUser($username, $email, $password, $fullName, $roleId)) {
                    $message = 'Utilisateur créé avec succès';
                    $messageType = 'success';
                } else {
                    throw new Exception("Erreur lors de la création (l'email ou le nom d'utilisateur existe peut-être déjà)");
                }
                
            } elseif ($action === 'update') {
                $id = intval($_POST['user_id']);
                $username = cleanInput($_POST['username']);
                $email = cleanInput($_POST['email']);
                $fullName = cleanInput($_POST['full_name']);
                $roleId = intval($_POST['role_id']);
                $isActive = intval($_POST['is_active']);
                
                if ($id === 1) {
                    throw new Exception("Impossible de modifier le super administrateur");
                }
                
                if (updateUser($id, $username, $email, $fullName, $roleId, $isActive)) {
                    $message = 'Utilisateur mis à jour avec succès';
                    $messageType = 'success';
                } else {
                    throw new Exception("Erreur lors de la mise à jour");
                }
                
            } elseif ($action === 'delete') {
                $id = intval($_POST['user_id']);
                
                if ($id === 1) {
                    throw new Exception("Impossible de supprimer le super administrateur");
                }
                
                if ($id === $_SESSION['user_id']) {
                    throw new Exception("Vous ne pouvez pas supprimer votre propre compte");
                }
                
                if (deleteUser($id)) {
                    $message = 'Utilisateur supprimé avec succès';
                    $messageType = 'success';
                } else {
                    throw new Exception("Erreur lors de la suppression");
                }
            }
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
        error_log("Erreur gestion utilisateurs: " . $e->getMessage());
    }
    
    header('Location: users.php');
    exit();
}

$users = getAllUsers();
$unreadCount = countUnreadNotifications($_SESSION['user_id']);
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion utilisateurs - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="ml-3 text-xl font-bold text-green-600 dark:text-green-400"><?php echo SITE_NAME; ?></span>
                    <span class="ml-4 px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs font-semibold rounded-full">ADMIN</span>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">Dashboard</a>
                    <a href="transactions.php" class="text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">Transactions</a>
                    <a href="users.php" class="text-green-600 dark:text-green-400 font-medium">Utilisateurs</a>
                    <a href="reports.php" class="text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">Rapports</a>
                    <a href="balance.php" class="text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">Solde</a>
                    
                    <div class="flex items-center space-x-2">
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300"><?php echo $_SESSION['full_name']; ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Administrateur</p>
                        </div>
                        <a href="../auth/logout.php" class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 p-2" title="Déconnexion">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Gestion des utilisateurs</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Créer, modifier et gérer les comptes utilisateurs</p>
        </div>

        <?php if ($flash): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $flash['type'] === 'success' ? 'bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500' : 'bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500'; ?>">
                <div class="flex">
                    <svg class="h-5 w-5 <?php echo $flash['type'] === 'success' ? 'text-green-400' : 'text-red-400'; ?>" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <p class="ml-3 text-sm <?php echo $flash['type'] === 'success' ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'; ?>">
                        <?php echo $flash['message']; ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <button onclick="openCreateModal()" class="mb-6 px-6 py-3 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition shadow-lg">
            <svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Ajouter un utilisateur
        </button>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nom</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Rôle</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Créé le</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-300"><?php echo $user['id']; ?></td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-300"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($user['username']); ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-300"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $user['role_id'] == 1 ? 'bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200' : 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200'; ?>">
                                    <?php echo htmlspecialchars($user['role_name']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $user['is_active'] ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200'; ?>">
                                    <?php echo $user['is_active'] ? 'Actif' : 'Inactif'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                <?php echo formatDate($user['created_at']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php if ($user['id'] != 1): ?>
                                    <button onclick='editUser(<?php echo json_encode($user); ?>)' class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 mr-3">Modifier</button>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Supprimer cet utilisateur ?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300">Supprimer</button>
                                    </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-gray-400">Super Admin</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal création -->
    <div id="createModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 p-8 rounded-lg max-w-md w-full mx-4 shadow-2xl">
            <h3 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">Nouvel utilisateur</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="space-y-4">
                    <input type="text" name="full_name" placeholder="Nom complet" required class="w-full px-3 py-2 border dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <input type="text" name="username" placeholder="Nom d'utilisateur" required class="w-full px-3 py-2 border dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <input type="email" name="email" placeholder="Email" required class="w-full px-3 py-2 border dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <input type="password" name="password" placeholder="Mot de passe" required class="w-full px-3 py-2 border dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <select name="role_id" required class="w-full px-3 py-2 border dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="2">Utilisateur</option>
                        <option value="1">Administrateur</option>
                    </select>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeCreateModal()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300">Annuler</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Créer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal édition -->
    <div id="editModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 p-8 rounded-lg max-w-md w-full mx-4 shadow-2xl">
            <h3 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">Modifier l'utilisateur</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="space-y-4">
                    <input type="text" name="full_name" id="edit_full_name" placeholder="Nom complet" required class="w-full px-3 py-2 border dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <input type="text" name="username" id="edit_username" placeholder="Nom d'utilisateur" required class="w-full px-3 py-2 border dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <input type="email" name="email" id="edit_email" placeholder="Email" required class="w-full px-3 py-2 border dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <select name="role_id" id="edit_role_id" required class="w-full px-3 py-2 border dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="2">Utilisateur</option>
                        <option value="1">Administrateur</option>
                    </select>
                    <select name="is_active" id="edit_is_active" required class="w-full px-3 py-2 border dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="1">Actif</option>
                        <option value="0">Inactif</option>
                    </select>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300">Annuler</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/theme.js"></script>
    <script>
        function openCreateModal() {
            document.getElementById('createModal').classList.remove('hidden');
        }
        
        function closeCreateModal() {
            document.getElementById('createModal').classList.add('hidden');
        }
        
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_full_name').value = user.full_name;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_role_id').value = user.role_id;
            document.getElementById('edit_is_active').value = user.is_active;
            document.getElementById('editModal').classList.remove('hidden');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
    </script>
    
</body>
</html>