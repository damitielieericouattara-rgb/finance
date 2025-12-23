<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'create') {
            $username = cleanInput($_POST['username']);
            $email = cleanInput($_POST['email']);
            $password = $_POST['password'];
            $fullName = cleanInput($_POST['full_name']);
            $roleId = intval($_POST['role_id']);
            
            if (createUser($username, $email, $password, $fullName, $roleId)) {
                setFlashMessage('success', 'Utilisateur créé avec succès');
            }
        } elseif ($action === 'update') {
            $id = intval($_POST['user_id']);
            $username = cleanInput($_POST['username']);
            $email = cleanInput($_POST['email']);
            $fullName = cleanInput($_POST['full_name']);
            $roleId = intval($_POST['role_id']);
            $isActive = intval($_POST['is_active']);
            
            if (updateUser($id, $username, $email, $fullName, $roleId, $isActive)) {
                setFlashMessage('success', 'Utilisateur mis à jour');
            }
        } elseif ($action === 'delete') {
            $id = intval($_POST['user_id']);
            if (deleteUser($id)) {
                setFlashMessage('success', 'Utilisateur supprimé');
            }
        }
        
        header('Location: users.php');
        exit();
    }
}

$users = getAllUsers();
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion utilisateurs</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex justify-between">
                <span class="text-xl font-bold text-green-600"><?php echo SITE_NAME; ?></span>
                <div class="space-x-4">
                    <a href="dashboard.php">Dashboard</a>
                    <a href="transactions.php">Transactions</a>
                    <a href="users.php" class="text-green-600 font-semibold">Utilisateurs</a>
                    <a href="../auth/logout.php" class="text-red-600">Déconnexion</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Gestion des utilisateurs</h1>
        
        <?php if ($flash): ?>
        <div class="mb-6 p-4 <?php echo $flash['type'] === 'success' ? 'bg-green-50' : 'bg-red-50'; ?> rounded">
            <?php echo $flash['message']; ?>
        </div>
        <?php endif; ?>

        <button onclick="document.getElementById('createModal').classList.remove('hidden')" class="mb-6 px-4 py-2 bg-green-600 text-white rounded">
            + Ajouter un utilisateur
        </button>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left">Nom</th>
                        <th class="px-6 py-3 text-left">Email</th>
                        <th class="px-6 py-3 text-left">Rôle</th>
                        <th class="px-6 py-3 text-left">Statut</th>
                        <th class="px-6 py-3 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-xs <?php echo $user['role_id'] == 1 ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                <?php echo htmlspecialchars($user['role_name']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-xs <?php echo $user['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $user['is_active'] ? 'Actif' : 'Inactif'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($user['role_id'] != 1): ?>
                            <button onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" class="text-blue-600 mr-3">Modifier</button>
                            <form method="POST" class="inline" onsubmit="return confirm('Supprimer cet utilisateur ?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" class="text-red-600">Supprimer</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal création (à compléter avec formulaire) -->
    <div id="createModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg max-w-md w-full">
            <h3 class="text-xl font-bold mb-4">Nouvel utilisateur</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="space-y-4">
                    <input type="text" name="full_name" placeholder="Nom complet" required class="w-full px-3 py-2 border rounded">
                    <input type="text" name="username" placeholder="Nom d'utilisateur" required class="w-full px-3 py-2 border rounded">
                    <input type="email" name="email" placeholder="Email" required class="w-full px-3 py-2 border rounded">
                    <input type="password" name="password" placeholder="Mot de passe" required class="w-full px-3 py-2 border rounded">
                    <select name="role_id" required class="w-full px-3 py-2 border rounded">
                        <option value="2">Utilisateur</option>
                        <option value="1">Administrateur</option>
                    </select>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" class="px-4 py-2 border rounded">Annuler</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded">Créer</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>