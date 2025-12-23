<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireLogin();

$filters = ['user_id' => $_SESSION['user_id']];
if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
if (!empty($_GET['type'])) $filters['type'] = $_GET['type'];

$page = max(1, intval($_GET['page'] ?? 1));
$transactions = getTransactions($filters, $page, 20);
$totalTransactions = countTransactions($filters);
$totalPages = ceil($totalTransactions / 20);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon historique - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation identique à dashboard -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="ml-3 text-xl font-bold text-green-600"><?php echo SITE_NAME; ?></span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 hover:text-green-600">Dashboard</a>
                    <a href="submit.php" class="text-gray-700 hover:text-green-600">Nouvelle demande</a>
                    <a href="history.php" class="text-green-600 font-medium">Historique</a>
                    <a href="../auth/logout.php" class="text-red-600">Déconnexion</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Mon historique de transactions</h1>
        
        <!-- Filtres -->
        <div class="bg-white p-4 rounded-lg shadow mb-6">
            <form method="GET" class="flex gap-4">
                <select name="status" class="px-3 py-2 border rounded">
                    <option value="">Tous les statuts</option>
                    <option value="en_attente">En attente</option>
                    <option value="validee">Validée</option>
                    <option value="refusee">Refusée</option>
                </select>
                <select name="type" class="px-3 py-2 border rounded">
                    <option value="">Tous les types</option>
                    <option value="entree">Entrée</option>
                    <option value="sortie">Sortie</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded">Filtrer</button>
            </form>
        </div>

        <!-- Table des transactions -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($transactions as $t): ?>
                    <tr>
                        <td class="px-6 py-4"><?php echo formatDateTime($t['created_at']); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full <?php echo $t['type'] === 'entree' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo getTypeLabel($t['type']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 font-semibold"><?php echo formatAmount($t['amount']); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars(substr($t['description'], 0, 50)); ?>...</td>
                        <td class="px-6 py-4">
                            <?php $colors = ['en_attente' => 'bg-yellow-100 text-yellow-800', 'validee' => 'bg-green-100 text-green-800', 'refusee' => 'bg-red-100 text-red-800']; ?>
                            <span class="px-2 py-1 text-xs rounded-full <?php echo $colors[$t['status']]; ?>">
                                <?php echo getStatusLabel($t['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>