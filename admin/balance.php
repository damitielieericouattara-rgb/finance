<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireAdmin();

$message = '';
$messageType = '';

// Traiter la mise à jour du solde
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_balance'])) {
    $newBalance = floatval($_POST['new_balance']);
    $notes = cleanInput($_POST['notes']);
    
    try {
        $db = getDB();
        $stmt = $db->prepare("CALL set_manual_balance(?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $newBalance, $notes]);
        
        $message = "Solde mis à jour avec succès à " . formatAmount($newBalance);
        $messageType = 'success';
    } catch (Exception $e) {
        $message = "Erreur lors de la mise à jour: " . $e->getMessage();
        $messageType = 'error';
    }
}

$currentBalance = getCurrentBalance();

// Historique des modifications
$db = getDB();
$historyStmt = $db->query("
    SELECT bh.*, u.full_name as admin_name
    FROM balance_history bh
    LEFT JOIN users u ON bh.admin_id = u.id
    ORDER BY bh.created_at DESC
    LIMIT 20
");
$history = $historyStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer le solde - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <!-- Navigation identique -->
    <nav class="bg-white dark:bg-gray-800 shadow-lg sticky top-0 z-50">
        <!-- ... Navigation complète ... -->
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

            <!-- Formulaire de mise à jour -->
            <div class="bg-white dark:bg-gray-800 p-8 rounded-xl shadow-xl">
                <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Mettre à jour le solde</h2>
                <form method="POST">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nouveau solde (FCFA)
                            </label>
                            <input type="number" name="new_balance" required step="0.01"
                                   class="w-full px-4 py-3 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500"
                                   placeholder="<?php echo $currentBalance; ?>">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Notes / Raison de la modification
                            </label>
                            <textarea name="notes" required rows="3"
                                      class="w-full px-4 py-3 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500"
                                      placeholder="Exemple: Ajustement après inventaire physique..."></textarea>
                        </div>

                        <button type="submit" name="update_balance"
                                class="w-full bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700">
                            💾 Enregistrer le nouveau solde
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
                                    <?php echo $h['change_type'] === 'manual_set' ? 'Manuel' : 'Transaction'; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-300">
                                    <?php echo htmlspecialchars($h['admin_name'] ?? 'Système'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="../assets/js/theme.js"></script>
</body>
</html>