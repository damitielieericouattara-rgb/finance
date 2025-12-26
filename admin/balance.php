<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireAdmin();

$message = '';
$messageType = '';

// 🔴 CORRECTION MAJEURE : AJOUT au solde, pas réinitialisation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_balance'])) {
    $amountToAdd = floatval($_POST['amount_to_add']);
    $notes = cleanInput($_POST['notes']);
    
    // Validation
    if ($amountToAdd <= 0) {
        $message = "Le montant à ajouter doit être supérieur à zéro";
        $messageType = 'error';
    } else {
        try {
            $db = getDB();
            
            // Appeler la procédure d'AJOUT (pas set_manual_balance)
            $stmt = $db->prepare("CALL add_to_balance(?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $amountToAdd, $notes]);
            
            $message = "✅ " . formatAmount($amountToAdd) . " ajouté avec succès au solde global";
            $messageType = 'success';
            
            // Créer notification pour tous les admins
            $adminStmt = $db->prepare("SELECT id FROM users WHERE role_id = 1 AND is_active = 1");
            $adminStmt->execute();
            $admins = $adminStmt->fetchAll();
            
            foreach ($admins as $admin) {
                if ($admin['id'] != $_SESSION['user_id']) {
                    createNotification(
                        $admin['id'],
                        'Solde global mis à jour',
                        $_SESSION['full_name'] . " a ajouté " . formatAmount($amountToAdd) . " au solde global. Raison: " . $notes,
                        'info',
                        null
                    );
                }
            }
            
        } catch (Exception $e) {
            $message = "Erreur lors de l'ajout: " . $e->getMessage();
            $messageType = 'error';
            error_log("Erreur add_balance: " . $e->getMessage());
        }
    }
}

// Récupérer le solde actuel
$currentBalance = getCurrentBalance();

// 🚨 VÉRIFIER SI LE SOLDE EST À ZÉRO
$isBalanceZero = ($currentBalance <= 0);

// Historique des modifications
$db = getDB();
$history = [];
try {
    $historyStmt = $db->query("
        SELECT 
            bh.id,
            bh.transaction_id,
            bh.previous_balance,
            bh.new_balance,
            bh.change_amount,
            bh.change_type,
            bh.notes,
            bh.created_at,
            u.full_name as admin_name
        FROM balance_history bh
        LEFT JOIN users u ON bh.admin_id = u.id
        ORDER BY bh.created_at DESC
        LIMIT 50
    ");
    $history = $historyStmt->fetchAll();
} catch (Exception $e) {
    error_log("Erreur historique balance: " . $e->getMessage());
}

$unreadCount = countUnreadNotifications($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer le solde - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
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
                    <a href="balance.php" class="text-green-600 dark:text-green-400 font-medium">Gérer Solde</a>
                    
                    <button id="themeToggle" class="p-2 text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400">
                        <svg class="h-6 w-6 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <svg class="h-6 w-6 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </button>
                    
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

    <div class="max-w-7xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8 text-gray-900 dark:text-white">💰 Gérer le solde global</h1>
        
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500' : ($messageType === 'warning' ? 'bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500' : 'bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500'); ?>">
                <p class="text-sm <?php echo $messageType === 'success' ? 'text-green-700 dark:text-green-300' : ($messageType === 'warning' ? 'text-yellow-700 dark:text-yellow-300' : 'text-red-700 dark:text-red-300'); ?>">
                    <?php echo $message; ?>
                </p>
            </div>
        <?php endif; ?>

        <?php if ($isBalanceZero): ?>
        <!-- 🚨 ALERTE SOLDE À 0 FCFA -->
        <div class="mb-8 bg-red-500 text-white p-8 rounded-xl shadow-2xl pulse-red">
            <div class="flex items-center mb-4">
                <svg class="h-16 w-16 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <h2 class="text-3xl font-bold">⚠️ ATTENTION : SOLDE À 0 FCFA</h2>
                    <p class="text-xl mt-2">Le solde global est actuellement vide. Veuillez ajouter des fonds pour pouvoir valider les transactions.</p>
                </div>
            </div>
            <div class="bg-white bg-opacity-20 p-4 rounded-lg">
                <p class="font-semibold">📌 Actions bloquées jusqu'à ajout de fonds :</p>
                <ul class="list-disc list-inside mt-2">
                    <li>Validation de transactions de sortie</li>
                    <li>Approbation de demandes de retrait</li>
                    <li>Distribution de fonds aux utilisateurs</li>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Solde actuel -->
            <div class="bg-gradient-to-br from-green-500 to-green-600 p-8 rounded-xl shadow-xl text-white">
                <h2 class="text-2xl font-bold mb-4">Solde actuel</h2>
                <p class="text-6xl font-bold mb-2 <?php echo $isBalanceZero ? 'text-red-200' : ''; ?>">
                    <?php echo formatAmount($currentBalance); ?>
                </p>
                <p class="text-green-100">Dernière mise à jour: <?php echo date('d/m/Y H:i'); ?></p>
                
                <?php if ($isBalanceZero): ?>
                <div class="mt-6 bg-red-600 bg-opacity-50 p-4 rounded-lg">
                    <p class="font-bold">⚠️ Solde vide - Veuillez ajouter des fonds immédiatement</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Formulaire d'AJOUT (pas de remplacement) -->
            <div class="bg-white dark:bg-gray-800 p-8 rounded-xl shadow-xl">
                <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">
                    ➕ Ajouter des fonds
                </h2>
                <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border-l-4 border-blue-500">
                    <p class="text-sm text-blue-700 dark:text-blue-300">
                        <strong>ℹ️ Important :</strong> Le montant entré sera <strong>AJOUTÉ</strong> au solde existant, pas remplacé.
                    </p>
                </div>
                
                <form method="POST">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Montant à AJOUTER (FCFA) *
                            </label>
                            <input type="number" name="amount_to_add" required step="0.01" min="0.01"
                                   class="w-full px-4 py-3 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500"
                                   placeholder="Exemple: 500000">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Nouveau solde = <?php echo formatAmount($currentBalance); ?> + votre montant
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Raison de l'ajout / Notes *
                            </label>
                            <textarea name="notes" required rows="3"
                                      class="w-full px-4 py-3 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500"
                                      placeholder="Exemple: Dépôt espèces du 27/12/2025, Virement bancaire, Fonds de démarrage..."></textarea>
                        </div>

                        <button type="submit" name="add_balance"
                                class="w-full bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700 transition">
                            ➕ Ajouter les fonds au solde
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Historique des modifications -->
        <div class="mt-8 bg-white dark:bg-gray-800 rounded-xl shadow-xl overflow-hidden">
            <div class="p-6 border-b dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">📋 Historique complet des modifications</h3>
            </div>
            
            <?php if (!empty($history)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Date/Heure</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ancien solde</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nouveau solde</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Changement</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Type d'opération</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Responsable</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($history as $h): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-300">
                                    <?php echo formatDateTime($h['created_at']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-300">
                                    <?php echo formatAmount($h['previous_balance']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-300">
                                    <?php echo formatAmount($h['new_balance']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm font-bold <?php echo $h['change_amount'] > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                                    <?php echo ($h['change_amount'] > 0 ? '+' : '') . formatAmount($h['change_amount']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <?php
                                    $typeLabels = [
                                        'manual_add' => '➕ Ajout manuel',
                                        'manual_set' => '🔧 Définition manuelle',
                                        'transaction_validation' => '✅ Validation transaction',
                                        'transaction_rejection' => '❌ Refus transaction'
                                    ];
                                    echo $typeLabels[$h['change_type']] ?? $h['change_type'];
                                    ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-300">
                                    <?php echo htmlspecialchars($h['admin_name'] ?? 'Système'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo htmlspecialchars(substr($h['notes'] ?? '-', 0, 50)); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p>Aucun historique disponible</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/theme.js"></script>
</body>
</html>