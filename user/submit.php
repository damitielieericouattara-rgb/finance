<?php
define('APP_ROOT', dirname(__DIR__));
define('APP_ENV', 'production');
require_once '../includes/config.php';

if (!isLoggedIn() || isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$user = getCurrentUser();
$message = '';
$messageType = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $type = $_POST['type'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    $requestDate = $_POST['request_date'] ?? '';
    $urgency = $_POST['urgency'] ?? 'normal';
    
    if (empty($type) || $amount <= 0 || empty($reason) || empty($requestDate)) {
        $message = 'Tous les champs sont requis et le montant doit être positif.';
        $messageType = 'error';
    } elseif (!in_array($type, ['entree', 'sortie'])) {
        $message = 'Type de transaction invalide.';
        $messageType = 'error';
    } else {
        $result = createTransaction($pdo, [
            'user_id' => $user['id'],
            'type' => $type,
            'amount' => $amount,
            'reason' => $reason,
            'request_date' => $requestDate,
            'urgency' => $urgency
        ]);
        
        if ($result['success']) {
            $message = 'Demande soumise avec succès. Les administrateurs ont été notifiés.';
            $messageType = 'success';
            
            // Rediriger après 2 secondes
            header('Refresh: 2; url=dashboard.php');
        } else {
            $message = $result['message'];
            $messageType = 'error';
        }
    }
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle demande - <?php echo APP_NAME; ?></title>
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
                        <a href="submit.php" class="px-3 py-2 rounded-md text-sm font-medium bg-primary text-white">
                            Nouvelle demande
                        </a>
                        <a href="history.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
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

    <main class="max-w-3xl mx-auto py-6 sm:px-6 lg:px-8">
        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700' : 'bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700'; ?>">
            <p class="text-sm <?php echo $messageType === 'success' ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'; ?>">
                <?php echo escape($message); ?>
            </p>
        </div>
        <?php endif; ?>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Nouvelle demande</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                Remplissez le formulaire ci-dessous pour soumettre une demande de transaction
            </p>

            <form method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                <!-- Type de transaction -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Type de transaction <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="relative flex items-center p-4 border-2 border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-primary hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all">
                            <input type="radio" name="type" value="entree" required class="sr-only peer">
                            <div class="flex items-center w-full">
                                <svg class="h-8 w-8 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white">Entrée</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Ajout de fonds</div>
                                </div>
                            </div>
                            <div class="absolute inset-0 border-2 border-transparent peer-checked:border-primary rounded-lg pointer-events-none"></div>
                        </label>

                        <label class="relative flex items-center p-4 border-2 border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-primary hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all">
                            <input type="radio" name="type" value="sortie" required class="sr-only peer">
                            <div class="flex items-center w-full">
                                <svg class="h-8 w-8 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                </svg>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white">Sortie</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Retrait de fonds</div>
                                </div>
                            </div>
                            <div class="absolute inset-0 border-2 border-transparent peer-checked:border-primary rounded-lg pointer-events-none"></div>
                        </label>
                    </div>
                </div>

                <!-- Montant -->
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Montant (FCFA) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           id="amount" 
                           name="amount" 
                           required 
                           min="1" 
                           step="1"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-lg font-semibold"
                           placeholder="50000">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Entrez le montant en Francs CFA</p>
                </div>

                <!-- Date de la demande -->
                <div>
                    <label for="request_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Date souhaitée <span class="text-red-500">*</span>
                    </label>
                    <input type="date" 
                           id="request_date" 
                           name="request_date" 
                           required
                           min="<?php echo date('Y-m-d'); ?>"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Date à laquelle vous souhaitez effectuer la transaction</p>
                </div>

                <!-- Urgence -->
                <div>
                    <label for="urgency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Niveau d'urgence <span class="text-red-500">*</span>
                    </label>
                    <select id="urgency" 
                            name="urgency" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="normal">Normal</option>
                        <option value="urgent">Urgent (traitement prioritaire)</option>
                    </select>
                </div>

                <!-- Motif -->
                <div>
                    <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Motif de la transaction <span class="text-red-500">*</span>
                    </label>
                    <textarea id="reason" 
                              name="reason" 
                              required 
                              rows="5"
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                              placeholder="Décrivez la raison de cette demande..."></textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Soyez précis pour faciliter le traitement de votre demande</p>
                </div>

                <!-- Boutons -->
                <div class="flex justify-between items-center pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="dashboard.php" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        Annuler
                    </a>
                    <button type="submit" class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-blue-600 shadow-lg hover:shadow-xl transition-all font-semibold">
                        Soumettre la demande
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script src="../assets/js/theme.js"></script>
    <script src="../assets/js/notifications_system.js"></script>
    <script>
        if (window.NotificationManager) {
            window.NotificationManager.init(<?php echo $user['id']; ?>);
        }

        // Validation du formulaire côté client
        document.querySelector('form').addEventListener('submit', function(e) {
            const amount = parseFloat(document.getElementById('amount').value);
            const reason = document.getElementById('reason').value.trim();
            
            if (amount <= 0) {
                e.preventDefault();
                alert('Le montant doit être supérieur à 0.');
                return false;
            }
            
            if (reason.length < 10) {
                e.preventDefault();
                alert('Le motif doit contenir au moins 10 caractères.');
                return false;
            }
        });
    </script>
</body>
</html>