<?php
require_once '../config/config.php';

// Si déjà connecté, rediriger
if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? '../admin/dashboard.php' : '../user/dashboard.php'));
    exit();
}

$errors = [];
$success = false;
$step = $_GET['step'] ?? '1';

// ÉTAPE 1 : SOUMISSION DU FORMULAIRE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === '1') {
    $username = cleanInput($_POST['username'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $fullName = cleanInput($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $phone = cleanInput($_POST['phone'] ?? '');
    $whatsapp = cleanInput($_POST['whatsapp'] ?? '');
    $adminCode = cleanInput($_POST['admin_code'] ?? '');
    $selectedRole = cleanInput($_POST['role'] ?? 'user');
    
    // Validation
    if (empty($username)) {
        $errors[] = "Le nom d'utilisateur est requis";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Une adresse email valide est requise";
    }
    
    if (empty($fullName)) {
        $errors[] = "Le nom complet est requis";
    }
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = "Le mot de passe doit contenir au moins " . PASSWORD_MIN_LENGTH . " caractères";
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }
    
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre";
    }
    
    // Déterminer le rôle
    $roleId = 2; // Par défaut : utilisateur
    if ($selectedRole === 'admin') {
        if ($adminCode === ADMIN_CODE) {
            $roleId = 1; // Admin
        } else {
            $errors[] = "Code administrateur incorrect. Vous serez inscrit en tant qu'utilisateur.";
            $roleId = 2;
        }
    }
    
    if (empty($errors)) {
        try {
            $db = getDB();
            
            // Vérifier si l'email existe déjà
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Cette adresse email est déjà utilisée";
            }
            
            // Vérifier si le username existe déjà
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = "Ce nom d'utilisateur est déjà pris";
            }
            
            if (empty($errors)) {
                // Stocker les données dans la session pour l'étape 2
                $_SESSION['registration_data'] = [
                    'username' => $username,
                    'email' => $email,
                    'password' => $password,
                    'full_name' => $fullName,
                    'role_id' => $roleId,
                    'phone' => $phone,
                    'whatsapp' => $whatsapp
                ];
                
                // Envoyer OTP
                $otpResult = sendOTP($email, 'registration');
                
                if ($otpResult['success']) {
                    // ✅ STOCKER LE CODE DEBUG EN SESSION
                    if (isset($otpResult['debug_code'])) {
                        $_SESSION['debug_otp_code'] = $otpResult['debug_code'];
                    }
                    
                    header('Location: register.php?step=2');
                    exit();
                } else {
                    $errors[] = $otpResult['message'];
                }
            }
        } catch (Exception $e) {
            error_log("Erreur inscription: " . $e->getMessage());
            $errors[] = "Une erreur est survenue. Veuillez réessayer.";
        }
    }
}

// ÉTAPE 2 : VÉRIFICATION OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === '2') {
    $otp = cleanInput($_POST['otp'] ?? '');
    
    if (empty($otp)) {
        $errors[] = "Veuillez entrer le code de vérification";
    } else {
        if (!isset($_SESSION['registration_data'])) {
            $errors[] = "Session expirée. Veuillez recommencer l'inscription.";
        } else {
            $data = $_SESSION['registration_data'];
            
            // Vérifier l'OTP
            $verifyResult = verifyOTP($data['email'], $otp, 'registration');
            
            if ($verifyResult['success']) {
                try {
                    $db = getDB();
                    
                    // Créer l'utilisateur
                    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                    
                    $stmt = $db->prepare("
                        INSERT INTO users (username, email, password, full_name, role_id, phone, whatsapp, email_verified)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 1)
                    ");
                    
                    if ($stmt->execute([
                        $data['username'],
                        $data['email'],
                        $hashedPassword,
                        $data['full_name'],
                        $data['role_id'],
                        $data['phone'],
                        $data['whatsapp']
                    ])) {
                        $userId = $db->lastInsertId();
                        
                        logActivity($userId, 'REGISTER', 'users', $userId, 'Nouvel utilisateur avec role_id: ' . $data['role_id']);
                        
                        // Créer préférences par défaut
                        $prefStmt = $db->prepare("INSERT INTO user_preferences (user_id) VALUES (?)");
                        $prefStmt->execute([$userId]);
                        
                        // Notification de bienvenue
                        $roleName = $data['role_id'] == 1 ? 'administrateur' : 'utilisateur';
                        notifyUser(
                            $userId,
                            'Bienvenue sur ' . SITE_NAME . ' !',
                            "Votre compte $roleName a été créé avec succès. Vous pouvez maintenant vous connecter.",
                            'success',
                            null,
                            false
                        );
                        
                        $success = true;
                        unset($_SESSION['registration_data']);
                        unset($_SESSION['debug_otp_code']); // ✅ Nettoyer le code debug
                    } else {
                        $errors[] = "Une erreur est survenue lors de la création du compte";
                    }
                } catch (Exception $e) {
                    error_log("Erreur création compte: " . $e->getMessage());
                    $errors[] = "Une erreur est survenue. Veuillez réessayer.";
                }
            } else {
                $errors[] = $verifyResult['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un compte - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-green-50 to-green-100 min-h-screen py-8 sm:py-12">
    <div class="max-w-2xl mx-auto px-4">
        <!-- Logo et titre -->
        <div class="text-center mb-8">
            <div class="flex justify-center mb-4">
                <svg class="h-16 w-16 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Créer un compte</h1>
            <p class="text-gray-600 mt-2">Rejoignez notre plateforme de gestion financière</p>
            
            <?php if ($step === '2'): ?>
                <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-sm text-blue-800">
                        📧 Un code de vérification a été envoyé à <strong><?php echo htmlspecialchars($_SESSION['registration_data']['email'] ?? ''); ?></strong>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Formulaire d'inscription -->
        <div class="bg-white rounded-2xl shadow-xl p-6 sm:p-8">
            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                    <div class="flex">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm text-green-700 font-medium">Compte créé avec succès !</p>
                            <p class="text-sm text-green-600 mt-1">
                                <a href="login.php" class="underline font-semibold">Cliquez ici pour vous connecter</a>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
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

            <?php if ($step === '1'): ?>
                <!-- ÉTAPE 1 : FORMULAIRE D'INSCRIPTION -->
                <!-- ÉTAPE 1 : FORMULAIRE D'INSCRIPTION -->
                <form method="POST" action="?step=1" class="<?php echo $success ? 'opacity-50 pointer-events-none' : ''; ?>">
                    <div class="space-y-6">
                        <!-- Sélection du rôle -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                Je souhaite créer un compte * 
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="role" value="admin" class="peer sr-only" <?php echo (($_POST['role'] ?? '') === 'admin') ? 'checked' : ''; ?>>
                                    <div class="border-2 border-gray-300 rounded-lg p-4 text-center peer-checked:border-purple-500 peer-checked:bg-purple-50 transition hover:border-purple-300">
                                        <svg class="mx-auto h-10 w-10 text-purple-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                        </svg>
                                        <span class="font-semibold text-lg">Administrateur</span>
                                        <p class="text-xs text-gray-600 mt-1">Code requis</p>
                                    </div>
                                </label>
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="role" value="user" checked class="peer sr-only">
                                    <div class="border-2 border-gray-300 rounded-lg p-4 text-center peer-checked:border-green-500 peer-checked:bg-green-50 transition hover:border-green-300">
                                        <svg class="mx-auto h-10 w-10 text-green-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        <span class="font-semibold text-lg">Utilisateur</span>
                                        <p class="text-xs text-gray-600 mt-1">Accès standard</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Code admin (affiché conditionnellement) -->
                        <div id="adminCodeField" class="hidden">
                            <label for="admin_code" class="block text-sm font-medium text-gray-700 mb-2">
                                Code administrateur
                            </label>
                            <input type="text" id="admin_code" name="admin_code" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition" placeholder="Entrez le code administrateur" value="<?php echo htmlspecialchars($_POST['admin_code'] ?? ''); ?>">
                            <p class="text-xs text-gray-500 mt-1">Si vous n'avez pas de code, vous serez inscrit en tant qu'utilisateur</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">Nom complet *</label>
                                <input type="text" id="full_name" name="full_name" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition" placeholder="Jean Dupont" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                            </div>

                            <div>
                                <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Nom d'utilisateur *</label>
                                <input type="text" id="username" name="username" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition" placeholder="jdupont" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                            </div>

                            <div class="md:col-span-2">
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Adresse email *</label>
                                <input type="email" id="email" name="email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition" placeholder="votre@email.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Téléphone (SMS)</label>
                                <input type="tel" id="phone" name="phone" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition" placeholder="+225 07 00 00 00 00" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                <p class="text-xs text-gray-500 mt-1">Pour les notifications SMS</p>
                            </div>

                            <div>
                                <label for="whatsapp" class="block text-sm font-medium text-gray-700 mb-2">WhatsApp</label>
                                <input type="tel" id="whatsapp" name="whatsapp" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition" placeholder="+225 07 00 00 00 00" value="<?php echo htmlspecialchars($_POST['whatsapp'] ?? ''); ?>">
                                <p class="text-xs text-gray-500 mt-1">Pour les notifications WhatsApp</p>
                            </div>

                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Mot de passe *</label>
                                <input type="password" id="password" name="password" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition" placeholder="••••••••">
                                <p class="text-xs text-gray-500 mt-1">Min. 8 caractères, une majuscule, une minuscule et un chiffre</p>
                            </div>

                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirmer le mot de passe *</label>
                                <input type="password" id="confirm_password" name="confirm_password" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition" placeholder="••••••••">
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <label class="flex items-start">
                            <input type="checkbox" required class="mt-1 h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                            <span class="ml-2 text-sm text-gray-600">
                                J'accepte les conditions d'utilisation et la politique de confidentialité
                            </span>
                        </label>
                    </div>

                    <button type="submit" class="w-full mt-6 bg-gradient-to-r from-green-600 to-green-700 text-white py-3 px-4 rounded-lg font-semibold hover:from-green-700 hover:to-green-800 focus:ring-4 focus:ring-green-300 transition transform hover:scale-105">
                        Continuer
                    </button>
                </form>
            <?php else: ?>
                <!-- ÉTAPE 2 : VÉRIFICATION OTP AVEC AFFICHAGE DEBUG -->
                <form method="POST" action="?step=2">
                    <div class="text-center mb-6">
                        <svg class="mx-auto h-20 w-20 text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <h2 class="text-xl font-bold text-gray-900 mb-2">Vérifiez votre email</h2>
                        <p class="text-gray-600">Entrez le code à 6 chiffres que nous vous avons envoyé</p>
                        
                        <!-- ✅ AFFICHAGE DU CODE EN MODE DÉVELOPPEMENT -->
                        <?php if (isset($_SESSION['debug_otp_code'])): ?>
                            <div class="mt-4 p-4 bg-yellow-50 border-2 border-yellow-400 rounded-lg animate-pulse">
                                <p class="text-sm font-bold text-yellow-800 mb-2">🔧 MODE DÉVELOPPEMENT</p>
                                <p class="text-2xl font-mono font-bold text-yellow-900">
                                    Votre code : <?php echo $_SESSION['debug_otp_code']; ?>
                                </p>
                                <p class="text-xs text-yellow-700 mt-2">
                                    ⚠️ Ce message ne s'affiche qu'en environnement localhost
                                </p>
                                <p class="text-xs text-yellow-600 mt-1">
                                    En production, le code sera envoyé par email uniquement
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-6">
                        <label for="otp" class="block text-sm font-medium text-gray-700 mb-2 text-center">Code de vérification</label>
                        <input type="text" id="otp" name="otp" required maxlength="6" pattern="[0-9]{6}" 
                               class="w-full px-4 py-4 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition text-center text-2xl tracking-widest font-mono" 
                               placeholder="000000" autofocus>
                        <p class="text-xs text-gray-500 mt-2 text-center">Le code expire dans 10 minutes</p>
                    </div>

                    <button type="submit" class="w-full bg-gradient-to-r from-green-600 to-green-700 text-white py-3 px-4 rounded-lg font-semibold hover:from-green-700 hover:to-green-800 focus:ring-4 focus:ring-green-300 transition transform hover:scale-105">
                        ✅ Vérifier et créer mon compte
                    </button>

                    <div class="mt-4 text-center space-y-2">
                        <button type="button" onclick="window.location.href='register.php?step=1'" class="text-sm text-gray-600 hover:text-green-600">
                            ← Retour au formulaire
                        </button>
                        <p class="text-xs text-gray-500">
                            Vous n'avez pas reçu le code ? Vérifiez vos spams ou recommencez l'inscription
                        </p>
                    </div>
                </form>
            <?php endif; ?>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Vous avez déjà un compte ?
                    <a href="login.php" class="text-green-600 hover:text-green-700 font-semibold">
                        Se connecter
                    </a>
                </p>
            </div>
        </div>

        <div class="text-center mt-6">
            <a href="../index.php" class="text-sm text-gray-600 hover:text-green-600 transition">
                ← Retour à l'accueil
            </a>
        </div>
    </div>

    <script>
        // Afficher/masquer le champ code admin
        document.querySelectorAll('input[name="role"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const adminCodeField = document.getElementById('adminCodeField');
                if (this.value === 'admin') {
                    adminCodeField.classList.remove('hidden');
                } else {
                    adminCodeField.classList.add('hidden');
                }
            });
        });

        // Initialiser au chargement
        if (document.querySelector('input[name="role"]:checked')?.value === 'admin') {
            document.getElementById('adminCodeField').classList.remove('hidden');
        }

        // Auto-focus et validation OTP
        const otpInput = document.getElementById('otp');
        if (otpInput) {
            otpInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }
    </script>
</body>
</html>