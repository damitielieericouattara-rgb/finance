<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Financière - Simplifiez votre gestion financière</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- <link rel="stylesheet" href="./assets/css/responsive.css"> -->
    <link rel="stylesheet" href="./assets/css/responsive2.css">
</head>
<body class="bg-gray-50 font-sans antialiased">

    <!---------------------------------NAVBAR ------------------------------->
                        <!-- NAVBAR FIXE RESPONSIVE -->
<nav class="bg-white shadow-md fixed w-full top-0 z-50 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            
            <!-----------------------Logo-------------------------->
            <div class="flex items-center space-x-2 flex-shrink-0">
                    <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-coins text-white text-xl"></i>
                    </div>
                    <span class="text-xl md:text-2xl font-bold text-gray-800">Gestion Financière</span>
                </div>

            <!----------------------------------Menu Desktop-------------------------------->
            <div class="hidden md:flex items-center space-x-4">
                <a href="#home" class="text-gray-700 hover:text-green-600 transition-colors font-medium">Accueil</a>
                <a href="#features" class="text-gray-700 hover:text-green-600 transition-colors font-medium">Caractéristiques</a>
                <a href="#about" class="text-gray-700 hover:text-green-600 transition-colors font-medium">À propos</a>
            </div>

            <!------------------------------Bouton connexion/déconnexion Desktop-------------------------->
            <div class="hidden md:flex items-center">
                <a href="./auth/login.php" class="boutton bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors font-medium shadow-md">
                    Se connecter
                </a>
            </div>

            <!-------------------------------Bouton Hamburger (Mobile)------------------------------>
            <button id="mobile-menu-btn" class="md:hidden text-gray-700 focus:outline-none p-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>
    </div>

    <!---------------------------------Menu Mobile--------------------------->
    <div id="mobile-menu" class="hidden">
        <div class="px-4 py-3 space-y-1 bg-white shadow-lg">
            
            <a href="#home" class="block text-gray-700 hover:text-green-600 hover:bg-gray-50 py-3 px-4 rounded-lg">
                Accueil
            </a>
            <a href="#features" class="block text-gray-700 hover:text-green-600 hover:bg-gray-50 py-3 px-4 rounded-lg">
                Caractéristiques
            </a>
            <a href="#about" class="block text-gray-700 hover:text-green-600 hover:bg-gray-50 py-3 px-4 rounded-lg">
                À propos
            </a>
            <a href="./auth/login.php" class="block bg-green-600 text-white px-4 py-3 rounded-lg hover:bg-green-700 text-center mt-2">
                Se connecter
            </a>
        </div>
    </div>
</nav>

    <!----------------------------------------SECTION ACCEUIL------------------------------>
    <section id="home" class="relative pt-32 pb-20 px-6 bg-gradient-to-r from-green-500 to-green-600 overflow-hidden">
        
        <div class="absolute top-0 right-0 w-96 h-96 bg-green-500 rounded-full filter blur-3xl opacity-20 -mr-48 -mt-48"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-green-500 rounded-full filter blur-3xl opacity-20 -ml-48 -mb-48"></div>
        
        <div class="container mx-auto relative z-10">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <!--------------------------------------TEXT DE LA PARTIE ACCEUIL------------------------------>
                <div class="text-white space-y-6 fade-in-up">
                    <h1 class="text-5xl md:text-6xl font-bold leading-tight">
                        Simplifiez et sécurisez votre gestion financière
                    </h1>
                    <p class="text-xl text-green-100 leading-relaxed">
                        Suivez toutes vos transactions, contrôlez votre budget et restez organisé sans effort grâce à notre plateforme intuitive.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 pt-4">
                        <a href="./auth/login.php" class="bg-white text-green-600 px-8 py-4 rounded-lg font-semibold hover:bg-gray-100 transition-all transform hover:scale-105 shadow-xl text-center">
                           <i class="fas fa-rocket mr-2"></i> Commencer
                        </a>
                        <a href="#features" class="bg-transparent border-2 border-white text-white px-8 py-4 rounded-lg font-semibold hover:bg-white hover:text-green-600 transition-all text-center">
                           <i class="fas fa-arrow-down mr-2"></i> Apprendre encore plus
                        </a>
                    </div>
                </div>

                <!------------------------------ILLUSTRATION DE LA PARTIE ACCEUIL------------------------------->
                <div class="hidden md:block fade-in-up delay-200">
                    <div class="bg-white rounded-2xl shadow-2xl p-8 transform hover:scale-105 transition-transform duration-300">
                        <div class="space-y-4">
                            <!----------------------------POUR LA PARTIE SCREEN DE PC MACBOOK------------------------>
                            <div class="flex items-center justify-between pb-4 border-b">
                                <h3 class="font-bold text-gray-800">Aperçu du tableau de bord</h3>
                                <div class="flex space-x-2">
                                    <div class="w-3 h-3 bg-red-400 rounded-full"></div>
                                    <div class="w-3 h-3 bg-yellow-400 rounded-full"></div>
                                    <div class="w-3 h-3 bg-green-400 rounded-full"></div>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-green-50 p-4 rounded-lg">
                                    <p class="text-sm text-gray-600">Revenu total</p>
                                    <p class="text-2xl font-bold text-green-600">45 32000 FCFA</p>
                                </div>
                                <div class="bg-red-50 p-4 rounded-lg">
                                    <p class="text-sm text-gray-600">Dépenses totales</p>
                                    <p class="text-2xl font-bold text-red-600">28 45000 FCFA</p>
                                </div>
                            </div>
                            <div class="bg-gradient-to-r from-green-500 to-green-600 p-4 rounded-lg text-white">
                                <p class="text-sm opacity-90">Solde actuel</p>
                                <p class="text-3xl font-bold">16 87000 FCFA</p>
                            </div>
                            <div class="flex items-center justify-center py-4">
                                <svg class="w-full h-20" viewBox="0 0 200 40">
                                    <polyline points="0,30 40,25 80,15 120,20 160,10 200,15" fill="none" stroke="#2563eb" stroke-width="2"/>
                                    <polyline points="0,30 40,25 80,15 120,20 160,10 200,15" fill="url(#gradient)" opacity="0.3"/>
                                    <defs>
                                        <linearGradient id="gradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                            <stop offset="0%" style="stop-color:#2563eb;stop-opacity:0.5" />
                                            <stop offset="100%" style="stop-color:#2563eb;stop-opacity:0" />
                                        </linearGradient>
                                    </defs>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-----------------------------------Section caracteristiques---------------------------------->
    <section id="features" class="py-20 px-6 bg-white">
        <div class="container mx-auto">
            <div class="text-center mb-16 fade-in-section">
                <h2 class="text-4xl font-bold text-gray-800 mb-4">Des fonctionnalités puissantes pour un contrôle total</h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">Tout ce dont vous avez besoin pour gérer vos finances efficacement et en toute sécurité</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!------------------------caracteristiques 1------------------------>
                <div class="bg-gray-50 rounded-xl p-8 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 fade-in-section">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-400 to-green-600 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Suivi des revenus et des dépenses</h3>
                    <p class="text-gray-600">Suivez chaque transaction grâce à une catégorisation détaillée et des mises à jour instantanées de votre aperçu financier.</p>
                </div>

                <!------------------------caracteristiques 2------------------------>
                <div class="bg-gray-50 rounded-xl p-8 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 fade-in-section">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Mises à jour du solde en temps réel</h3>
                    <p class="text-gray-600">Consultez instantanément votre situation financière actuelle grâce aux calculs automatiques et au suivi en temps réel de votre solde.</p>
                </div>

                <!------------------------caracteristiques 3------------------------>
                <div class="bg-gray-50 rounded-xl p-8 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 fade-in-section">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-400 to-purple-600 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Générer des reçus et des rapports</h3>
                    <p class="text-gray-600">Créez des rapports et des reçus professionnels grâce à des fonctionnalités complètes d'analyse et d'exportation des données.</p>
                </div>

                <!------------------------caracteristiques 4------------------------>
                <div class="bg-gray-50 rounded-xl p-8 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 fade-in-section">
                    <div class="w-16 h-16 bg-gradient-to-br from-red-400 to-red-600 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Accès sécurisé et rôles</h3>
                    <p class="text-gray-600">Une sécurité de niveau entreprise avec contrôle d'accès basé sur les rôles pour protéger vos données financières sensibles.</p>
                </div>
            </div>
        </div>
    </section>

    <!--------------------------------------------SECTION  A PROPOS----------------------------->
    <section id="about" class="py-20 px-6 bg-gray-50">
        <div class="container mx-auto">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <!------------------------------partie texte------------------------------->
                <div class="space-y-6 fade-in-section">
                    <h2 class="text-4xl font-bold text-gray-800">Une interface claire et intuitive</h2>
                    <p class="text-lg text-gray-600 leading-relaxed">
                        Conçue pour les utilisateurs comme pour les administrateurs, notre plateforme offre une expérience simplifiée qui facilite la gestion financière.
                    </p>
                    <ul class="space-y-4">
                        <li class="flex items-start space-x-3">
                            <svg class="w-6 h-6 text-green-500 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span class="text-gray-700">Tableau de bord convivial avec widgets personnalisables</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <svg class="w-6 h-6 text-green-500 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span class="text-gray-700">Des outils d'administration performants pour une supervision complète</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <svg class="w-6 h-6 text-green-500 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span class="text-gray-700">Conception adaptative fonctionnant sur tous les appareils</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <svg class="w-6 h-6 text-green-500 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span class="text-gray-700">Fonctionnalités avancées de filtrage et de recherche</span>
                        </li>
                    </ul>
                </div>

                <!------------------------------POUR LA PARTIE SCREEN DE PC MACBOOK-------------------------------->
                <div class="relative fade-in-section">
                    <div class="relative z-10 bg-white rounded-2xl shadow-2xl p-6 transform hover:scale-105 transition-transform duration-300">
                        <!-----------------------PARTIE BARRE DE RECHERCHE------------------------------------->
                        <div class="flex items-center space-x-2 mb-4 pb-4 border-b">
                            <div class="flex space-x-2">
                                <div class="w-3 h-3 bg-red-400 rounded-full"></div>
                                <div class="w-3 h-3 bg-yellow-400 rounded-full"></div>
                                <div class="w-3 h-3 bg-green-400 rounded-full"></div>
                            </div>
                            <div class="flex-1 bg-gray-100 rounded px-3 py-1 text-xs text-gray-500">
                                GestionFinancière.app/dashboard
                            </div>
                        </div>
                        
                        <!----------------LES CARRES DU MOCKUP------------------------>
                        <div class="space-y-3">
                            <div class="h-8 bg-gradient-to-r from-green-500 to-green-600 rounded"></div>
                            <div class="grid grid-cols-3 gap-3">
                                <div class="h-20 bg-green-100 rounded"></div>
                                <div class="h-20 bg-blue-100 rounded"></div>
                                <div class="h-20 bg-purple-100 rounded"></div>
                            </div>
                            <div class="h-32 bg-gray-100 rounded"></div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="h-16 bg-gray-100 rounded"></div>
                                <div class="h-16 bg-gray-100 rounded"></div>
                            </div>
                        </div>
                    </div>
                    <!---------------------LE CARRE ARRIERE------------------- -->
                    <div class="absolute inset-0 bg-gradient-to-r from-green-500 to-green-600 rounded-2xl transform translate-x-4 translate-y-4 -z-10"></div>
                </div>
            </div>
        </div>
    </section>

    <!--------------------------------Section  CTA---------------------------------------->
<section class="relative py-24 rounded-t-lg px-6 bg-gradient-to-br from-green-500 via-green-600 to-green-700 overflow-hidden">
    <div class="absolute inset-0 bg-black bg-opacity-10"></div>
    <div class="absolute top-0 left-0 w-64 h-64 bg-white rounded-full opacity-10 -ml-32 -mt-32"></div>
    <div class="absolute bottom-0 right-0 w-96 h-96 bg-green-400 rounded-full opacity-20 -mr-48 -mb-48"></div>
    
    <div class="container mx-auto relative z-10 text-center fade-in-section">
        <h2 class="text-5xl md:text-6xl font-extrabold text-white mb-6 leading-tight">
            Prêt à transformer votre gestion financière ?
        </h2>
        <p class="text-xl md:text-2xl text-green-100 mb-8 max-w-3xl mx-auto leading-relaxed">
            Rejoignez des milliers d'utilisateurs satisfaits et simplifiez votre quotidien dès aujourd'hui.
        </p>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
            <div class="bg-white bg-opacity-10 backdrop-blur-sm rounded-lg p-6 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 fade-in-section">
                <div class="text-4xl font-bold text-white mb-2">50K+</div>
                <p class="text-green-200">Entreprises satisfaites</p>
            </div>
            <div class="bg-white bg-opacity-10 backdrop-blur-sm rounded-lg p-6 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 fade-in-section">
                <div class="text-4xl font-bold text-white mb-2">99.9%</div>
                <p class="text-green-200">Taux de disponibilité</p>
            </div>
            <div class="bg-white bg-opacity-10 backdrop-blur-sm rounded-lg p-6 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 fade-in-section">
                <div class="text-4xl font-bold text-white mb-2">24/7</div>
                <p class="text-green-200">Support technique</p>
            </div>
        </div>
        
        <!-- Boutons CTA -->
        <div class="flex flex-col sm:flex-row gap-6 justify-center items-center">
            <a href="./auth/login.php" class="inline-block bg-white text-green-600 px-12 py-5 rounded-lg font-bold text-xl hover:bg-gray-100 transition-all transform hover:scale-110 shadow-2xl hover:shadow-3xl animate-pulse">
                <i class="fas fa-rocket mr-2"></i>Commencer maintenant
            </a>
            <a href="#features" class="inline-block bg-transparent border-2 border-white text-white px-12 py-5 rounded-lg font-semibold text-xl hover:bg-white hover:text-green-600 transition-all transform hover:scale-105">
               <i class="fas fa-info-circle mr-2"></i> Voir les fonctionnalités
            </a>
        </div>
    </div>
</section>

    <!---------------------------------------Footer---------------------------------->
    <footer class="bg-gray-900 text-gray-300 py-12 px-6">
        <div class="container mx-auto">
            <div class="grid md:grid-cols-4 gap-8 mb-8">
                <!-----------------------------Info de la compagnie------------------------------>
                <div class="space-y-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gradient-to-r from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-coins text-white"></i>
                        </div>
                        <span class="text-xl font-bold text-white">Gestion Financière</span>
                    </div>
                    <p class="text-sm">Votre partenaire de confiance en matière de gestion et d'organisation financières.</p>
                </div>

                <!--------------------------PARTIE LIEN RAPIDE-------------------------------->
                <div>
                    <h3 class="text-white font-bold mb-4"><i class="fas fa-link mr-2"></i>Liens rapides</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#home" class="hover:text-green-400 transition-colors">Acceuil</a></li>
                        <li><a href="#features" class="hover:text-green-400 transition-colors">Caractéristiques</a></li>
                        <li><a href="#about" class="hover:text-green-400 transition-colors">A propos</a></li>
                        <li><a href="./utilisateur/login.html" class="hover:text-green-400 transition-colors">Se connecter</a></li>
                    </ul>
                </div>

                
                <div>
                    <h3 class="text-white font-bold mb-4"><i class="fas fa-headset mr-2"></i>Support</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-green-400 transition-colors">Centre d'aide</a></li>
                        <li><a href="#" class="hover:text-green-400 transition-colors">Contactez-nous</a></li>
                        <li><a href="#" class="hover:text-green-400 transition-colors">Documentation</a></li>
                        <li><a href="#" class="hover:text-green-400 transition-colors">FAQ</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-white font-bold mb-4"><i class="fas fa-shield-alt mr-1"></i>Légal</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-green-400 transition-colors">politique de confidentialité</a></li>
                        <li><a href="#" class="hover:text-green-400 transition-colors">Conditions d'utilisation</a></li>
                        <li><a href="#" class="hover:text-green-400 transition-colors">Politique relative aux cookies</a></li>
                        <li><a href="#" class="hover:text-green-400 transition-colors">Conformité</a></li>
                    </ul>
                </div>
            </div>

            
            <div class="border-t border-gray-800 pt-8 text-center text-sm">
                <p>&copy; 2025 Gestion Financière. Tous droit réserver.</p>
            </div>
        </div>
    </footer>
    <!-------------------------------------JavaScript----------------------------------->
    
    <script src="../assets/js/hamburger.js"></script>
    <script>
         // Activation/désactivation du menu hamburger
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');

        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        // Défilement fluide des liens de navigation
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                     // Fermeture automatique du menu mobile s'il est ouvert
                    mobileMenu.classList.add('hidden');
                }
            });
        });

        // Ombre de la barre de navigation au défilement 
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('shadow-lg');
            } else {
                navbar.classList.remove('shadow-lg');
            }
        });

        // fade-in au défilement
        const fadeInSections = document.querySelectorAll('.fade-in-section');
        
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '0';
                    entry.target.style.transform = 'translateY(30px)';
                    entry.target.style.transition = 'opacity 0.8s ease-out, transform 0.8s ease-out';
                    
                    setTimeout(() => {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }, 100);
                    
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        fadeInSections.forEach(section => {
            observer.observe(section);
        });
    </script>

</body>
</html>