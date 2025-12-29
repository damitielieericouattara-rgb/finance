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
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $requiredDate = $_POST['required_date'] ?? '';
    $urgency = $_POST['urgency'] ?? 'normal';
    
    if (empty($type) || $amount <= 0 || empty($description) || empty($requiredDate)) {
        $message = 'Tous les champs sont requis et le montant doit être positif.';
        $messageType = 'error';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("
                INSERT INTO transactions (user_id, type, amount, description, required_date, urgency, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'en_attente', NOW())
            ");
            
            if ($stmt->execute([$user['id'], $type, $amount, $description, $requiredDate, $urgency])) {
                $transactionId = $db->lastInsertId();
                
                // Notifier les admins
                $adminStmt = $db->prepare("SELECT id FROM users WHERE role_id = 1 AND is_active = 1");
                $adminStmt->execute();
                $admins = $adminStmt->fetchAll();
                
                foreach ($admins as $admin) {
                    createNotification(
                        $admin['id'],
                        $urgency === 'urgent' ? '🔴 Nouvelle demande URGENTE' : 'Nouvelle demande',
                        "Nouvelle demande de " . $user['full_name'] . " : " . formatAmount($amount),
                        $urgency === 'urgent' ? 'urgent' : 'info',
                        $transactionId
                    );
                }
                
                $message = 'Demande soumise avec succès. Les administrateurs ont été notifiés.';
                $messageType = 'success';
                header('Refresh: 2; url=dashboard.php');
            }
        } catch (Exception $e) {
            error_log("Erreur submit: " . $e->getMessage());
            $message = 'Erreur lors de la soumission';
            $messageType = 'error';
        }
    }
}

$unreadCount = countUnreadNotifications($user['id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle demande - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body class="bg-gray-100 dark:bg-gray-900">
    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="ml-2 text-xl font-bold text-gray-900 dark:text-white"><?php echo SITE_NAME; ?></span>
                    <div class="hidden md:ml-6 md:flex md:space-x-4">
                        <a href="dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Tableau de bord</a>
                        <a href="submit.php" class="px-3 py-2 rounded-md text-sm font-medium bg-green-600 text-white">Nouvelle demande</a>
                        <a href="history.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Historique</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="notifications.php" class="relative p-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <?php if ($unreadCount > 0): ?>
                        <span class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"><?php echo $unreadCount; ?></span>
                        <?php endif; ?>
                    </a>
                    
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

    <main class="max-w-3xl mx-auto py-6 px-4">
        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700' : 'bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700'; ?>">
            <p class="text-sm <?php echo $messageType === 'success' ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'; ?>">
                <?php echo escape($message); ?>
            </p>
        </div>
        <?php endif; ?>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Nouvelle demande</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Remplissez le formulaire ci-dessous</p>

            <form method="POST">
                <div class="space-y-6">
                    <!-- Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Type de transaction *</label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="type" value="entree" required class="peer sr-only">
                                <div class="border-2 border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center peer-checked:border-green-500 peer-checked:bg-green-50 dark:peer-checked:bg-green-900/20">
                                    <svg class="mx-auto h-8 w-8 text-green-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    <span class="font-semibold text-gray-900 dark:text-white">Entrée</span>
                                </div>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" name="type" value="sortie" required class="peer sr-only">
                                <div class="border-2 border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center peer-checked:border-red-500 peer-checked:bg-red-50 dark:peer-checked:bg-red-900/20">
                                    <svg class="mx-auto h-8 w-8 text-red-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                    </svg>
                                    <span class="font-semibold text-gray-900 dark:text-white">Sortie</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Montant -->
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Montant (FCFA) *</label>
                        <input type="number" id="amount" name="amount" required min="1" step="1"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500"
                               placeholder="50000">
                    </div>

                    <!-- Date -->
                    <div>
                        <label for="required_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date souhaitée *</label>
                        <input type="date" id="required_date" name="required_date" required min="<?php echo date('Y-m-d'); ?>"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                    </div>

                    <!-- Urgence -->
                    <div>
                        <label for="urgency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Niveau d'urgence *</label>
                        <select id="urgency" name="urgency" required
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                            <option value="normal">Normal</option>
                            <option value="urgent">Urgent 🔴 (traitement prioritaire)</option>
                        </select>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Motif *</label>
                        <textarea id="description" name="description" required rows="5"
                                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500"
                                  placeholder="Décrivez la raison de cette demande..."></textarea>
                    </div>

                    <!-- Boutons -->
                    <div class="flex justify-between items-center pt-4">
                        <a href="dashboard.php" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            Annuler
                        </a>
                        <button type="submit" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 shadow-lg font-semibold">
                            Soumettre la demande
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <script src="../assets/js/theme.js"></script>
</body>
</html>