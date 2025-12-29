<?php
define('APP_ROOT', dirname(__DIR__));
define('APP_ENV', 'production');
require_once '../includes/config.php';

if (!isLoggedIn() || isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$user = getCurrentUser();

if (isset($_GET['mark_all_read'])) {
    markAllNotificationsAsRead($pdo, $user['id']);
    header('Location: notifications.php');
    exit;
}

if (isset($_GET['mark_read'])) {
    markNotificationAsRead($pdo, $_GET['mark_read']);
    if (isset($_GET['redirect'])) {
        header('Location: ' . $_GET['redirect']);
        exit;
    }
}

$notifications = getUnreadNotifications($pdo, $user['id']);
$allNotifications = getAllNotifications($pdo, $user['id'], 50);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/responsive.css">
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
                        <a href="dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
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

    <main class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Notifications</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    <?php echo count($notifications); ?> non lue(s)
                </p>
            </div>
            <?php if (count($notifications) > 0): ?>
            <a href="?mark_all_read=1" class="px-4 py-2 text-sm bg-primary text-white rounded-lg hover:bg-blue-600">
                Tout marquer comme lu
            </a>
            <?php endif; ?>
        </div>

        <!-- Notifications non lues -->
        <?php if (count($notifications) > 0): ?>
        <div class="px-4 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Non lues</h2>
            <div class="space-y-3">
                <?php foreach ($notifications as $notif): ?>
                <div class="bg-blue-50 dark:bg-blue-900/30 border-l-4 border-primary p-4 rounded-r-lg hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                <?php echo escape($notif['title']); ?>
                            </p>
                            <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                                <?php echo escape($notif['message']); ?>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                <?php echo formatDateTime($notif['created_at']); ?>
                            </p>
                        </div>
                        <div class="flex items-center space-x-2 ml-4">
                            <?php if ($notif['redirect_url']): ?>
                            <a href="?mark_read=<?php echo $notif['id']; ?>&redirect=<?php echo urlencode($notif['redirect_url']); ?>" 
                               class="text-primary hover:text-blue-600 text-sm font-medium">
                                Voir
                            </a>
                            <?php endif; ?>
                            <a href="?mark_read=<?php echo $notif['id']; ?>" 
                               class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Toutes les notifications -->
        <div class="px-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Historique</h2>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <?php if (empty($allNotifications)): ?>
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    Aucune notification
                </div>
                <?php else: ?>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($allNotifications as $notif): ?>
                    <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors <?php echo !$notif['is_read'] ? 'bg-blue-50 dark:bg-blue-900/20' : ''; ?>">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    <?php echo escape($notif['title']); ?>
                                    <?php if (!$notif['is_read']): ?>
                                    <span class="ml-2 px-2 py-1 text-xs bg-primary text-white rounded">Nouveau</span>
                                    <?php endif; ?>
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    <?php echo escape($notif['message']); ?>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                                    <?php echo formatDateTime($notif['created_at']); ?>
                                </p>
                            </div>
                            <?php if ($notif['redirect_url'] && !$notif['is_read']): ?>
                            <a href="?mark_read=<?php echo $notif['id']; ?>&redirect=<?php echo urlencode($notif['redirect_url']); ?>" 
                               class="ml-4 text-primary hover:text-blue-600 text-sm font-medium">
                                Voir
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
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