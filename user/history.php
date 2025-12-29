<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

requireLogin();

if (isAdmin()) {
    header('Location: ../admin/dashboard.php');
    exit;
}

$user = getCurrentUser();

// Filtres
$status = cleanInput($_GET['status'] ?? '');
$type = cleanInput($_GET['type'] ?? '');

try {
    $db = getDB();
    
    $sql = "SELECT t.*, u.full_name as user_name, u.email as user_email
            FROM transactions t
            LEFT JOIN users u ON t.user_id = u.id
            WHERE t.user_id = ?";
    
    $params = [$user['id']];
    
    if ($status) {
        $sql .= " AND t.status = ?";
        $params[] = $status;
    }
    
    if ($type) {
        $sql .= " AND t.type = ?";
        $params[] = $type;
    }
    
    $sql .= " ORDER BY t.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Erreur history: " . $e->getMessage());
    $transactions = [];
}

$unreadCount = countUnreadNotifications($user['id']);
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="../assets/css/responsive.css">
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
                    <span class="ml-2 text-xl font-bold text-gray-900 dark:text-white"><?php echo SITE_NAME; ?></span>
                </div>
                
                <div class="hidden md:flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400 px-3 py-2">Dashboard</a>
                    <a href="submit.php" class="text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400 px-3 py-2">Nouvelle demande</a>
                    <a href="history.php" class="text-green-600 dark:text-green-400 font-medium px-3 py-2">Historique</a>
                    
                    <a href="notifications.php" class="relative p-2 text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <?php if ($unreadCount > 0): ?>
                        <span class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"><?php echo $unreadCount; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <!-- <button id="themeToggle" class="p-2 text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">
                        <svg class="h-6 w-6 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <svg class="h-6 w-6 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </button> -->
                    
                    <div class="flex items-center space-x-2">
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($user['full_name']); ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Utilisateur</p>
                        </div>
                        <a href="../auth/logout.php" class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 p-2">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">📜 Historique des transactions</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Consultez toutes vos demandes</p>
        </div>

        <!-- Filtres -->
        <div class="mb-6 bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Statut</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="">Tous</option>
                        <option value="en_attente" <?php echo $status === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                        <option value="validee" <?php echo $status === 'validee' ? 'selected' : ''; ?>>Validée</option>
                        <option value="refusee" <?php echo $status === 'refusee' ? 'selected' : ''; ?>>Refusée</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Type</label>
                    <select name="type" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="">Tous</option>
                        <option value="entree" <?php echo $type === 'entree' ? 'selected' : ''; ?>>Entrée</option>
                        <option value="sortie" <?php echo $type === 'sortie' ? 'selected' : ''; ?>>Sortie</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        Filtrer
                    </button>
                </div>
            </form>
        </div>

        <!-- Liste -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Montant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                            Aucune transaction. <a href="submit.php" class="text-green-600 dark:text-green-400 hover:underline">Créer une demande</a>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $trans): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                <?php echo formatDateTime($trans['created_at'], 'd/m/Y H:i'); ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $trans['type'] === 'entree' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200'; ?>">
                                    <?php echo $trans['type'] === 'entree' ? 'Entrée' : 'Sortie'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm font-bold text-gray-900 dark:text-white">
                                <?php echo formatAmount($trans['amount']); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php echo getStatusBadge($trans['status']); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($trans['status'] === 'validee' && $trans['receipt_number']): ?>
                                <a href="../pdf/receipt.php?id=<?php echo $trans['id']; ?>" target="_blank"
                                   class="inline-flex items-center px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-xs font-medium">
                                    📄 Reçu
                                </a>
                                <?php elseif ($trans['status'] === 'refusee' && $trans['admin_comment']): ?>
                                <button onclick="showReason('<?php echo addslashes(htmlspecialchars($trans['admin_comment'])); ?>')"
                                        class="text-red-600 dark:text-red-400 hover:text-red-800 text-xs">
                                    Voir raison
                                </button>
                                <?php else: ?>
                                <span class="text-gray-400 text-xs">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modal raison rejet -->
    <div id="reasonModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Raison du refus</h3>
            <p id="reasonContent" class="text-gray-600 dark:text-gray-400 mb-6"></p>
            <button onclick="closeModal()" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                Fermer
            </button>
        </div>
    </div>

    <script src="../assets/js/theme.js"></script>
    <script>
        function showReason(reason) {
            document.getElementById('reasonContent').textContent = reason;
            document.getElementById('reasonModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('reasonModal').classList.add('hidden');
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
    </script>
</body>
</html>