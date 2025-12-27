<?php
define('APP_ROOT', dirname(__DIR__));
define('APP_ENV', 'production');
require_once '../config/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || isAdmin()) {
    header('Location: ../auth/login.php');
    exit;
}

$user = getCurrentUser();

// Marquer toutes comme lues
if (isset($_GET['mark_all_read'])) {
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        header('Location: notifications.php');
        exit;
    } catch (Exception $e) {
        error_log("Erreur mark_all_read: " . $e->getMessage());
    }
}

// Marquer une notification comme lue et rediriger
if (isset($_GET['mark_read'])) {
    try {
        $notifId = intval($_GET['mark_read']);
        $db = getDB();
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notifId, $user['id']]);
        
        // Rediriger si demandé
        if (isset($_GET['redirect'])) {
            header('Location: ' . $_GET['redirect']);
            exit;
        }
        
        header('Location: notifications.php');
        exit;
    } catch (Exception $e) {
        error_log("Erreur mark_read: " . $e->getMessage());
    }
}

// Récupérer les notifications
try {
    $db = getDB();
    
    // Notifications non lues
    $stmtUnread = $db->prepare("
        SELECT n.*, t.type as transaction_type, t.amount as transaction_amount
        FROM notifications n
        LEFT JOIN transactions t ON n.transaction_id = t.id
        WHERE n.user_id = ? AND n.is_read = 0
        ORDER BY n.created_at DESC
    ");
    $stmtUnread->execute([$user['id']]);
    $unreadNotifications = $stmtUnread->fetchAll();
    
    // Toutes les notifications (historique)
    $stmtAll = $db->prepare("
        SELECT n.*, t.type as transaction_type, t.amount as transaction_amount
        FROM notifications n
        LEFT JOIN transactions t ON n.transaction_id = t.id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT 50
    ");
    $stmtAll->execute([$user['id']]);
    $allNotifications = $stmtAll->fetchAll();
    
    $unreadCount = count($unreadNotifications);
} catch (Exception $e) {
    error_log("Erreur notifications: " . $e->getMessage());
    $unreadNotifications = [];
    $allNotifications = [];
    $unreadCount = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
</head>
<body class="bg-gray-100 dark:bg-gray-900">
    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <svg class="h-8 w-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="ml-2 text-xl font-bold text-gray-900 dark:text-white"><?php echo SITE_NAME; ?></span>
                    <div class="hidden md:ml-6 md:flex md:space-x-4">
                        <a href="dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Tableau de bord</a>
                        <a href="submit.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Nouvelle demande</a>
                        <a href="history.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Historique</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <button id="themeToggle" class="p-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <svg class="h-6 w-6 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <svg class="h-6 w-6 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </button>
                    <span class="text-sm text-gray-700 dark:text-gray-300"><?php echo escape($user['full_name']); ?></span>
                    <a href="../auth/logout.php" class="text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">Déconnexion</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto py-6 px-4">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">🔔 Notifications</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    <?php echo $unreadCount; ?> notification(s) non lue(s)
                </p>
            </div>
            <?php if ($unreadCount > 0): ?>
            <a href="?mark_all_read=1" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                ✓ Tout marquer comme lu
            </a>
            <?php endif; ?>
        </div>

        <!-- Notifications non lues -->
        <?php if (!empty($unreadNotifications)): ?>
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Non lues</h2>
            <div class="space-y-3">
                <?php foreach ($unreadNotifications as $notif): ?>
                <div class="bg-white dark:bg-gray-800 border-l-4 border-<?php 
                    echo $notif['type'] === 'urgent' ? 'red' : 
                        ($notif['type'] === 'warning' ? 'yellow' : 
                        ($notif['type'] === 'success' ? 'green' : 'blue')); 
                ?>-500 p-4 rounded-r-lg shadow hover:shadow-lg transition">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <span class="text-xl mr-2">
                                    <?php 
                                    echo $notif['type'] === 'urgent' ? '🔴' : 
                                        ($notif['type'] === 'warning' ? '⚠️' : 
                                        ($notif['type'] === 'success' ? '✅' : 'ℹ️'));
                                    ?>
                                </span>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    <?php echo escape($notif['title']); ?>
                                </p>
                            </div>
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                <?php echo escape($notif['message']); ?>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                <?php echo formatDateTime($notif['created_at']); ?>
                            </p>
                        </div>
                        <div class="flex items-center space-x-2 ml-4">
                            <?php if ($notif['transaction_id']): ?>
                            <a href="?mark_read=<?php echo $notif['id']; ?>&redirect=history.php" 
                               class="text-green-600 dark:text-green-400 hover:text-green-800 text-sm font-medium">
                                Voir →
                            </a>
                            <?php endif; ?>
                            <a href="?mark_read=<?php echo $notif['id']; ?>" 
                               class="text-gray-600 dark:text-gray-400 hover:text-gray-800">
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

        <!-- Historique -->
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Historique</h2>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <?php if (empty($allNotifications)): ?>
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    Aucune notification
                </div>
                <?php else: ?>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($allNotifications as $notif): ?>
                    <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 <?php echo !$notif['is_read'] ? 'bg-blue-50 dark:bg-blue-900/20' : ''; ?>">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center mb-1">
                                    <span class="mr-2">
                                        <?php 
                                        echo $notif['type'] === 'urgent' ? '🔴' : 
                                            ($notif['type'] === 'warning' ? '⚠️' : 
                                            ($notif['type'] === 'success' ? '✅' : 'ℹ️'));
                                        ?>
                                    </span>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        <?php echo escape($notif['title']); ?>
                                        <?php if (!$notif['is_read']): ?>
                                        <span class="ml-2 px-2 py-1 text-xs bg-blue-600 text-white rounded">Nouveau</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo escape($notif['message']); ?>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                                    <?php echo formatDateTime($notif['created_at']); ?>
                                </p>
                            </div>
                            <?php if ($notif['transaction_id'] && !$notif['is_read']): ?>
                            <a href="?mark_read=<?php echo $notif['id']; ?>&redirect=history.php" 
                               class="ml-4 text-green-600 dark:text-green-400 hover:text-green-800 text-sm font-medium">
                                Voir →
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
</body>
</html>