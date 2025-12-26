<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

requireLogin();

$userId = $_SESSION['user_id'];
$db = getDB();

// Marquer une notification comme lue
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notifId = intval($_GET['mark_read']);
    try {
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notifId, $userId]);
        header('Location: notifications.php');
        exit;
    } catch (Exception $e) {
        error_log("Erreur mark_read: " . $e->getMessage());
    }
}

// Marquer toutes comme lues
if (isset($_POST['mark_all_read'])) {
    try {
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        header('Location: notifications.php');
        exit;
    } catch (Exception $e) {
        error_log("Erreur mark_all_read: " . $e->getMessage());
    }
}

// Supprimer une notification
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $notifId = intval($_GET['delete']);
    try {
        $stmt = $db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->execute([$notifId, $userId]);
        header('Location: notifications.php');
        exit;
    } catch (Exception $e) {
        error_log("Erreur delete notification: " . $e->getMessage());
    }
}

// Récupérer les notifications
try {
    $stmt = $db->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll();
    
    // Compter les non lues
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    $unreadCount = $stmt->fetch()['count'];
    
} catch (Exception $e) {
    error_log("Erreur récupération notifications: " . $e->getMessage());
    $notifications = [];
    $unreadCount = 0;
}

$isAdmin = isAdmin();
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="<?php echo $isAdmin ? '../admin/dashboard.php' : '../user/dashboard.php'; ?>" class="flex items-center">
                        <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="ml-3 text-xl font-bold text-green-600 dark:text-green-400"><?php echo SITE_NAME; ?></span>
                    </a>
                    <?php if ($isAdmin): ?>
                        <span class="ml-4 px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs font-semibold rounded-full">ADMIN</span>
                    <?php endif; ?>
                </div>
                
                <div class="flex items-center space-x-4">
                    <button id="themeToggle" class="p-2 text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400 transition-colors">
                        <svg id="sunIcon" class="h-6 w-6 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <svg id="moonIcon" class="h-6 w-6 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </button>
                    
                    <div class="flex items-center space-x-2">
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300"><?php echo $_SESSION['full_name']; ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo $isAdmin ? 'Administrateur' : 'Utilisateur'; ?></p>
                        </div>
                        <a href="../auth/logout.php" class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 p-2 transition-colors" title="Déconnexion">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- En-tête -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">🔔 Notifications</h1>
                <p class="text-gray-600 dark:text-gray-400">
                    <?php echo $unreadCount > 0 ? "$unreadCount notification(s) non lue(s)" : "Aucune nouvelle notification"; ?>
                </p>
            </div>
            
            <?php if ($unreadCount > 0): ?>
            <form method="POST" class="inline">
                <button type="submit" name="mark_all_read" 
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    ✓ Tout marquer comme lu
                </button>
            </form>
            <?php endif; ?>
        </div>

        <!-- Liste des notifications -->
        <?php if (empty($notifications)): ?>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-12 text-center">
                <svg class="h-24 w-24 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Aucune notification</h3>
                <p class="text-gray-600 dark:text-gray-400">Vous n'avez aucune notification pour le moment.</p>
                <a href="<?php echo $isAdmin ? '../admin/dashboard.php' : '../user/dashboard.php'; ?>" 
                   class="inline-block mt-6 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    Retour au tableau de bord
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($notifications as $notif): ?>
                    <?php
                    // Déterminer l'icône et la couleur selon le type
                    $typeConfig = [
                        'info' => ['icon' => 'ℹ️', 'bg' => 'bg-blue-50 dark:bg-blue-900/20', 'border' => 'border-blue-200 dark:border-blue-800', 'text' => 'text-blue-600 dark:text-blue-400'],
                        'success' => ['icon' => '✅', 'bg' => 'bg-green-50 dark:bg-green-900/20', 'border' => 'border-green-200 dark:border-green-800', 'text' => 'text-green-600 dark:text-green-400'],
                        'warning' => ['icon' => '⚠️', 'bg' => 'bg-yellow-50 dark:bg-yellow-900/20', 'border' => 'border-yellow-200 dark:border-yellow-800', 'text' => 'text-yellow-600 dark:text-yellow-400'],
                        'error' => ['icon' => '❌', 'bg' => 'bg-red-50 dark:bg-red-900/20', 'border' => 'border-red-200 dark:border-red-800', 'text' => 'text-red-600 dark:text-red-400'],
                    ];
                    $config = $typeConfig[$notif['type']] ?? $typeConfig['info'];
                    ?>
                    
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border-l-4 <?php echo $config['border']; ?> <?php echo !$notif['is_read'] ? 'ring-2 ring-green-500 ring-opacity-50' : ''; ?>">
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start space-x-4 flex-1">
                                    <div class="flex-shrink-0">
                                        <div class="p-3 <?php echo $config['bg']; ?> rounded-lg">
                                            <span class="text-3xl"><?php echo $config['icon']; ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-2 mb-2">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                                <?php echo htmlspecialchars($notif['title']); ?>
                                            </h3>
                                            <?php if (!$notif['is_read']): ?>
                                                <span class="px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs font-semibold rounded-full">
                                                    Nouveau
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <p class="text-gray-700 dark:text-gray-300 mb-3">
                                            <?php echo nl2br(htmlspecialchars($notif['message'])); ?>
                                        </p>
                                        
                                        <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                                            <span>📅 <?php echo formatDateTime($notif['created_at'], 'd/m/Y à H:i'); ?></span>
                                            <?php if ($notif['transaction_id']): ?>
                                                <a href="<?php echo $isAdmin ? '../admin/transactions.php?id=' : '../user/history.php?id='; ?><?php echo $notif['transaction_id']; ?>" 
                                                   class="<?php echo $config['text']; ?> hover:underline font-medium">
                                                    Voir la transaction →
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex flex-col space-y-2 ml-4">
                                    <?php if (!$notif['is_read']): ?>
                                        <a href="?mark_read=<?php echo $notif['id']; ?>" 
                                           class="p-2 text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors"
                                           title="Marquer comme lu">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="?delete=<?php echo $notif['id']; ?>" 
                                       class="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                       onclick="return confirm('Supprimer cette notification ?')"
                                       title="Supprimer">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Bouton retour -->
        <div class="mt-8 text-center">
            <a href="<?php echo $isAdmin ? '../admin/dashboard.php' : '../user/dashboard.php'; ?>" 
               class="inline-flex items-center px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Retour au tableau de bord
            </a>
        </div>
    </div>

    <script>
        // Gestion du thème sombre
        const themeToggle = document.getElementById('themeToggle');
        const htmlElement = document.documentElement;
        
        // Charger le thème sauvegardé
        function loadTheme() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                htmlElement.classList.add('dark');
            } else {
                htmlElement.classList.remove('dark');
            }
        }
        
        // Basculer le thème
        function toggleTheme() {
            htmlElement.classList.toggle('dark');
            const isDark = htmlElement.classList.contains('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }
        
        // Initialiser
        loadTheme();
        
        // Event listener
        if (themeToggle) {
            themeToggle.addEventListener('click', toggleTheme);
        }
    </script>
    <script src="../assets/js/theme.js"></script>
</body>
</html>