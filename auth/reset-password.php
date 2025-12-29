<?php
require_once '../config/config.php';

$message = '';
$messageType = '';
$token = cleanInput($_GET['token'] ?? '');
$validToken = false;

// Vérifier le token
if (!empty($token)) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT id, username, full_name, email 
            FROM users 
            WHERE reset_token = ? 
            AND reset_token_expires > NOW() 
            AND is_active = 1
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if ($user) {
            $validToken = true;
        } else {
            $message = 'Ce lien de réinitialisation est invalide ou a expiré';
            $messageType = 'error';
        }
    } catch (Exception $e) {
        error_log("Erreur vérification token: " . $e->getMessage());
        $message = 'Une erreur est survenue';
        $messageType = 'error';
    }
} else {
    $message = 'Token manquant';
    $messageType = 'error';
}

// Traiter le formulaire de réinitialisation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($newPassword)) {
        $message = 'Le nouveau mot de passe est requis';
        $messageType = 'error';
    } elseif (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
        $message = 'Le mot de passe doit contenir au moins ' . PASSWORD_MIN_LENGTH . ' caractères';
        $messageType = 'error';
    } elseif (!preg_match('/[A-Z]/', $newPassword) || !preg_match('/[a-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
        $message = 'Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre';
        $messageType = 'error';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'Les mots de passe ne correspondent pas';
        $messageType = 'error';
    } else {
        try {
            $db = getDB();
            
            // Mettre à jour le mot de passe
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("
                UPDATE users 
                SET password = ?, 
                    reset_token = NULL, 
                    reset_token_expires = NULL,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            if ($stmt->execute([$hashedPassword, $user['id']])) {
                // Logger l'activité
                logActivity($user['id'], 'PASSWORD_RESET_SUCCESS', 'users', $user['id'], 'Mot de passe réinitialisé avec succès');
                
                // Envoyer un email de confirmation
                $subject = "Votre mot de passe a été réinitialisé - " . SITE_NAME;
                $body = "
                    <html>
                    <body style='font-family: Arial, sans-serif;'>
                        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                            <h2 style='color: #059669;'>Mot de passe réinitialisé</h2>
                            <p>Bonjour {$user['full_name']},</p>
                            <p>Votre mot de passe a été réinitialisé avec succès.</p>
                            <p>Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.</p>
                            <p style='margin-top: 30px;'>
                                <a href='" . SITE_URL . "/auth/login.php' 
                                   style='background-color: #059669; color: white; padding: 12px 24px; 
                                          text-decoration: none; border-radius: 5px; display: inline-block;'>
                                    Se connecter
                                </a>
                            </p>
                            <p style='color: #666; font-size: 14px; margin-top: 30px;'>
                                Si vous n'avez pas effectué cette action, contactez-nous immédiatement.
                            </p>
                        </div>
                    </body>
                    </html>
                ";
                
                sendEmail($user['email'], $subject, $body, true);
                
                $message = 'Votre mot de passe a été réinitialisé avec succès';
                $messageType = 'success';
                $validToken = false; // Empêcher la réutilisation
                
                // Redirection après 3 secondes
                header("refresh:3;url=login.php");
            } else {
                $message = 'Une erreur est survenue lors de la réinitialisation';
                $messageType = 'error';
            }
        } catch (Exception $e) {
            error_log("Erreur réinitialisation: " . $e->getMessage());
            $message = 'Une erreur est survenue. Veuillez réessayer.';
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser le mot de passe - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <script>
        function validatePassword() {
            const password = document.getElementById('new_password').value;
            const confirm = document.getElementById('confirm_password').value;
            const submitBtn = document.getElementById('submitBtn');
            const strength = document.getElementById('passwordStrength');
            const match = document.getElementById('passwordMatch');
            
            // Vérifier la force du mot de passe
            let strengthLevel = 0;
            let strengthText = '';
            let strengthColor = '';
            
            if (password.length >= 8) strengthLevel++;
            if (/[A-Z]/.test(password)) strengthLevel++;
            if (/[a-z]/.test(password)) strengthLevel++;
            if (/[0-9]/.test(password)) strengthLevel++;
            if (/[^A-Za-z0-9]/.test(password)) strengthLevel++;
            
            if (password.length === 0) {
                strengthText = '';
            } else if (strengthLevel <= 2) {
                strengthText = '❌ Faible';
                strengthColor = 'text-red-600';
            } else if (strengthLevel === 3) {
                strengthText = '⚠️ Moyen';
                strengthColor = 'text-yellow-600';
            } else {
                strengthText = '✅ Fort';
                strengthColor = 'text-green-600';
            }
            
            strength.className = 'text-sm font-medium ' + strengthColor;
            strength.textContent = strengthText;
            
            // Vérifier la correspondance
            if (confirm.length > 0) {
                if (password === confirm) {
                    match.className = 'text-sm text-green-600';
                    match.textContent = '✅ Les mots de passe correspondent';
                } else {
                    match.className = 'text-sm text-red-600';
                    match.textContent = '❌ Les mots de passe ne correspondent pas';
                }
            } else {
                match.textContent = '';
            }
            
            // Activer/désactiver le bouton
            submitBtn.disabled = !(strengthLevel >= 3 && password === confirm && password.length >= 8);
        }
    </script>
</head>
<body class="bg-gradient-to-br from-green-50 to-green-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <!-- Logo et titre -->
        <div class="text-center mb-8">
            <div class="flex justify-center mb-4">
                <svg class="h-16 w-16 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900">Nouveau mot de passe</h1>
            <p class="text-gray-600 mt-2">Choisissez un mot de passe sécurisé</p>
        </div>

        <!-- Formulaire -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 border-l-4 border-green-500' : 'bg-red-50 border-l-4 border-red-500'; ?>">
                    <div class="flex">
                        <?php if ($messageType === 'success'): ?>
                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        <?php else: ?>
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        <?php endif; ?>
                        <div class="ml-3">
                            <p class="text-sm <?php echo $messageType === 'success' ? 'text-green-700' : 'text-red-700'; ?>">
                                <?php echo $message; ?>
                            </p>
                            <?php if ($messageType === 'success'): ?>
                                <p class="text-sm text-green-600 mt-1">Redirection vers la page de connexion...</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($validToken): ?>
                <form method="POST" action="" onsubmit="return document.getElementById('submitBtn').disabled === false">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="space-y-6">
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                                Nouveau mot de passe
                            </label>
                            <input type="password" id="new_password" name="new_password" required
                                   oninput="validatePassword()"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                                   placeholder="••••••••">
                            <div id="passwordStrength" class="mt-2 text-sm"></div>
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                                Confirmer le mot de passe
                            </label>
                            <input type="password" id="confirm_password" name="confirm_password" required
                                   oninput="validatePassword()"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                                   placeholder="••••••••">
                            <div id="passwordMatch" class="mt-2 text-sm"></div>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-700 font-medium mb-2">Le mot de passe doit contenir :</p>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>• Au moins 8 caractères</li>
                                <li>• Une lettre majuscule</li>
                                <li>• Une lettre minuscule</li>
                                <li>• Un chiffre</li>
                            </ul>
                        </div>

                        <button type="submit" id="submitBtn" disabled
                                class="w-full bg-gradient-to-r from-green-600 to-green-700 text-white py-3 px-4 rounded-lg font-semibold hover:from-green-700 hover:to-green-800 focus:ring-4 focus:ring-green-300 transition transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
                            Réinitialiser le mot de passe
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="text-center py-6">
                    <p class="text-gray-600 mb-6">Ce lien n'est plus valide ou a expiré.</p>
                    <a href="forgot-password.php" class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition">
                        Demander un nouveau lien
                    </a>
                </div>
            <?php endif; ?>

            <div class="mt-6 text-center">
                <a href="login.php" class="text-sm text-green-600 hover:text-green-700 font-medium">
                    ← Retour à la connexion
                </a>
            </div>
        </div>

        <div class="text-center mt-6">
            <a href="../index.php" class="text-sm text-gray-600 hover:text-green-600 transition">
                ← Retour à l'accueil
            </a>
        </div>
    </div>
    <script src="../assets/js/theme.js"></script>
</body>
</html>