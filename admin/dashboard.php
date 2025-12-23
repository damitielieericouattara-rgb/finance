<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

requireAdmin();

$period = $_GET['period'] ?? 'day';
$stats = getDashboardStats($period);
$currentBalance = getCurrentBalance();
$chartData = getChartData('month');

// Récupérer les transactions urgentes en attente
$db = getDB();
$urgentStmt = $db->query("
    SELECT t.*, u.full_name as user_name 
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.status = 'en_attente' AND t.urgency = 'urgent'
    ORDER BY t.created_at ASC
    LIMIT 5
");
$urgentTransactions = $urgentStmt->fetchAll();

// Récupérer les dernières transactions
$recentStmt = $db->query("
    SELECT t.*, u.full_name as user_name 
    FROM transactions t
    LEFT JOIN users u ON t.user_id = u.id
    ORDER BY t.created_at DESC
    LIMIT 10
");
$recentTransactions = $recentStmt->fetchAll();

$unreadCount = countUnreadNotifications($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
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
                    <a href="dashboard.php" class="text-green-600 hover:text-green-700 font-medium">
                        Dashboard
                    </a>
                    <a href="transactions.php" class="text-gray-700 hover:text-green-600">
                        Transactions
                    </a>
                    <a href="users.php" class="text-gray-700 hover:text-green-600">
                        Utilisateurs
                    </a>
                    <a href="reports.php" class="text-gray-700 hover:text-green-600">
                        Rapports
                    </a>
                    
                    <div class="relative">
                        <button id="notificationBtn" class="relative p-2 text-gray-600 hover:text-green-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <?php if ($unreadCount > 0): ?>
                                <span class="absolute top-0 right-0 block h-5 w-5 rounded-full bg-red-500 text-white text-xs flex items-center justify-center">
                                    <?php echo $unreadCount; ?>
                                </span>
                            <?php endif; ?>
                        </button>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-700"><?php echo $_SESSION['full_name']; ?></p>
                            <p class="text-xs text-gray-500">Administrateur</p>
                        </div>
                        <a href="../auth/logout.php" class="text-red-600 hover:text-red-700 p-2" title="Déconnexion">
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
        <!-- En-tête -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Tableau de bord</h1>
            <p class="text-gray-600 mt-1">Vue d'ensemble de l'activité financière</p>
        </div>

        <!-- Alertes urgentes -->
        <?php if (!empty($urgentTransactions)): ?>
            <div class="mb-8 bg-red-50 border-l-4 border-red-500 p-6 rounded-lg animate-pulse">
                <div class="flex items-center mb-4">
                    <svg class="h-6 w-6 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <h3 class="text-lg font-bold text-red-900">Demandes urgentes en attente</h3>
                </div>
                <div class="space-y-3">
                    <?php foreach ($urgentTransactions as $trans): ?>
                        <div class="bg-white p-4 rounded-lg flex justify-between items-center">
                            <div>
                                <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($trans['user_name']); ?></p>
                                <p class="text-sm text-gray-600"><?php echo formatAmount($trans['amount']); ?> - <?php echo htmlspecialchars($trans['description']); ?></p>
                                <p class="text-xs text-gray-500">Il y a <?php echo date_diff(new DateTime($trans['created_at']), new DateTime())->format('%h heures %i min'); ?></p>
                            </div>
                            <a href="transactions.php?id=<?php echo $trans['id']; ?>" class="bg-red-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-red-700">
                                Traiter maintenant
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filtres de période -->
        <div class="flex space-x-2 mb-6">
            <a href="?period=day" class="px-4 py-2 rounded-lg <?php echo $period === 'day' ? 'bg-green-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                Aujourd'hui
            </a>
            <a href="?period=week" class="px-4 py-2 rounded-lg <?php echo $period === 'week' ? 'bg-green-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                7 jours
            </a>
            <a href="?period=month" class="px-4 py-2 rounded-lg <?php echo $period === 'month' ? 'bg-green-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                30 jours
            </a>
        </div>

        <!-- Cartes de statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Solde actuel -->
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-green-500">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-gray-600 text-sm font-medium">Solde Global</h3>
                    <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-gray-900"><?php echo formatAmount($currentBalance); ?></p>
            </div>

            <!-- Total entrées -->
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-blue-500">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-gray-600 text-sm font-medium">Total Entrées</h3>
                    <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-gray-900"><?php echo formatAmount($stats['total_entrees']); ?></p>
                <p class="text-sm text-gray-500 mt-1"><?php echo $stats['validated_count']; ?> transactions validées</p>
            </div>

            <!-- Total sorties -->
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-red-500">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-gray-600 text-sm font-medium">Total Sorties</h3>
                    <svg class="h-8 w-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-gray-900"><?php echo formatAmount($stats['total_sorties']); ?></p>
                <p class="text-sm text-gray-500 mt-1"><?php echo $stats['validated_count']; ?> transactions validées</p>
            </div>

            <!-- En attente -->
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-yellow-500">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-gray-600 text-sm font-medium">En Attente</h3>
                    <svg class="h-8 w-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-gray-900"><?php echo $stats['pending_count']; ?></p>
                <p class="text-sm text-gray-500 mt-1"><?php echo $stats['rejected_count']; ?> refusées</p>
            </div>
        </div>

        <!-- Graphiques -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Graphique des transactions -->
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Évolution des transactions (30 jours)</h3>
                <canvas id="transactionsChart"></canvas>
            </div>

            <!-- Répartition entrées/sorties -->
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Répartition Entrées/Sorties</h3>
                <canvas id="pieChart"></canvas>
            </div>
        </div>

        <!-- Dernières transactions -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-900">Dernières transactions</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilisateur</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($recentTransactions as $trans): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($trans['user_name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $trans['type'] === 'entree' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo getTypeLabel($trans['type']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo formatAmount($trans['amount']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php 
                                    $statusColors = [
                                        'en_attente' => 'bg-yellow-100 text-yellow-800',
                                        'validee' => 'bg-green-100 text-green-800',
                                        'refusee' => 'bg-red-100 text-red-800'
                                    ];
                                    ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $statusColors[$trans['status']]; ?>">
                                        <?php echo getStatusLabel($trans['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo formatDateTime($trans['created_at']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <a href="transactions.php?id=<?php echo $trans['id']; ?>" class="text-green-600 hover:text-green-900 font-medium">
                                        Voir détails
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-4 bg-gray-50 text-center">
                <a href="transactions.php" class="text-green-600 hover:text-green-700 font-medium">
                    Voir toutes les transactions →
                </a>
            </div>
        </div>
    </div>

    <script>
        // Données pour le graphique
        const chartData = <?php echo json_encode($chartData); ?>;
        
        // Préparer les données
        const dates = [...new Set(chartData.map(d => d.date))];
        const entrees = dates.map(date => {
            const item = chartData.find(d => d.date === date && d.type === 'entree');
            return item ? parseFloat(item.total) : 0;
        });
        const sorties = dates.map(date => {
            const item = chartData.find(d => d.date === date && d.type === 'sortie');
            return item ? parseFloat(item.total) : 0;
        });

        // Graphique des transactions
        new Chart(document.getElementById('transactionsChart'), {
            type: 'line',
            data: {
                labels: dates,
                datasets: [
                    {
                        label: 'Entrées',
                        data: entrees,
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Sorties',
                        data: sorties,
                        borderColor: '#EF4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Graphique circulaire
        const totalEntrees = <?php echo $stats['total_entrees']; ?>;
        const totalSorties = <?php echo $stats['total_sorties']; ?>;
        
        new Chart(document.getElementById('pieChart'), {
            type: 'doughnut',
            data: {
                labels: ['Entrées', 'Sorties'],
                datasets: [{
                    data: [totalEntrees, totalSorties],
                    backgroundColor: ['#3B82F6', '#EF4444']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>