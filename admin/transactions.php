<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

requireAdmin();

// Traiter les actions de validation/refus
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['transaction_id'])) {
        $transactionId = intval($_POST['transaction_id']);
        $action = $_POST['action'];
        $comment = cleanInput($_POST['comment'] ?? '');
        
        if ($action === 'validate') {
            if (validateTransaction($transactionId, $_SESSION['user_id'], $comment)) {
                setFlashMessage('success', 'Transaction validée avec succès');
            } else {
                setFlashMessage('error', 'Erreur lors de la validation');
            }
        } elseif ($action === 'reject') {
            if (empty($comment)) {
                setFlashMessage('error', 'Un motif de refus est requis');
            } elseif (rejectTransaction($transactionId, $_SESSION['user_id'], $comment)) {
                setFlashMessage('success', 'Transaction refusée');
            } else {
                setFlashMessage('error', 'Erreur lors du refus');
            }
        }
        
        header('Location: transactions.php');
        exit();
    }
}

// Filtres
$filters = [
    'status' => $_GET['status'] ?? '',
    'type' => $_GET['type'] ?? '',
    'urgency' => $_GET['urgency'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

$transactions = getTransactions($filters, $page, $perPage);
$totalTransactions = countTransactions($filters);
$totalPages = ceil($totalTransactions / $perPage);

$flash = getFlashMessage();
$unreadCount = countUnreadNotifications($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des transactions - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="ml-3 text-xl font-bold text-green-600"><?php echo SITE_NAME; ?></span>
                    <span class="ml-4 px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">ADMIN</span>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 hover:text-green-600">Dashboard</a>
                    <a href="transactions.php" class="text-green-600 font-medium">Transactions</a>
                    <a href="users.php" class="text-gray-700 hover:text-green-600">Utilisateurs</a>
                    <a href="reports.php" class="text-gray-700 hover:text-green-600">Rapports</a>
                    <a href="balance.php" class="text-gray-700 hover:text-green-600">Solde</a>
                    
                    <div class="flex items-center space-x-2">
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-700"><?php echo $_SESSION['full_name']; ?></p>
                            <p class="text-xs text-gray-500">Administrateur</p>
                        </div>
                        <a href="../auth/logout.php" class="text-red-600 hover:text-red-700 p-2">
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
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Gestion des transactions</h1>
            <p class="text-gray-600 mt-1">Validez ou refusez les demandes de transaction</p>
        </div>

        <?php if ($flash): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $flash['type'] === 'success' ? 'bg-green-50 border-l-4 border-green-500' : 'bg-red-50 border-l-4 border-red-500'; ?>">
                <p class="text-sm <?php echo $flash['type'] === 'success' ? 'text-green-700' : 'text-red-700'; ?>">
                    <?php echo $flash['message']; ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Filtres -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Filtrer les transactions</h3>
            <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        <option value="">Tous</option>
                        <option value="en_attente" <?php echo $filters['status'] === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                        <option value="validee" <?php echo $filters['status'] === 'validee' ? 'selected' : ''; ?>>Validée</option>
                        <option value="refusee" <?php echo $filters['status'] === 'refusee' ? 'selected' : ''; ?>>Refusée</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                    <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        <option value="">Tous</option>
                        <option value="entree" <?php echo $filters['type'] === 'entree' ? 'selected' : ''; ?>>Entrée</option>
                        <option value="sortie" <?php echo $filters['type'] === 'sortie' ? 'selected' : ''; ?>>Sortie</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Urgence</label>
                    <select name="urgency" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        <option value="">Tous</option>
                        <option value="normal" <?php echo $filters['urgency'] === 'normal' ? 'selected' : ''; ?>>Normal</option>
                        <option value="urgent" <?php echo $filters['urgency'] === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Du</label>
                    <input type="date" name="date_from" value="<?php echo $filters['date_from']; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Au</label>
                    <input type="date" name="date_to" value="<?php echo $filters['date_to']; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                
                <div class="md:col-span-5 flex justify-end space-x-2">
                    <a href="transactions.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Réinitialiser
                    </a>
                    <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        Filtrer
                    </button>
                </div>
            </form>
        </div>

        <!-- Liste des transactions -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilisateur</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date requise</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Urgence</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                                Aucune transaction trouvée
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($transactions as $trans): ?>
                            <tr class="hover:bg-gray-50 <?php echo $trans['urgency'] === 'urgent' && $trans['status'] === 'en_attente' ? 'bg-red-50' : ''; ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #<?php echo $trans['id']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($trans['user_name']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($trans['user_email']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $trans['type'] === 'entree' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo getTypeLabel($trans['type']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                    <?php echo formatAmount($trans['amount']); ?>
                                </td>
                                <td class="px-6 py-4 max-w-xs">
                                    <p class="text-sm text-gray-900 truncate"><?php echo htmlspecialchars($trans['description']); ?></p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <?php echo formatDate($trans['required_date']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($trans['urgency'] === 'urgent'): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 animate-pulse">
                                            🔴 URGENT
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                            Normal
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php echo getStatusBadge($trans['status']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php if ($trans['status'] === 'en_attente'): ?>
                                        <button onclick="openModal(<?php echo htmlspecialchars(json_encode($trans)); ?>)" 
                                                class="bg-green-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-700">
                                            Traiter
                                        </button>
                                    <?php elseif ($trans['status'] === 'validee'): ?>
                                        <a href="../pdf/receipt.php?id=<?php echo $trans['id']; ?>" target="_blank"
                                           class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 inline-block">
                                            📄 Reçu
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-500">Refusée</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="bg-gray-50 px-4 py-3 flex items-center justify-between border-t border-gray-200">
                    <div>
                        <p class="text-sm text-gray-700">
                            Affichage de <span class="font-medium"><?php echo (($page - 1) * $perPage) + 1; ?></span> à 
                            <span class="font-medium"><?php echo min($page * $perPage, $totalTransactions); ?></span> sur 
                            <span class="font-medium"><?php echo $totalTransactions; ?></span> résultats
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>&<?php echo http_build_query($filters); ?>" 
                                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium <?php echo $i === $page ? 'bg-green-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </nav>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal de traitement -->
    <div id="actionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Traiter la transaction</h3>
                <div id="modalContent"></div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button onclick="closeModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Annuler
                    </button>
                    <button onclick="submitAction('reject')" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold">
                        ❌ Refuser
                    </button>
                    <button onclick="submitAction('validate')" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                        ✅ Valider
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentTransaction = null;

        function openModal(transaction) {
            currentTransaction = transaction;
            const content = `
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <label class="text-xs font-medium text-gray-500 uppercase">Utilisateur</label>
                            <p class="text-sm font-semibold text-gray-900 mt-1">${transaction.user_name}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <label class="text-xs font-medium text-gray-500 uppercase">Type</label>
                            <p class="text-sm font-semibold text-gray-900 mt-1">${transaction.type === 'entree' ? '📈 Entrée' : '📉 Sortie'}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <label class="text-xs font-medium text-gray-500 uppercase">Montant</label>
                            <p class="text-lg font-bold text-gray-900 mt-1">${parseFloat(transaction.amount).toLocaleString('fr-FR')} FCFA</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <label class="text-xs font-medium text-gray-500 uppercase">Date requise</label>
                            <p class="text-sm font-semibold text-gray-900 mt-1">${new Date(transaction.required_date).toLocaleDateString('fr-FR')}</p>
                        </div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <label class="text-xs font-medium text-gray-500 uppercase">Description / Motif</label>
                        <p class="text-sm text-gray-900 mt-2">${transaction.description}</p>
                    </div>
                    <div>
                        <label for="modalComment" class="block text-sm font-medium text-gray-700 mb-2">
                            Commentaire ${transaction.urgency === 'urgent' ? '(requis pour refus)' : '(optionnel)'}
                        </label>
                        <textarea id="modalComment" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500" placeholder="Ajoutez un commentaire..."></textarea>
                    </div>
                </div>
            `;
            document.getElementById('modalContent').innerHTML = content;
            document.getElementById('actionModal').classList.remove('hidden');
            document.getElementById('actionModal').classList.add('flex');
        }

        function closeModal() {
            document.getElementById('actionModal').classList.add('hidden');
            document.getElementById('actionModal').classList.remove('flex');
            currentTransaction = null;
        }

        function submitAction(action) {
            if (!currentTransaction) return;
            
            const comment = document.getElementById('modalComment').value;
            
            if (action === 'reject' && !comment.trim()) {
                alert('Un motif de refus est requis');
                return;
            }
            
            if (action === 'validate' && !confirm('Confirmer la validation de cette transaction ?')) {
                return;
            }
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="transaction_id" value="${currentTransaction.id}">
                <input type="hidden" name="action" value="${action}">
                <input type="hidden" name="comment" value="${comment}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    </script>
    <script src="../assets/js/hamburger.js"></script>
</body>
</html>