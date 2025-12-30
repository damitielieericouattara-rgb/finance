/**
 * MENU HAMBURGER RESPONSIVE
 * Script universel pour toutes les pages (Landing, Admin, User)
 * À inclure dans toutes les pages : <script src="../assets/js/hamburger.js"></script>
 */

(function() {
    'use strict';
    
    /**
     * Initialiser le menu hamburger
     */
    function initHamburgerMenu() {
        // Créer le bouton hamburger s'il n'existe pas
        const nav = document.querySelector('nav');
        if (!nav) return;
        
        const navContainer = nav.querySelector('.max-w-7xl, .container');
        if (!navContainer) return;
        
        // Chercher ou créer le bouton hamburger
        let hamburgerBtn = document.getElementById('mobile-menu-btn');
        
        if (!hamburgerBtn) {
            // Créer le bouton hamburger
            hamburgerBtn = document.createElement('button');
            hamburgerBtn.id = 'mobile-menu-btn';
            hamburgerBtn.className = 'md:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-green-500 transition-all duration-300';
            hamburgerBtn.setAttribute('aria-label', 'Toggle menu');
            hamburgerBtn.innerHTML = `
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path class="hamburger-line hamburger-line-1" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16"></path>
                    <path class="hamburger-line hamburger-line-2" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12h16"></path>
                    <path class="hamburger-line hamburger-line-3" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 18h16"></path>
                </svg>
            `;
            
            // Insérer le bouton dans la navigation
            const navFlex = navContainer.querySelector('.flex.justify-between');
            if (navFlex) {
                const rightSection = navFlex.querySelector('.flex.items-center:last-child');
                if (rightSection) {
                    // Ajouter le bouton avant les autres éléments
                    rightSection.insertBefore(hamburgerBtn, rightSection.firstChild);
                }
            }
        }
        
        // Chercher ou créer le menu mobile
        let mobileMenu = document.getElementById('mobile-menu');
        
        if (!mobileMenu) {
            // Créer le menu mobile
            mobileMenu = document.createElement('div');
            mobileMenu.id = 'mobile-menu';
            mobileMenu.className = 'hidden md:hidden';
            
            // Récupérer les liens du menu desktop
            const desktopMenu = nav.querySelector('.hidden.md\\:flex, .hidden.md\\:ml-6');
            if (desktopMenu) {
                const links = desktopMenu.querySelectorAll('a');
                
                const menuContent = document.createElement('div');
                menuContent.className = 'px-2 pt-2 pb-3 space-y-1 bg-white dark:bg-gray-800 shadow-lg';
                
                links.forEach(link => {
                    const mobileLink = link.cloneNode(true);
                    mobileLink.className = 'block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors';
                    
                    // Marquer le lien actif
                    if (link.classList.contains('bg-green-600') || link.classList.contains('text-green-600')) {
                        mobileLink.classList.add('bg-green-600', 'text-white', '!text-white');
                        mobileLink.classList.remove('text-gray-700', 'dark:text-gray-300');
                    }
                    
                    menuContent.appendChild(mobileLink);
                });
                
                // Ajouter les boutons d'action (connexion, déconnexion, etc.)
                const actionButtons = nav.querySelectorAll('a[href*="login"], a[href*="logout"], a[href*="register"]');
                if (actionButtons.length > 0) {
                    const separator = document.createElement('div');
                    separator.className = 'border-t border-gray-200 dark:border-gray-700 my-2';
                    menuContent.appendChild(separator);
                    
                    actionButtons.forEach(btn => {
                        const mobileBtn = btn.cloneNode(true);
                        mobileBtn.className = 'block w-full text-center px-3 py-2 rounded-md text-base font-medium bg-green-600 text-white hover:bg-green-700 transition-colors';
                        menuContent.appendChild(mobileBtn);
                    });
                }
                
                mobileMenu.appendChild(menuContent);
            }
            
            // Insérer le menu après la navigation principale
            navContainer.appendChild(mobileMenu);
        }
        
        // Gérer l'ouverture/fermeture du menu
        hamburgerBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleMenu();
        });
        
        // Fermer le menu en cliquant en dehors
        document.addEventListener('click', function(e) {
            if (!nav.contains(e.target)) {
                closeMenu();
            }
        });
        
        // Fermer le menu en cliquant sur un lien
        const mobileLinks = mobileMenu.querySelectorAll('a');
        mobileLinks.forEach(link => {
            link.addEventListener('click', function() {
                closeMenu();
            });
        });
        
        // Gérer le redimensionnement de la fenêtre
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768) {
                closeMenu();
            }
        });
        
        // Animation CSS pour le menu hamburger
        addHamburgerStyles();
    }
    
    /**
     * Basculer l'état du menu
     */
    function toggleMenu() {
        const mobileMenu = document.getElementById('mobile-menu');
        const hamburgerBtn = document.getElementById('mobile-menu-btn');
        
        if (!mobileMenu || !hamburgerBtn) return;
        
        const isOpen = !mobileMenu.classList.contains('hidden');
        
        if (isOpen) {
            closeMenu();
        } else {
            openMenu();
        }
    }
    
    /**
     * Ouvrir le menu
     */
    function openMenu() {
        const mobileMenu = document.getElementById('mobile-menu');
        const hamburgerBtn = document.getElementById('mobile-menu-btn');
        
        if (!mobileMenu || !hamburgerBtn) return;
        
        mobileMenu.classList.remove('hidden');
        hamburgerBtn.classList.add('menu-open');
        
        // Animation d'entrée
        setTimeout(() => {
            mobileMenu.classList.add('animate-slideDown');
        }, 10);
        
        // Empêcher le scroll du body
        document.body.style.overflow = 'hidden';
    }
    
    /**
     * Fermer le menu
     */
    function closeMenu() {
        const mobileMenu = document.getElementById('mobile-menu');
        const hamburgerBtn = document.getElementById('mobile-menu-btn');
        
        if (!mobileMenu || !hamburgerBtn) return;
        
        mobileMenu.classList.remove('animate-slideDown');
        hamburgerBtn.classList.remove('menu-open');
        
        setTimeout(() => {
            mobileMenu.classList.add('hidden');
        }, 300);
        
        // Réactiver le scroll du body
        document.body.style.overflow = '';
    }
    
    /**
     * Ajouter les styles CSS pour l'animation
     */
    function addHamburgerStyles() {
        if (document.getElementById('hamburger-styles')) return;
        
        const style = document.createElement('style');
        style.id = 'hamburger-styles';
        style.textContent = `
            /* Animation du bouton hamburger */
            #mobile-menu-btn {
                transition: all 0.3s ease;
            }
            
            #mobile-menu-btn.menu-open {
                transform: rotate(90deg);
            }
            
            #mobile-menu-btn svg {
                transition: all 0.3s ease;
            }
            
            #mobile-menu-btn.menu-open .hamburger-line-1 {
                transform: rotate(45deg) translate(5px, 5px);
            }
            
            #mobile-menu-btn.menu-open .hamburger-line-2 {
                opacity: 0;
            }
            
            #mobile-menu-btn.menu-open .hamburger-line-3 {
                transform: rotate(-45deg) translate(7px, -6px);
            }
            
            /* Animation du menu */
            @keyframes slideDown {
                from {
                    opacity: 0;
                    transform: translateY(-10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .animate-slideDown {
                animation: slideDown 0.3s ease-out forwards;
            }
            
            /* Style du menu mobile */
            #mobile-menu {
                transition: all 0.3s ease;
            }
            
            /* Masquer le menu desktop sur mobile */
            @media (max-width: 767px) {
                .hidden.md\\:flex,
                .hidden.md\\:ml-6 {
                    display: none !important;
                }
                
                /* Masquer les boutons de connexion/déconnexion desktop sur mobile */
                nav .flex.items-center:last-child > a[href*="login"],
                nav .flex.items-center:last-child > a[href*="logout"],
                nav .flex.items-center:last-child > a[href*="register"] {
                    display: none !important;
                }
            }
            
            /* Afficher le menu desktop sur tablette et plus */
            @media (min-width: 768px) {
                #mobile-menu,
                #mobile-menu-btn {
                    display: none !important;
                }
                
                .hidden.md\\:flex,
                .hidden.md\\:ml-6 {
                    display: flex !important;
                }
            }
            
            /* Style amélioré pour les liens du menu mobile */
            #mobile-menu a {
                transition: all 0.2s ease;
            }
            
            #mobile-menu a:active {
                transform: scale(0.98);
            }
            
            /* Overlay sombre quand le menu est ouvert */
            #mobile-menu.animate-slideDown::before {
                content: '';
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.3);
                z-index: -1;
                animation: fadeIn 0.3s ease;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
        `;
        
        document.head.appendChild(style);
    }
    
    /**
     * Initialiser au chargement du DOM
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHamburgerMenu);
    } else {
        initHamburgerMenu();
    }
    
    // Exposer globalement pour debug
    window.HamburgerMenu = {
        toggle: toggleMenu,
        open: openMenu,
        close: closeMenu
    };
    
})();