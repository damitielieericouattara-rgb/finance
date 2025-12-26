<?php
define('APP_ROOT', dirname(__DIR__));
define('APP_ENV', 'production');
require_once '../includes/config.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Rediriger les admins vers leur dashboard
if (isAdmin()) {
    header('Location: ../admin/dashboard.php');
    exit;
}

$user = getCurrentUser();

// Récupérer les transactions de l'utilisateur
$userTransactions = getTransactions($pdo, [
    'user_id' => $user['id'],
    'limit' => 10,
    'order' => 'created_at DESC'
]);

// Statistiques utilisateur
$stats = [
    'pending' => 0,
    'validated' => 0,
    'rejected' => 0,
    'total_validated_amount' => 0
];

$stmt = $pdo->prepare("
    SELECT 
        status,
        COUNT(*) as count,
        SUM(CASE WHEN status = 'validee' THEN amount ELSE 0 END) as validated_amount
    FROM transactions
    WHERE user_id = ?
    GROUP BY status
");
$stmt->execute([$user['id']]);
while ($row = $stmt->fetch()) {
    if ($row['status'] === 'en_attente') $stats['pending'] = $row['count'];
    elseif ($row['status'] === 'validee') $stats['validated'] = $row['count'];
    elseif ($row['status'] === 'rejetee') $stats['rejected'] = $row['count'];
    $stats['total_validated_amount'] = $row['validated_amount'] ?? 0;
}

$unreadCount = countUnreadNotifications($pdo, $user['id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon tableau de bord - <?php echo APP_NAME; ?></title>
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
                        <span class="ml-2 text-xl font-bold text-gray-900 dark:text-white">Financial App</span>
                    </div>
                    <div class="hidden md:ml-6 md:flex md:space-x-4">
                        <a href="dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium bg-primary text-white">
                            Tableau de bord
                        </a>
                        <a href="submit.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            Nouvelle demande
                        </a>
                        <a href="history.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            Historique
                        </a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <a href="notifications.php" class="relative p-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <?php if ($unreadCount > 0): ?>
                            <span data-notification-badge class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                <?php echo $unreadCount; ?>
                            </span>
                            <?php endif; ?>
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
        <div class="px-4 py-5 sm:px-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Bonjour, <?php echo escape(explode(' ', $user['full_name'])[0]); ?> 👋</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Voici un aperçu de vos demandes
            </p>
        </div>

        <!-- Statistiques -->
        <div class="px-4 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">En attente</dt>
                                <dd class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $stats['pending']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Validées</dt>
                                <dd class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $stats['validated']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Rejetées</dt>
                                <dd class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $stats['rejected']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total validé</dt>
                                <dd class="text-xl font-bold text-gray-900 dark:text-white"><?php echo formatAmount($stats['total_validated_amount']); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bouton nouvelle demande -->
        <div class="px-4 mb-6">
            <a href="submit.php" class="inline-flex items-center px-6 py-3 bg-primary text-white rounded-lg hover:bg-blue-600 shadow-lg hover:shadow-xl transition-all">
                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nouvelle demande
            </a>
        </div>

        <!-- Transactions récentes -->
        <div class="px-4">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                        Mes demandes récentes
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Montant</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Motif</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php if (empty($userTransactions)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                    Aucune demande. <a href="submit.php" class="text-primary hover:underline">Créer votre première demande</a>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($userTransactions as $trans): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        <?php echo formatDate($trans['created_at']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $trans['type'] === 'entree' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'; ?>">
                                            <?php echo ucfirst($trans['type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">
                                        <?php echo formatAmount($trans['amount']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                        <?php echo escape(substr($trans['reason'], 0, 40)) . (strlen($trans['reason']) > 40 ? '...' : ''); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo getStatusBadge($trans['status']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?php if ($trans['status'] === 'validee' && $trans['receipt_number']): ?>
                                        <a href="../pdf/receipt.php?id=<?php echo $trans['id']; ?>" 
                                           target="_blank"
                                           class="text-primary hover:text-blue-600 font-medium">
                                            📄 Reçu
                                        </a>
                                        <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6">
                    <a href="history.php" class="text-sm font-medium text-primary hover:text-blue-600">
                        Voir tout l'historique →
                    </a>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/theme.js"></script>
    <script src="../assets/js/notifications_system.js"></script>
    <script>
        if (window.NotificationManager) {
            window.NotificationManager.init(<?php echo $user['id']; ?>);
        }
    </script>
</body>
</html>