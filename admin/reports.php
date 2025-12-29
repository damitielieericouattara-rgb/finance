<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireAdmin();

$message = '';
$messageType = '';

// Gérer l'export
if (isset($_POST['export'])) {
    $format = $_POST['format'];
    $period = $_POST['period'];
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    
    // Calculer les dates selon la période
    if ($period === 'week') {
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime('-7 days'));
    } elseif ($period === 'month') {
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime('-30 days'));
    }
    
    // Récupérer les transactions
    $db = getDB();
    $stmt = $db->prepare("
        SELECT t.*, u.full_name as user_name, u.email as user_email,
               v.full_name as validator_name
        FROM transactions t
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN users v ON t.validated_by = v.id
        WHERE t.created_at BETWEEN ? AND ?
        AND t.status = 'validee'
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    $transactions = $stmt->fetchAll();
    
    if ($format === 'csv') {
        // Export CSV
        $filename = 'export_transactions_' . date('Y-m-d_His') . '.csv';
        $filepath = EXPORT_PATH . $filename;
        
        $fp = fopen($filepath, 'w');
        fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
        
        // En-têtes
        fputcsv($fp, [
            'ID',
            'Date',
            'Utilisateur',
            'Email',
            'Type',
            'Montant (FCFA)',
            'Description',
            'Date requise',
            'Validé par',
            'Date validation',
            'Reçu N°'
        ], ';');
        
        // Données
        foreach ($transactions as $t) {
            fputcsv($fp, [
                $t['id'],
                formatDateTime($t['created_at']),
                $t['user_name'],
                $t['user_email'],
                getTypeLabel($t['type']),
                number_format($t['amount'], 0, ',', ' '),
                $t['description'],
                formatDate($t['required_date']),
                $t['validator_name'],
                formatDateTime($t['validated_at']),
                $t['receipt_number']
            ], ';');
        }
        
        fclose($fp);
        
        // Télécharger
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        readfile($filepath);
        exit();
        
    } elseif ($format === 'excel') {
        // Export Excel (HTML avec extension .xls)
        $filename = 'export_transactions_' . date('Y-m-d_His') . '.xls';
        
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head><meta charset="UTF-8"><style>table { border-collapse: collapse; } th, td { border: 1px solid black; padding: 5px; }</style></head>';
        echo '<body>';
        echo '<table>';
        echo '<thead><tr>';
        echo '<th>ID</th><th>Date</th><th>Utilisateur</th><th>Type</th><th>Montant</th><th>Description</th><th>Validé par</th><th>Reçu N°</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($transactions as $t) {
            echo '<tr>';
            echo '<td>' . $t['id'] . '</td>';
            echo '<td>' . formatDateTime($t['created_at']) . '</td>';
            echo '<td>' . htmlspecialchars($t['user_name']) . '</td>';
            echo '<td>' . getTypeLabel($t['type']) . '</td>';
            echo '<td>' . number_format($t['amount'], 0, ',', ' ') . ' FCFA</td>';
            echo '<td>' . htmlspecialchars($t['description']) . '</td>';
            echo '<td>' . htmlspecialchars($t['validator_name']) . '</td>';
            echo '<td>' . htmlspecialchars($t['receipt_number']) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table></body></html>';
        exit();
        
    } elseif ($format === 'pdf') {
        // Export PDF simple
        $filename = 'export_transactions_' . date('Y-m-d_His') . '.pdf';
        
        // Créer un HTML pour conversion PDF
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; }
        h1 { text-align: center; color: #059669; font-size: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #059669; color: white; }
        .total { font-weight: bold; background-color: #f0f0f0; }
    </style>
</head>
<body>
    <h1>RAPPORT DES TRANSACTIONS</h1>
    <p><strong>Période :</strong> Du ' . formatDate($startDate) . ' au ' . formatDate($endDate) . '</p>
    <p><strong>Généré le :</strong> ' . formatDateTime(date('Y-m-d H:i:s')) . '</p>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Utilisateur</th>
                <th>Type</th>
                <th>Montant</th>
                <th>Description</th>
                <th>Reçu N°</th>
            </tr>
        </thead>
        <tbody>';
        
        $totalEntrees = 0;
        $totalSorties = 0;
        
        foreach ($transactions as $t) {
            if ($t['type'] === 'entree') {
                $totalEntrees += $t['amount'];
            } else {
                $totalSorties += $t['amount'];
            }
            
            $html .= '<tr>
                <td>' . $t['id'] . '</td>
                <td>' . formatDate($t['created_at']) . '</td>
                <td>' . htmlspecialchars($t['user_name']) . '</td>
                <td>' . getTypeLabel($t['type']) . '</td>
                <td>' . formatAmount($t['amount']) . '</td>
                <td>' . htmlspecialchars(substr($t['description'], 0, 50)) . '</td>
                <td>' . htmlspecialchars($t['receipt_number']) . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
        <tfoot>
            <tr class="total">
                <td colspan="4">Total Entrées</td>
                <td colspan="3">' . formatAmount($totalEntrees) . '</td>
            </tr>
            <tr class="total">
                <td colspan="4">Total Sorties</td>
                <td colspan="3">' . formatAmount($totalSorties) . '</td>
            </tr>
            <tr class="total">
                <td colspan="4">Solde</td>
                <td colspan="3">' . formatAmount($totalEntrees - $totalSorties) . '</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>';
        
        // Afficher le HTML (en production, utiliser une librairie PDF comme TCPDF)
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        echo '<script>window.print();</script>';
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapports et exports - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation FIXE -->
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
                    <a href="transactions.php" class="text-gray-700 hover:text-green-600">Transactions</a>
                    <a href="users.php" class="text-gray-700 hover:text-green-600">Utilisateurs</a>
                    <a href="reports.php" class="text-green-600 font-medium">Exports</a>
                    
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

    <div class="max-w-4xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8 text-gray-900">📊 Rapports et exports</h1>
        
        <div class="bg-white p-8 rounded-xl shadow-lg">
            <h3 class="text-xl font-semibold mb-6 text-gray-900">Générer un rapport</h3>
            <form method="POST">
                <div class="space-y-6">
                    <!-- Période -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">Période</label>
                        <div class="grid grid-cols-3 gap-4">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="period" value="week" required class="peer sr-only">
                                <div class="border-2 border-gray-300 rounded-lg p-4 text-center peer-checked:border-green-500 peer-checked:bg-green-50 transition">
                                    <svg class="mx-auto h-8 w-8 text-green-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="font-semibold">7 derniers jours</span>
                                </div>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" name="period" value="month" required class="peer sr-only">
                                <div class="border-2 border-gray-300 rounded-lg p-4 text-center peer-checked:border-green-500 peer-checked:bg-green-50 transition">
                                    <svg class="mx-auto h-8 w-8 text-green-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="font-semibold">30 derniers jours</span>
                                </div>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" name="period" value="custom" required class="peer sr-only" onclick="document.getElementById('customDates').classList.remove('hidden')">
                                <div class="border-2 border-gray-300 rounded-lg p-4 text-center peer-checked:border-green-500 peer-checked:bg-green-50 transition">
                                    <svg class="mx-auto h-8 w-8 text-green-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                    </svg>
                                    <span class="font-semibold">Personnalisé</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Dates personnalisées -->
                    <div id="customDates" class="hidden grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date de début</label>
                            <input type="date" name="start_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date de fin</label>
                            <input type="date" name="end_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>

                    <!-- Format -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">Format d'export</label>
                        <div class="grid grid-cols-3 gap-4">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="format" value="pdf" required class="peer sr-only">
                                <div class="border-2 border-gray-300 rounded-lg p-4 text-center peer-checked:border-red-500 peer-checked:bg-red-50 transition">
                                    <span class="text-3xl">📄</span>
                                    <p class="font-semibold mt-2">PDF</p>
                                </div>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" name="format" value="excel" required class="peer sr-only">
                                <div class="border-2 border-gray-300 rounded-lg p-4 text-center peer-checked:border-green-500 peer-checked:bg-green-50 transition">
                                    <span class="text-3xl">📊</span>
                                    <p class="font-semibold mt-2">Excel</p>
                                </div>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" name="format" value="csv" required class="peer sr-only">
                                <div class="border-2 border-gray-300 rounded-lg p-4 text-center peer-checked:border-blue-500 peer-checked:bg-blue-50 transition">
                                    <span class="text-3xl">📋</span>
                                    <p class="font-semibold mt-2">CSV</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <button type="submit" name="export" class="w-full bg-gradient-to-r from-green-600 to-green-700 text-white py-3 px-4 rounded-lg font-semibold hover:from-green-700 hover:to-green-800 transition">
                        🚀 Générer et télécharger le rapport
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script src="../assets/js/theme.js"></script>
</body>
</html>