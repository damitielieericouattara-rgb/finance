<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

requireAdmin();

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
        LIMIT 100
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
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - <?php echo SITE_NAME; ?></title>
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
                    <span class="ml-3 text-xl font-bold text-green-600 dark:text-green-400"><?php echo SITE_NAME; ?></span>
                    <span class="ml-4 px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs font-semibold rounded-full">ADMIN</span>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">Dashboard</a>
                    <a href="transactions.php" class="text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">Transactions</a>
                    <a href="users.php" class="text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">Utilisateurs</a>
                    <a href="reports.php" class="text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">Rapports</a>
                    <a href="balance.php" class="text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">Solde</a>
                    
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

    <main class="max-w-5xl mx-auto py-6 px-4">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">🔔 Mes Notifications</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    <?php echo $unreadCount; ?> notification(s) non lue(s)
                </p>
            </div>
            <?php if ($unreadCount > 0): ?>
            <a href="?mark_all_read=1" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium shadow-lg">
                ✓ Tout marquer comme lu
            </a>
            <?php endif; ?>
        </div>

        <!-- Notifications non lues -->
        <?php if (!empty($unreadNotifications)): ?>
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                <span class="inline-block w-3 h-3 bg-red-500 rounded-full mr-2 animate-pulse"></span>
                Non lues (<?php echo count($unreadNotifications); ?>)
            </h2>
            <div class="space-y-3">
                <?php foreach ($unreadNotifications as $notif): ?>
                <div class="bg-white dark:bg-gray-800 border-l-4 border-<?php 
                    echo $notif['type'] === 'urgent' ? 'red' : 
                        ($notif['type'] === 'warning' ? 'yellow' : 
                        ($notif['type'] === 'success' ? 'green' : 'blue')); 
                ?>-500 p-5 rounded-r-lg shadow-lg hover:shadow-xl transition">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <span class="text-2xl mr-2">
                                    <?php 
                                    echo $notif['type'] === 'urgent' ? '🔴' : 
                                        ($notif['type'] === 'warning' ? '⚠️' : 
                                        ($notif['type'] === 'success' ? '✅' : 'ℹ️'));
                                    ?>
                                </span>
                                <p class="font-bold text-gray-900 dark:text-white text-lg">
                                    <?php echo htmlspecialchars($notif['title']); ?>
                                </p>
                                <?php if ($notif['type'] === 'urgent'): ?>
                                <span class="ml-2 px-2 py-1 text-xs bg-red-500 text-white rounded-full animate-pulse">URGENT</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                                <?php echo htmlspecialchars($notif['message']); ?>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                📅 <?php echo formatDateTime($notif['created_at']); ?>
                            </p>
                        </div>
                        <div class="flex flex-col items-end space-y-2 ml-4">
                            <?php if ($notif['transaction_id']): ?>
                            <a href="?mark_read=<?php echo $notif['id']; ?>&redirect=transactions.php?id=<?php echo $notif['transaction_id']; ?>" 
                               class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 shadow">
                                👁️ Voir la transaction
                            </a>
                            <?php endif; ?>
                            <a href="?mark_read=<?php echo $notif['id']; ?>" 
                               class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
                                ✓ Marquer comme lu
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
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">📜 Historique complet</h2>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                <?php if (empty($allNotifications)): ?>
                <div class="p-12 text-center text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <p class="text-lg">Aucune notification pour le moment</p>
                </div>
                <?php else: ?>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($allNotifications as $notif): ?>
                    <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition <?php echo !$notif['is_read'] ? 'bg-blue-50 dark:bg-blue-900/20' : ''; ?>">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center mb-1">
                                    <span class="mr-2 text-lg">
                                        <?php 
                                        echo $notif['type'] === 'urgent' ? '🔴' : 
                                            ($notif['type'] === 'warning' ? '⚠️' : 
                                            ($notif['type'] === 'success' ? '✅' : 'ℹ️'));
                                        ?>
                                    </span>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($notif['title']); ?>
                                        <?php if (!$notif['is_read']): ?>
                                        <span class="ml-2 px-2 py-1 text-xs bg-blue-600 text-white rounded">Nouveau</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                    <?php echo htmlspecialchars($notif['message']); ?>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-500">
                                    <?php echo formatDateTime($notif['created_at']); ?>
                                </p>
                            </div>
                            <?php if ($notif['transaction_id'] && !$notif['is_read']): ?>
                            <a href="?mark_read=<?php echo $notif['id']; ?>&redirect=transactions.php?id=<?php echo $notif['transaction_id']; ?>" 
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