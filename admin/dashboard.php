<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

requireAdmin();

$db = getDB();

// Récupérer les statistiques
try {
    $currentBalance = getCurrentBalance();
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM transactions");
    $totalTransactions = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM transactions WHERE status = 'en_attente'");
    $pendingTransactions = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM transactions WHERE status = 'validee' AND MONTH(validated_at) = MONTH(CURRENT_DATE) AND YEAR(validated_at) = YEAR(CURRENT_DATE)");
    $monthValidated = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE type = 'entree' AND status = 'validee' AND MONTH(validated_at) = MONTH(CURRENT_DATE) AND YEAR(validated_at) = YEAR(CURRENT_DATE)");
    $monthIncome = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE type = 'sortie' AND status = 'validee' AND MONTH(validated_at) = MONTH(CURRENT_DATE) AND YEAR(validated_at) = YEAR(CURRENT_DATE)");
    $monthExpenses = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE is_active = 1");
    $activeUsers = $stmt->fetch()['total'];
    
    $stmt = $db->query("
        SELECT t.*, u.full_name as user_name 
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        ORDER BY t.created_at DESC
        LIMIT 10
    ");
    $recentTransactions = $stmt->fetchAll();
    
    $stmt = $db->query("
        SELECT status, COUNT(*) as count 
        FROM transactions 
        GROUP BY status
    ");
    $transactionsByStatus = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
} catch (Exception $e) {
    error_log("Erreur dashboard admin: " . $e->getMessage());
    $currentBalance = 0;
    $totalTransactions = 0;
    $pendingTransactions = 0;
    $monthValidated = 0;
    $monthIncome = 0;
    $monthExpenses = 0;
    $activeUsers = 0;
    $recentTransactions = [];
    $transactionsByStatus = [];
}

$unreadCount = countUnreadNotifications($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
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
                    <a href="dashboard.php" class="text-green-600 dark:text-green-400 font-medium">Dashboard</a>
                    <a href="transactions.php" class="text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">Transactions</a>
                    <a href="users.php" class="text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">Utilisateurs</a>
                    <a href="reports.php" class="text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">Rapports</a>
                    <a href="balance.php" class="text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">Gérer Solde</a>
                    
                    <button id="themeToggle" class="p-2 text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">
                        <svg class="h-6 w-6 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <svg class="h-6 w-6 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </button>
                    
                    <a href="../includes/notifications.php" class="relative p-2 text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <?php if ($unreadCount > 0): ?>
                            <span class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                <?php echo $unreadCount; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    
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

    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Tableau de bord</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Vue d'ensemble de l'activité financière</p>
        </div>

        <!-- Statistiques principales -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-br from-green-500 to-green-600 p-6 rounded-xl shadow-lg text-white">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-white bg-opacity-20 rounded-lg">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <p class="text-sm text-green-100 mb-1">Solde actuel</p>
                    <p class="text-3xl font-bold"><?php echo formatAmount($currentBalance); ?></p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                        <svg class="h-8 w-8 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">En attente</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo $pendingTransactions; ?></p>
                    <a href="transactions.php?status=en_attente" class="text-sm text-green-600 dark:text-green-400 hover:underline mt-2 inline-block">
                        Voir les demandes →
                    </a>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Entrées ce mois</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo formatAmount($monthIncome); ?></p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1"><?php echo $monthValidated; ?> validées</p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                        <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Sorties ce mois</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo formatAmount($monthExpenses); ?></p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Balance: <?php echo formatAmount($monthIncome - $monthExpenses); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Statistiques secondaires -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📊 Transactions</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">Total</span>
                        <span class="font-bold text-gray-900 dark:text-white"><?php echo $totalTransactions; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">En attente</span>
                        <span class="font-bold text-yellow-600 dark:text-yellow-400"><?php echo $transactionsByStatus['en_attente'] ?? 0; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">Validées</span>
                        <span class="font-bold text-green-600 dark:text-green-400"><?php echo $transactionsByStatus['validee'] ?? 0; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">Refusées</span>
                        <span class="font-bold text-red-600 dark:text-red-400"><?php echo $transactionsByStatus['refusee'] ?? 0; ?></span>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">👥 Utilisateurs</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">Actifs</span>
                        <span class="font-bold text-green-600 dark:text-green-400"><?php echo $activeUsers; ?></span>
                    </div>
                    <a href="users.php" class="text-sm text-green-600 dark:text-green-400 hover:underline">
                        Gérer les utilisateurs →
                    </a>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">⚡ Actions rapides</h3>
                <div class="space-y-2">
                    <a href="transactions.php?status=en_attente" class="block w-full bg-green-600 text-white text-center py-2 rounded-lg hover:bg-green-700 transition">
                        📋 Traiter les demandes
                    </a>
                    <a href="balance.php" class="block w-full bg-blue-600 text-white text-center py-2 rounded-lg hover:bg-blue-700 transition">
                        💰 Gérer le solde
                    </a>
                    <a href="reports.php" class="block w-full bg-purple-600 text-white text-center py-2 rounded-lg hover:bg-purple-700 transition">
                        📊 Rapports
                    </a>
                </div>
            </div>
        </div>

        <!-- Dernières transactions -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 border-b dark:border-gray-700">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Dernières transactions</h3>
                    <a href="transactions.php" class="text-sm text-green-600 dark:text-green-400 hover:underline">
                        Voir tout →
                    </a>
                </div>
            </div>
            
            <?php if (!empty($recentTransactions)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Utilisateur</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Montant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($recentTransactions as $t): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-300">
                                    <?php echo formatDateTime($t['created_at'], 'd/m/Y H:i'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-300">
                                    <?php echo htmlspecialchars($t['user_name']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $t['type'] === 'entree' ? 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200' : 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200'; ?>">
                                        <?php echo $t['type'] === 'entree' ? '📈 Entrée' : '📉 Sortie'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm font-bold text-gray-900 dark:text-gray-300">
                                    <?php echo formatAmount($t['amount']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <?php echo getStatusBadge($t['status']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <a href="transactions.php?id=<?php echo $t['id']; ?>" class="text-green-600 dark:text-green-400 hover:underline">
                                        Voir détails
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                <p>Aucune transaction récente</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/theme.js"></script>
</body>
</html>