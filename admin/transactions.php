<?php
define('APP_ROOT', dirname(__DIR__));
define('APP_ENV', 'production');
require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$user = getCurrentUser();
$message = '';
$messageType = '';

// Traitement de validation/rejet
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transactionId = $_POST['transaction_id'] ?? null;
    $action = $_POST['action'] ?? null;
    $notes = trim($_POST['notes'] ?? '');
    
    if ($transactionId && $action) {
        if ($action === 'validate') {
            $result = validateTransaction($pdo, $transactionId, $user['id'], $notes);
            if ($result['success']) {
                $message = 'Transaction validée avec succès.';
                $messageType = 'success';
            } else {
                $message = $result['message'];
                $messageType = 'error';
            }
        } elseif ($action === 'reject') {
            $result = rejectTransaction($pdo, $transactionId, $user['id'], $notes);
            if ($result['success']) {
                $message = 'Transaction rejetée.';
                $messageType = 'success';
            } else {
                $message = $result['message'];
                $messageType = 'error';
            }
        }
    }
}

// Filtres
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';

$filters = [];
if ($status) $filters['status'] = $status;
if ($type) $filters['type'] = $type;
if ($search) $filters['search'] = $search;

$transactions = getTransactions($pdo, $filters);
$currentBalance = getCurrentBalance($pdo);
$isBalanceZero = isBalanceZero($pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Transactions - <?php echo APP_NAME; ?></title>
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
    <style>
        @keyframes pulse-red {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .pulse-red {
            animation: pulse-red 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
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
                        <span class="ml-2 text-xl font-bold text-gray-900 dark:text-white">Admin</span>
                    </div>
                    <div class="hidden md:ml-6 md:flex md:space-x-4">
                        <a href="dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            Tableau de bord
                        </a>
                        <a href="transactions.php" class="px-3 py-2 rounded-md text-sm font-medium bg-primary text-white">
                            Transactions
                        </a>
                        <a href="balance.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            Solde Global
                        </a>
                        <a href="users.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            Utilisateurs
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
        <?php if ($isBalanceZero): ?>
        <div class="mb-6 bg-red-50 dark:bg-red-900/30 border-2 border-red-500 rounded-lg p-4 pulse-red">
            <div class="flex items-center">
                <svg class="h-6 w-6 text-red-600 dark:text-red-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-red-800 dark:text-red-200">⚠️ ALERTE : Solde à 0 FCFA</h3>
                    <p class="text-sm text-red-700 dark:text-red-300 mt-1">
                        Impossible de valider des transactions de sortie. <a href="balance.php" class="underline font-semibold">Ajoutez des fonds</a>
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700' : 'bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700'; ?>">
            <p class="text-sm <?php echo $messageType === 'success' ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'; ?>">
                <?php echo escape($message); ?>
            </p>
        </div>
        <?php endif; ?>

        <div class="px-4 py-5 sm:px-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Gestion des Transactions</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Solde actuel : <span class="font-bold"><?php echo formatAmount($currentBalance); ?></span>
            </p>
        </div>

        <!-- Filtres -->
        <div class="px-4 mb-6">
            <form method="GET" class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Recherche</label>
                        <input type="text" name="search" value="<?php echo escape($search); ?>" 
                               placeholder="Utilisateur, motif..." 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
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
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Utilisateur</th>
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
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                    Aucune transaction trouvée
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $trans): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        <?php echo formatDateTime($trans['created_at']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        <?php echo escape($trans['user_full_name']); ?>
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
                                        <?php echo escape(substr($trans['reason'], 0, 50)) . (strlen($trans['reason']) > 50 ? '...' : ''); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo getStatusBadge($trans['status']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?php if ($trans['status'] === 'en_attente'): ?>
                                        <button onclick="openModal(<?php echo $trans['id']; ?>, 'validate')" 
                                                class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 mr-3">
                                            Valider
                                        </button>
                                        <button onclick="openModal(<?php echo $trans['id']; ?>, 'reject')" 
                                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                            Rejeter
                                        </button>
                                        <?php elseif ($trans['status'] === 'validee' && $trans['receipt_number']): ?>
                                        <a href="../pdf/receipt.php?id=<?php echo $trans['id']; ?>" 
                                           target="_blank"
                                           class="text-primary hover:text-blue-600 dark:hover:text-blue-400">
                                            Voir reçu
                                        </a>
                                        <?php else: ?>
                                        <span class="text-gray-400 dark:text-gray-600">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de confirmation -->
    <div id="actionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
            <h3 id="modalTitle" class="text-lg font-bold text-gray-900 dark:text-white mb-4"></h3>
            <form id="actionForm" method="POST">
                <input type="hidden" name="transaction_id" id="modalTransactionId">
                <input type="hidden" name="action" id="modalAction">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Notes (optionnel)
                    </label>
                    <textarea name="notes" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                              placeholder="Raison de la validation/rejet..."></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()" 
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        Annuler
                    </button>
                    <button type="submit" id="modalSubmit"
                            class="px-4 py-2 rounded-lg text-white">
                        Confirmer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/theme.js"></script>
    <script src="../assets/js/notifications_system.js"></script>
    <script>
        if (window.NotificationManager) {
            window.NotificationManager.init(<?php echo $user['id']; ?>);
        }

        function openModal(transactionId, action) {
            const modal = document.getElementById('actionModal');
            const title = document.getElementById('modalTitle');
            const submitBtn = document.getElementById('modalSubmit');
            
            document.getElementById('modalTransactionId').value = transactionId;
            document.getElementById('modalAction').value = action;
            
            if (action === 'validate') {
                title.textContent = 'Valider la transaction';
                submitBtn.textContent = 'Valider';
                submitBtn.className = 'px-4 py-2 rounded-lg text-white bg-green-600 hover:bg-green-700';
            } else {
                title.textContent = 'Rejeter la transaction';
                submitBtn.textContent = 'Rejeter';
                submitBtn.className = 'px-4 py-2 rounded-lg text-white bg-red-600 hover:bg-red-700';
            }
            
            modal.classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('actionModal').classList.add('hidden');
            document.getElementById('actionForm').reset();
        }

        // Fermer avec Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
    </script>
</body>
</html>