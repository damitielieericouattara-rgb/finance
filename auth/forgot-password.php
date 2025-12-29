<?php
require_once '../config/config.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = cleanInput($_POST['email'] ?? '');
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Veuillez entrer une adresse email valide';
        $messageType = 'error';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT id, username, full_name FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Générer un token de réinitialisation
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', time() + RESET_TOKEN_EXPIRY);
                
                // Enregistrer le token
                $stmt = $db->prepare("
                    UPDATE users 
                    SET reset_token = ?, reset_token_expires = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$token, $expiry, $user['id']]);
                
                // Créer le lien de réinitialisation
                $resetLink = SITE_URL . "/auth/reset-password.php?token=" . $token;
                
                // Envoyer l'email
                $subject = "Réinitialisation de votre mot de passe - " . SITE_NAME;
                $body = "
                    <html>
                    <body style='font-family: Arial, sans-serif;'>
                        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                            <h2 style='color: #059669;'>Réinitialisation de mot de passe</h2>
                            <p>Bonjour {$user['full_name']},</p>
                            <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
                            <p>Cliquez sur le lien ci-dessous pour créer un nouveau mot de passe :</p>
                            <p style='margin: 30px 0;'>
                                <a href='$resetLink' 
                                   style='background-color: #059669; color: white; padding: 12px 24px; 
                                          text-decoration: none; border-radius: 5px; display: inline-block;'>
                                    Réinitialiser mon mot de passe
                                </a>
                            </p>
                            <p>Ou copiez ce lien dans votre navigateur :</p>
                            <p style='color: #666; word-break: break-all;'>$resetLink</p>
                            <p style='color: #666; font-size: 14px; margin-top: 30px;'>
                                Ce lien expire dans 1 heure.<br>
                                Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.
                            </p>
                        </div>
                    </body>
                    </html>
                ";
                
                sendEmail($email, $subject, $body, true);
                
                logActivity($user['id'], 'PASSWORD_RESET_REQUEST', 'users', $user['id'], 'Demande de réinitialisation');
                
                $message = 'Un email de réinitialisation a été envoyé à votre adresse';
                $messageType = 'success';
            } else {
                // Ne pas révéler si l'email existe ou non (sécurité)
                $message = 'Si cette adresse email existe, un lien de réinitialisation a été envoyé';
                $messageType = 'success';
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
    <title>Mot de passe oublié - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/responsive.css">
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
            <h1 class="text-3xl font-bold text-gray-900">Mot de passe oublié ?</h1>
            <p class="text-gray-600 mt-2">Entrez votre email pour réinitialiser votre mot de passe</p>
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
                            <p class="ml-3 text-sm text-green-700"><?php echo $message; ?></p>
                        <?php else: ?>
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <p class="ml-3 text-sm text-red-700"><?php echo $message; ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Adresse email
                        </label>
                        <input type="email" id="email" name="email" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                               placeholder="votre@email.com"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>

                    <button type="submit"
                            class="w-full bg-gradient-to-r from-green-600 to-green-700 text-white py-3 px-4 rounded-lg font-semibold hover:from-green-700 hover:to-green-800 focus:ring-4 focus:ring-green-300 transition transform hover:scale-105">
                        Envoyer le lien de réinitialisation
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center space-y-2">
                <a href="login.php" class="block text-sm text-green-600 hover:text-green-700 font-medium">
                    ← Retour à la connexion
                </a>
                <a href="register.php" class="block text-sm text-gray-600 hover:text-green-600">
                    Créer un nouveau compte
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