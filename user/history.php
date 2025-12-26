<?php
define('APP_ROOT', dirname(__DIR__));
define('APP_ENV', 'production');
require_once '../includes/config.php';

if (!isLoggedIn() || isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$user = getCurrentUser();

// Filtres
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';

$filters = ['user_id' => $user['id']];
if ($status) $filters['status'] = $status;
if ($type) $filters['type'] = $type;

$transactions = getTransactions($pdo, $filters);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique - <?php echo APP_NAME; ?></title>
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
                        <a href="dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            Tableau de bord
                        </a>
                        <a href="submit.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            Nouvelle demande
                        </a>
                        <a href="history.php" class="px-3 py-2 rounded-md text-sm font-medium bg-primary text-white">
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
                            <span data-notification-badge class="hidden absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full h-5 w-5 items-center justify-center">0</span>
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
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Historique des transactions</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Consultez toutes vos demandes
            </p>
        </div>

        <!-- Filtres -->
        <div class="px-4 mb-6">
            <form method="GET" class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Statut</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">Tous</option>
                            <option value="en_attente" <?php echo $status === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                            <option value="validee" <?php echo $status === 'validee' ? 'selected' : ''; ?>>Validée</option>
                            <option value="rejetee" <?php echo $status === 'rejetee' ? 'selected' : ''; ?>>Rejetée</option>
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
                        <button type="submit" class="w-full px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-600">
                            Filtrer
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Liste des transactions -->
        <div class="px-4">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date création</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Montant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Motif</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                Aucune transaction trouvée. <a href="submit.php" class="text-primary hover:underline">Créer une demande</a>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $trans): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?php echo formatDateTime($trans['created_at']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $trans['type'] === 'entree' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'; ?>">
                                        <?php echo ucfirst($trans['type']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">
                                    <?php echo formatAmount($trans['amount']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 max-w-xs truncate">
                                    <?php echo escape($trans['reason']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php echo getStatusBadge($trans['status']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php if ($trans['status'] === 'validee' && $trans['receipt_number']): ?>
                                    <a href="../pdf/receipt.php?id=<?php echo $trans['id']; ?>" 
                                       target="_blank"
                                       class="inline-flex items-center px-3 py-1 bg-primary text-white rounded hover:bg-blue-600 text-xs font-medium">
                                        <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        Reçu
                                    </a>
                                    <?php elseif ($trans['status'] === 'rejetee' && $trans['admin_notes']): ?>
                                    <button onclick="showNotes('<?php echo addslashes($trans['admin_notes']); ?>')" 
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
        </div>
    </main>

    <!-- Modal raison rejet -->
    <div id="notesModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Raison du rejet</h3>
            <p id="notesContent" class="text-gray-600 dark:text-gray-400 mb-6"></p>
            <button onclick="closeNotesModal()" class="w-full px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-600">
                Fermer
            </button>
        </div>
    </div>

    <script src="../assets/js/theme.js"></script>
    <script src="../assets/js/notifications_system.js"></script>
    <script>
        if (window.NotificationManager) {
            window.NotificationManager.init(<?php echo $user['id']; ?>);
        }

        function showNotes(notes) {
            document.getElementById('notesContent').textContent = notes;
            document.getElementById('notesModal').classList.remove('hidden');
        }

        function closeNotesModal() {
            document.getElementById('notesModal').classList.add('hidden');
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeNotesModal();
        });
    </script>
</body>
</html>