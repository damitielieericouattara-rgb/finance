<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireAdmin();

$message = '';
$messageType = '';

// Addition cumulative au lieu de réinitialisation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_balance'])) {
    $amountToAdd = floatval($_POST['amount_to_add']);
    $notes = cleanInput($_POST['notes']);
    
    if ($amountToAdd <= 0) {
        $message = "Le montant doit être supérieur à zéro";
        $messageType = 'error';
    } else {
        try {
            $db = getDB();
            
            // Récupérer le solde actuel
            $currentBalance = getCurrentBalance();
            
            // CALCUL CUMULATIF : Addition au lieu de réinitialisation
            $newBalance = $currentBalance + $amountToAdd;
            
            // Mettre à jour le solde
            $stmt = $db->prepare("
                UPDATE global_balance 
                SET balance = ?, updated_by = ?, last_updated = NOW() 
                WHERE id = 1
            ");
            $stmt->execute([$newBalance, $_SESSION['user_id']]);
            
            // Enregistrer dans l'historique
            $histStmt = $db->prepare("
                INSERT INTO balance_history 
                (previous_balance, new_balance, change_amount, change_type, admin_id, notes)
                VALUES (?, ?, ?, 'manual_add', ?, ?)
            ");
            $histStmt->execute([
                $currentBalance, 
                $newBalance, 
                $amountToAdd, 
                $_SESSION['user_id'], 
                $notes
            ]);
            
            logActivity($_SESSION['user_id'], 'BALANCE_ADDED', 'global_balance', 1, 
                "Ajout de " . formatAmount($amountToAdd) . " au solde. Ancien: " . formatAmount($currentBalance) . ", Nouveau: " . formatAmount($newBalance));
            
            $message = "✅ Solde mis à jour avec succès ! Ancien solde: " . formatAmount($currentBalance) . " + " . formatAmount($amountToAdd) . " = Nouveau solde: " . formatAmount($newBalance);
            $messageType = 'success';
            
        } catch (Exception $e) {
            $message = "Erreur lors de la mise à jour: " . $e->getMessage();
            $messageType = 'error';
            error_log("Erreur balance: " . $e->getMessage());
        }
    }
}

$currentBalance = getCurrentBalance();

// Historique
$db = getDB();
$history = [];
try {
    $historyStmt = $db->query("
        SELECT 
            bh.*,
            u.full_name as admin_name
        FROM balance_history bh
        LEFT JOIN users u ON bh.admin_id = u.id
        ORDER BY bh.created_at DESC
        LIMIT 20
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
                    <a href="balance.php" class="text-green-600 dark:text-green-400 font-medium">Gérer Solde</a>
                    
                    <a href="notifications.php" class="relative p-2 text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400 transition-colors" title="Voir les notifications">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <?php if ($unreadCount > 0): ?>
                            <span class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center animate-pulse font-bold">
                                <?php echo $unreadCount; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    
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

    <div class="max-w-7xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8 text-gray-900 dark:text-white">💰 Gérer le solde global</h1>
        
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500' : 'bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500'; ?>">
                <p class="text-sm <?php echo $messageType === 'success' ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'; ?>">
                    <?php echo $message; ?>
                </p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Solde actuel -->
            <div class="bg-gradient-to-br from-green-500 to-green-600 p-8 rounded-xl shadow-xl text-white">
                <h2 class="text-2xl font-bold mb-4">Solde actuel</h2>
                <p class="text-6xl font-bold mb-2"><?php echo formatAmount($currentBalance); ?></p>
                <p class="text-green-100">Dernière mise à jour: <?php echo date('d/m/Y H:i'); ?></p>
            </div>

            <!-- Formulaire d'ajout (CUMULATIVE) -->
            <div class="bg-white dark:bg-gray-800 p-8 rounded-xl shadow-xl">
                <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">✅ Ajouter des fonds (Addition cumulative)</h2>
                <form method="POST">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Montant à ajouter (FCFA)
                            </label>
                            <input type="number" name="amount_to_add" required step="0.01" min="0.01"
                                   class="w-full px-4 py-3 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 text-2xl font-bold"
                                   placeholder="50000">
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                💡 Ce montant sera <strong>AJOUTÉ</strong> au solde actuel de <?php echo formatAmount($currentBalance); ?>
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Notes / Raison de l'ajout
                            </label>
                            <textarea name="notes" required rows="3"
                                      class="w-full px-4 py-3 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500"
                                      placeholder="Exemple: Dépôt banque, Apport personnel..."></textarea>
                        </div>

                        <!-- Calcul en temps réel -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-700">
                            <p class="text-sm text-blue-800 dark:text-blue-200 font-medium">Calcul :</p>
                            <p class="text-lg text-blue-900 dark:text-blue-100 mt-2">
                                <?php echo formatAmount($currentBalance); ?> 
                                <span class="text-green-600 dark:text-green-400 font-bold">+</span> 
                                <span id="amountDisplay">0 FCFA</span>
                                <span class="text-green-600 dark:text-green-400 font-bold">=</span> 
                                <strong id="newBalanceDisplay"><?php echo formatAmount($currentBalance); ?></strong>
                            </p>
                        </div>

                        <button type="submit" name="add_balance"
                                class="w-full bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700 transition">
                                ➕ Ajouter au solde
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Historique -->
        <div class="mt-8 bg-white dark:bg-gray-800 rounded-xl shadow-xl overflow-hidden">
            <div class="p-6 border-b dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Historique des modifications</h3>
            </div>
            
            <?php if (!empty($history)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ancien solde</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nouveau solde</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Changement</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Administrateur</th>
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
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-300">
                                    <?php 
                                    $types = [
                                        'manual_add' => '➕ Ajout manuel',
                                        'manual_set' => '⚙️ Définition manuelle',
                                        'transaction_validation' => '✅ Transaction'
                                    ];
                                    echo $types[$h['change_type']] ?? $h['change_type']; 
                                    ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-300">
                                    <?php echo htmlspecialchars($h['admin_name'] ?? 'Système'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo htmlspecialchars($h['notes'] ?? '-'); ?>
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
    <script>
    // Calcul en temps réel du nouveau solde
    const amountInput = document.querySelector('input[name="amount_to_add"]');
    const currentBalance = <?php echo $currentBalance; ?>;
    
    if (amountInput) {
        amountInput.addEventListener('input', function() {
            const amount = parseFloat(this.value) || 0;
            const newBalance = currentBalance + amount;
            
            document.getElementById('amountDisplay').textContent = amount.toLocaleString('fr-FR') + ' FCFA';
            document.getElementById('newBalanceDisplay').textContent = newBalance.toLocaleString('fr-FR') + ' FCFA';
        });
    }
    </script>
</body>
</html>