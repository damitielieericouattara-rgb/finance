<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Financière - Accueil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gradient-green {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
        }
        .hover-scale {
            transition: transform 0.3s ease;
        }
        .hover-scale:hover {
            transform: translateY(-5px);
        }
        .fade-in {
            animation: fadeIn 0.6s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="ml-3 text-2xl font-bold text-green-600">FinanceFlow</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="auth/login.php" class="text-gray-700 hover:text-green-600 px-3 py-2 rounded-md text-sm font-medium transition">
                        Connexion
                    </a>
                    <a href="auth/register.php" class="gradient-green text-white px-6 py-2 rounded-lg text-sm font-semibold hover:shadow-lg transition">
                        Créer un compte
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="gradient-green">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center fade-in">
            <h1 class="text-5xl font-extrabold text-white mb-6">
                Gérez vos finances d'entreprise avec simplicité
            </h1>
            <p class="text-xl text-green-50 mb-8 max-w-3xl mx-auto">
                Une solution complète et professionnelle pour suivre, valider et analyser toutes vos transactions financières en temps réel
            </p>
            <div class="flex justify-center space-x-4">
                <a href="auth/register.php" class="bg-white text-green-600 px-8 py-4 rounded-lg text-lg font-bold hover:shadow-2xl transition transform hover:scale-105">
                    Commencer gratuitement
                </a>
                <a href="auth/login.php" class="bg-green-700 text-white px-8 py-4 rounded-lg text-lg font-bold hover:bg-green-800 transition">
                    Se connecter
                </a>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <h2 class="text-4xl font-bold text-center text-gray-900 mb-4">
            Pourquoi choisir FinanceFlow ?
        </h2>
        <p class="text-center text-gray-600 mb-16 text-lg">
            Une plateforme complète conçue pour optimiser votre gestion financière
        </p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Feature 1 -->
            <div class="bg-white p-8 rounded-xl shadow-lg hover-scale fade-in">
                <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-4">Validation Rapide</h3>
                <p class="text-gray-600">
                    Soumettez vos demandes et obtenez une validation en quelques clics. Système d'alertes pour les demandes urgentes.
                </p>
            </div>

            <!-- Feature 2 -->
            <div class="bg-white p-8 rounded-xl shadow-lg hover-scale fade-in" style="animation-delay: 0.1s">
                <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-4">Suivi en Temps Réel</h3>
                <p class="text-gray-600">
                    Consultez le solde global, visualisez les statistiques et générez des rapports détaillés instantanément.
                </p>
            </div>

            <!-- Feature 3 -->
            <div class="bg-white p-8 rounded-xl shadow-lg hover-scale fade-in" style="animation-delay: 0.2s">
                <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-4">Sécurité Maximale</h3>
                <p class="text-gray-600">
                    Vos données sont protégées avec un système d'authentification sécurisé et des logs complets de toutes les actions.
                </p>
            </div>

            <!-- Feature 4 -->
            <div class="bg-white p-8 rounded-xl shadow-lg hover-scale fade-in" style="animation-delay: 0.3s">
                <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-4">Reçus Automatiques</h3>
                <p class="text-gray-600">
                    Génération automatique de reçus PDF pour chaque transaction validée avec traçabilité complète.
                </p>
            </div>

            <!-- Feature 5 -->
            <div class="bg-white p-8 rounded-xl shadow-lg hover-scale fade-in" style="animation-delay: 0.4s">
                <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-4">Notifications Intelligentes</h3>
                <p class="text-gray-600">
                    Recevez des alertes instantanées pour toutes les actions importantes et demandes urgentes prioritaires.
                </p>
            </div>

            <!-- Feature 6 -->
            <div class="bg-white p-8 rounded-xl shadow-lg hover-scale fade-in" style="animation-delay: 0.5s">
                <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-4">Exports Faciles</h3>
                <p class="text-gray-600">
                    Exportez vos données en PDF ou Excel avec filtres personnalisés pour vos rapports hebdomadaires et mensuels.
                </p>
            </div>
        </div>
    </div>

    <!-- Statistics Section -->
    <div class="bg-green-50 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-5xl font-bold text-green-600 mb-2">100%</div>
                    <div class="text-gray-700 text-lg">Sécurisé</div>
                </div>
                <div>
                    <div class="text-5xl font-bold text-green-600 mb-2">24/7</div>
                    <div class="text-gray-700 text-lg">Accessible</div>
                </div>
                <div>
                    <div class="text-5xl font-bold text-green-600 mb-2">Temps Réel</div>
                    <div class="text-gray-700 text-lg">Notifications</div>
                </div>
                <div>
                    <div class="text-5xl font-bold text-green-600 mb-2">Illimité</div>
                    <div class="text-gray-700 text-lg">Transactions</div>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="gradient-green rounded-2xl p-12 text-center">
            <h2 class="text-4xl font-bold text-white mb-4">
                Prêt à optimiser votre gestion financière ?
            </h2>
            <p class="text-xl text-green-50 mb-8">
                Rejoignez dès maintenant notre plateforme et simplifiez la gestion de vos transactions
            </p>
            <a href="auth/register.php" class="inline-block bg-white text-green-600 px-10 py-4 rounded-lg text-lg font-bold hover:shadow-2xl transition transform hover:scale-105">
                Créer mon compte gratuitement
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <div class="flex items-center mb-4">
                        <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="ml-2 text-xl font-bold">FinanceFlow</span>
                    </div>
                    <p class="text-gray-400">
                        Votre solution professionnelle pour une gestion financière transparente et efficace.
                    </p>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Liens rapides</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="auth/login.php" class="hover:text-green-500 transition">Connexion</a></li>
                        <li><a href="auth/register.php" class="hover:text-green-500 transition">Créer un compte</a></li>
                        <li><a href="#" class="hover:text-green-500 transition">Support</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Contact</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li>Email: support@financeflow.com</li>
                        <li>Téléphone: +225 XX XX XX XX XX</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2024 FinanceFlow. Tous droits réservés.</p>
            </div>
        </div>
    </footer>
</body>
</html>