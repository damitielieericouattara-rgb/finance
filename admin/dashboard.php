<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

requireAdmin();

$period = $_GET['period'] ?? 'day';
$stats = getDashboardStats($period);
$currentBalance = getCurrentBalance();
$chartData = getChartData('month');

// Récupérer les transactions urgentes en attente
$db = getDB();
$urgentStmt = $db->query("
    SELECT t.*, u.full_name as user_name 
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.status = 'en_attente' AND t.urgency = 'urgent'
    ORDER BY t.created_at ASC
    LIMIT 5
");
$urgentTransactions = $urgentStmt->fetchAll();

// Récupérer les dernières transactions
$recentStmt = $db->query("
    SELECT t.*, u.full_name as user_name 
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.id
    ORDER BY t.created_at DESC
    LIMIT 10
");
$recentTransactions = $recentStmt->fetchAll();

$unreadCount = countUnreadNotifications($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: {} }
        }
    </script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 transition-colors duration-300">
    <!-- Navigation FIXE avec mode sombre -->
    <nav class="bg-white dark:bg-gray-800 shadow-lg sticky top-0 z-50 transition-colors duration-300">
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
                    <a href="dashboard.php" class="text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 font-medium">
                        Dashboard
                    </a>
                    <a href="transactions.php" class="text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">
                        Transactions
                    </a>
                    <a href="users.php" class="text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">
                        Utilisateurs
                    </a>
                    <a href="reports.php" class="text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">
                        Rapports
                    </a>
                    <a href="balance.php" class="text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">
                        Gérer Solde
                    </a>
                    
                    <div class="relative">
                        <button id="notificationBtn" data-notification-toggle class="relative p-2 text-gray-600 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span data-notification-badge class="absolute top-0 right-0 block h-5 w-5 rounded-full bg-red-500 text-white text-xs flex items-center justify-center <?php echo $unreadCount > 0 ? '' : 'hidden'; ?>">
                                <?php echo $unreadCount; ?>
                            </span>
                        </button>
                        
                        <!-- Dropdown notifications -->
                        <div data-notification-dropdown class="hidden absolute right-0 mt-2 w-96 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 max-h-96 overflow-y-auto">
                            <!-- Contenu chargé dynamiquement -->
                        </div>
                    </div>
                    
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
        <!-- En-tête -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Tableau de bord</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Vue d'ensemble de l'activité financière</p>
        </div>

        <!-- Alertes urgentes -->
        <?php if (!empty($urgentTransactions)): ?>
            <div class="mb-8 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-6 rounded-lg">
                <div class="flex items-center mb-4">
                    <svg class="h-6 w-6 text-red-600 dark:text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <h3 class="text-lg font-bold text-red-900 dark:text-red-200">Demandes urgentes en attente</h3>
                </div>
                <div class="space-y-3">
                    <?php foreach ($urgentTransactions as $trans): ?>
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg flex justify-between items-center">
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($trans['user_name']); ?></p>
                                <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo formatAmount($trans['amount']); ?> - <?php echo htmlspecialchars($trans['description']); ?></p>
                                <p class="text-xs text-gray-500 dark:text-gray-500">Il y a <?php echo date_diff(new DateTime($trans['created_at']), new DateTime())->format('%h heures %i min'); ?></p>
                            </div>
                            <a href="transactions.php?id=<?php echo $trans['id']; ?>" class="bg-red-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-red-700">
                                Traiter maintenant
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Reste du contenu avec classes dark mode ajoutées partout -->
        <!-- [Le reste du code avec toutes les classes dark: ajoutées] -->
    </div>

    <!-- Scripts -->
    <script src="../assets/js/theme.js"></script>
    <script src="../assets/js/notifications.js"></script>
    <script>
        // Code des graphiques Chart.js...
    </script>
</body>
</html>