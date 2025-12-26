<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

requireLogin();

$userId = $_SESSION['user_id'];

// Statistiques utilisateur
$db = getDB();

$statsStmt = $db->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'en_attente' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'validee' THEN 1 ELSE 0 END) as validated,
        SUM(CASE WHEN status = 'refusee' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN type = 'entree' AND status = 'validee' THEN amount ELSE 0 END) as total_entrees,
        SUM(CASE WHEN type = 'sortie' AND status = 'validee' THEN amount ELSE 0 END) as total_sorties
    FROM transactions
    WHERE user_id = ?
");
$statsStmt->execute([$userId]);
$stats = $statsStmt->fetch();

// Dernières transactions
$recentStmt = $db->prepare("
    SELECT t.*, v.full_name as validator_name
    FROM transactions t
    LEFT JOIN users v ON t.validated_by = v.id
    WHERE t.user_id = ?
    ORDER BY t.created_at DESC
    LIMIT 10
");
$recentStmt->execute([$userId]);
$recentTransactions = $recentStmt->fetchAll();

$unreadCount = countUnreadNotifications($userId);
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Dashboard - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-green-600 font-medium">Dashboard</a>
                    <a href="submit.php" class="text-gray-700 hover:text-green-600">Nouvelle demande</a>
                    <a href="history.php" class="text-gray-700 hover:text-green-600">Historique</a>
                    
                    <div class="relative">
                        <button class="relative p-2 text-gray-600 hover:text-green-600">
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
                            <p class="text-xs text-gray-500">Utilisateur</p>
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
            <h1 class="text-3xl font-bold text-gray-900">Bienvenue, <?php echo $_SESSION['full_name']; ?> 👋</h1>
            <p class="text-gray-600 mt-1">Voici un aperçu de vos transactions</p>
        </div>

        <?php if ($flash): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $flash['type'] === 'success' ? 'bg-green-50 border-l-4 border-green-500' : 'bg-red-50 border-l-4 border-red-500'; ?>">
                <div class="flex">
                    <svg class="h-5 w-5 <?php echo $flash['type'] === 'success' ? 'text-green-400' : 'text-red-400'; ?>" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <p class="ml-3 text-sm <?php echo $flash['type'] === 'success' ? 'text-green-700' : 'text-red-700'; ?>">
                        <?php echo $flash['message']; ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Bouton d'action principal -->
        <div class="mb-8">
            <a href="submit.php" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white font-semibold rounded-lg shadow-lg hover:from-green-700 hover:to-green-800 transform hover:scale-105 transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Nouvelle demande de transaction
            </a>
        </div>

        <!-- Cartes de statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-blue-500">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-gray-600 text-sm font-medium">Total Transactions</h3>
                    <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-gray-900"><?php echo $stats['total']; ?></p>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-yellow-500">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-gray-600 text-sm font-medium">En Attente</h3>
                    <svg class="h-8 w-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-gray-900"><?php echo $stats['pending']; ?></p>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-green-500">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-gray-600 text-sm font-medium">Validées</h3>
                    <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-gray-900"><?php echo $stats['validated']; ?></p>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-red-500">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-gray-600 text-sm font-medium">Refusées</h3>
                    <svg class="h-8 w-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-gray-900"><?php echo $stats['rejected']; ?></p>
            </div>
        </div>

        <!-- Résumé financier -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-6 rounded-xl shadow-lg text-white">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Total Entrées Validées</h3>
                    <svg class="h-10 w-10 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                    </svg>
                </div>
                <p class="text-4xl font-bold"><?php echo formatAmount($stats['total_entrees']); ?></p>
            </div>

            <div class="bg-gradient-to-br from-red-500 to-red-600 p-6 rounded-xl shadow-lg text-white">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Total Sorties Validées</h3>
                    <svg class="h-10 w-10 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                    </svg>
                </div>
                <p class="text-4xl font-bold"><?php echo formatAmount($stats['total_sorties']); ?></p>
            </div>
        </div>

        <!-- Dernières transactions -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-900">Mes dernières transactions</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Urgence</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($recentTransactions as $trans): ?>
                            <tr class="hover:bg-gray-50">
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
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($trans['urgency'] === 'urgent'): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                            Urgent
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                            Normal
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php 
                                    $colors = [
                                        'en_attente' => 'bg-yellow-100 text-yellow-800',
                                        'validee' => 'bg-green-100 text-green-800',
                                        'refusee' => 'bg-red-100 text-red-800'
                                    ];
                                    ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $colors[$trans['status']]; ?>">
                                        <?php echo getStatusLabel($trans['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo formatDateTime($trans['created_at'], 'd/m/Y H:i'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-4 bg-gray-50 text-center">
                <a href="history.php" class="text-green-600 hover:text-green-700 font-medium">
                    Voir tout l'historique →
                </a>
            </div>
        </div>
    </div>
    <script src="../assets/js/theme.js"></script>
</body>
</html>