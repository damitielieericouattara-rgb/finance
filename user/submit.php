<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

requireLogin();

$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    $description = cleanInput($_POST['description'] ?? '');
    $requiredDate = $_POST['required_date'] ?? '';
    $urgency = $_POST['urgency'] ?? 'normal';
    
    // Validation
    if (!in_array($type, ['entree', 'sortie'])) {
        $errors[] = "Type de transaction invalide";
    }
    
    if ($amount <= 0) {
        $errors[] = "Le montant doit être supérieur à zéro";
    }
    
    if (empty($description)) {
        $errors[] = "La description est requise";
    }
    
    if (empty($requiredDate)) {
        $errors[] = "La date est requise";
    } else if (strtotime($requiredDate) < strtotime('today')) {
        $errors[] = "La date ne peut pas être dans le passé";
    }
    
    if (!in_array($urgency, ['normal', 'urgent'])) {
        $errors[] = "Niveau d'urgence invalide";
    }
    
    if (empty($errors)) {
        $transactionId = createTransaction(
            $_SESSION['user_id'],
            $type,
            $amount,
            $description,
            $requiredDate,
            $urgency
        );
        
        if ($transactionId) {
            $success = true;
            setFlashMessage('success', 'Votre demande a été soumise avec succès');
            
            // Rediriger après 2 secondes
            header("refresh:2;url=dashboard.php");
        } else {
            $errors[] = "Une erreur est survenue lors de la soumission";
        }
    }
}

$unreadCount = countUnreadNotifications($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle demande - <?php echo SITE_NAME; ?></title>
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
                    <a href="dashboard.php" class="text-gray-700 hover:text-green-600">
                        Dashboard
                    </a>
                    <a href="submit.php" class="text-green-600 font-medium">
                        Nouvelle demande
                    </a>
                    <a href="history.php" class="text-gray-700 hover:text-green-600">
                        Historique
                    </a>
                    
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

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Nouvelle demande de transaction</h1>
            <p class="text-gray-600 mt-1">Remplissez le formulaire ci-dessous pour soumettre votre demande</p>
        </div>

        <?php if ($success): ?>
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-6 rounded-lg">
                <div class="flex">
                    <svg class="h-6 w-6 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <div class="ml-3">
                        <p class="text-sm text-green-700 font-medium">Demande soumise avec succès !</p>
                        <p class="text-sm text-green-600 mt-1">Vous serez notifié dès qu'un administrateur traitera votre demande. Redirection en cours...</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div class="ml-3">
                        <?php foreach ($errors as $error): ?>
                            <p class="text-sm text-red-700"><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-lg p-8">
            <form method="POST" action="" class="<?php echo $success ? 'opacity-50 pointer-events-none' : ''; ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Type de transaction -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Type de transaction *
                        </label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="type" value="entree" required
                                       class="peer sr-only"
                                       <?php echo (($_POST['type'] ?? '') === 'entree') ? 'checked' : ''; ?>>
                                <div class="border-2 border-gray-300 rounded-lg p-4 text-center peer-checked:border-blue-500 peer-checked:bg-blue-50 transition">
                                    <svg class="mx-auto h-8 w-8 text-blue-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                                    </svg>
                                    <span class="font-semibold">Entrée</span>
                                </div>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" name="type" value="sortie" required
                                       class="peer sr-only"
                                       <?php echo (($_POST['type'] ?? '') === 'sortie') ? 'checked' : ''; ?>>
                                <div class="border-2 border-gray-300 rounded-lg p-4 text-center peer-checked:border-red-500 peer-checked:bg-red-50 transition">
                                    <svg class="mx-auto h-8 w-8 text-red-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                                    </svg>
                                    <span class="font-semibold">Sortie</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Niveau d'urgence -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Niveau d'urgence *
                        </label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="urgency" value="normal" required
                                       class="peer sr-only"
                                       <?php echo (($_POST['urgency'] ?? 'normal') === 'normal') ? 'checked' : ''; ?>>
                                <div class="border-2 border-gray-300 rounded-lg p-4 text-center peer-checked:border-green-500 peer-checked:bg-green-50 transition">
                                    <svg class="mx-auto h-8 w-8 text-green-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="font-semibold">Normal</span>
                                </div>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" name="urgency" value="urgent" required
                                       class="peer sr-only"
                                       <?php echo (($_POST['urgency'] ?? '') === 'urgent') ? 'checked' : ''; ?>>
                                <div class="border-2 border-gray-300 rounded-lg p-4 text-center peer-checked:border-red-500 peer-checked:bg-red-50 transition">
                                    <svg class="mx-auto h-8 w-8 text-red-500 mb-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="font-semibold">Urgent</span>
                                </div>
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Les demandes urgentes génèrent des alertes répétées aux administrateurs</p>
                    </div>

                    <!-- Montant -->
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                            Montant (FCFA) *
                        </label>
                        <input type="number" id="amount" name="amount" required min="1" step="0.01"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                               placeholder="100000"
                               value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>">
                    </div>

                    <!-- Date requise -->
                    <div>
                        <label for="required_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Date à laquelle les fonds sont nécessaires *
                        </label>
                        <input type="date" id="required_date" name="required_date" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                               min="<?php echo date('Y-m-d'); ?>"
                               value="<?php echo htmlspecialchars($_POST['required_date'] ?? ''); ?>">
                    </div>

                    <!-- Description -->
                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Motif / Description détaillée *
                        </label>
                        <textarea id="description" name="description" required rows="5"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                                  placeholder="Décrivez en détail le motif de votre demande..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="mt-8 flex justify-between items-center">
                    <a href="dashboard.php" class="text-gray-600 hover:text-gray-700 font-medium">
                        ← Annuler
                    </a>
                    <button type="submit"
                            class="bg-gradient-to-r from-green-600 to-green-700 text-white px-8 py-3 rounded-lg font-semibold hover:from-green-700 hover:to-green-800 focus:ring-4 focus:ring-green-300 transition transform hover:scale-105">
                        Soumettre la demande
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script src="../assets/js/theme.js"></script>
</body>
</html>