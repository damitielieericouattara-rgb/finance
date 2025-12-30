/**
 * MENU HAMBURGER RESPONSIVE UNIVERSEL V2.0
 * Compatible : Landing, Admin, User
 * Fonctionnalités : Animation fluide, fermeture automatique, gestion thème
 */

(function() {
    'use strict';
    
    // Configuration
    const CONFIG = {
        breakpoint: 768, // md breakpoint Tailwind
        animationDuration: 300,
        closeOnLinkClick: true,
        closeOnOutsideClick: true
    };
    
    let mobileMenu = null;
    let hamburgerBtn = null;
    let isOpen = false;
    
    /**
     * Initialiser le menu hamburger
     */
    function init() {
        createHamburgerButton();
        createMobileMenu();
        attachEventListeners();
        handleResize();
        addStyles();
    }
    
    /**
     * Créer le bouton hamburger
     */
    function createHamburgerButton() {
        const nav = document.querySelector('nav');
        if (!nav) return;
        
        // Chercher le bouton existant
        hamburgerBtn = document.getElementById('mobile-menu-btn');
        
        if (!hamburgerBtn) {
            hamburgerBtn = document.createElement('button');
            hamburgerBtn.id = 'mobile-menu-btn';
            hamburgerBtn.className = 'md:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-green-500 transition-all duration-300';
            hamburgerBtn.setAttribute('aria-label', 'Toggle menu');
            hamburgerBtn.setAttribute('aria-expanded', 'false');
            
            hamburgerBtn.innerHTML = `
                <svg class="hamburger-icon h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path class="line line-top" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16"></path>
                    <path class="line line-middle" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12h16"></path>
                    <path class="line line-bottom" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 18h16"></path>
                </svg>
            `;
            
            // Insérer le bouton
            const navContainer = nav.querySelector('.max-w-7xl, .container');
            if (navContainer) {
                const navFlex = navContainer.querySelector('.flex.justify-between, .flex.items-center.justify-between');
                if (navFlex) {
                    const rightSection = navFlex.querySelector('.flex.items-center:last-child, .hidden.md\\:flex');
                    if (rightSection) {
                        rightSection.parentNode.insertBefore(hamburgerBtn, rightSection);
                    } else {
                        navFlex.appendChild(hamburgerBtn);
                    }
                }
            }
        }
    }
    
    /**
     * Créer le menu mobile
     */
    function createMobileMenu() {
        const nav = document.querySelector('nav');
        if (!nav) return;
        
        mobileMenu = document.getElementById('mobile-menu');
        
        if (!mobileMenu) {
            mobileMenu = document.createElement('div');
            mobileMenu.id = 'mobile-menu';
            mobileMenu.className = 'mobile-menu-container';
            mobileMenu.setAttribute('aria-hidden', 'true');
            
            // Récupérer les liens du menu desktop
            const desktopMenu = nav.querySelector('.hidden.md\\:flex, .hidden.md\\:ml-6');
            const menuItems = [];
            
            if (desktopMenu) {
                const links = desktopMenu.querySelectorAll('a');
                links.forEach(link => {
                    const href = link.getAttribute('href');
                    const text = link.textContent.trim();
                    const isActive = link.classList.contains('bg-green-600') || 
                                    link.classList.contains('text-green-600') ||
                                    link.classList.contains('font-medium');
                    
                    menuItems.push({ href, text, isActive });
                });
            }
            
            // Construire le menu
            let menuHTML = '<div class="mobile-menu-content">';
            
            // Logo et titre (optionnel)
            const logo = nav.querySelector('svg.h-8');
            const siteName = nav.querySelector('.text-xl.font-bold, .text-2xl.font-bold');
            
            if (logo && siteName) {
                menuHTML += `
                    <div class="mobile-menu-header">
                        ${logo.outerHTML}
                        <span class="mobile-menu-title">${siteName.textContent}</span>
                    </div>
                `;
            }
            
            // Navigation principale
            menuHTML += '<nav class="mobile-menu-nav">';
            menuItems.forEach(item => {
                const activeClass = item.isActive ? 'mobile-menu-link-active' : '';
                menuHTML += `
                    <a href="${item.href}" class="mobile-menu-link ${activeClass}">
                        ${item.text}
                    </a>
                `;
            });
            menuHTML += '</nav>';
            
            // Boutons d'action (connexion, déconnexion, etc.)
            const actionButtons = nav.querySelectorAll('a[href*="login"], a[href*="logout"], a[href*="register"]');
            if (actionButtons.length > 0) {
                menuHTML += '<div class="mobile-menu-actions">';
                actionButtons.forEach(btn => {
                    const isLogout = btn.href.includes('logout');
                    const text = btn.textContent.trim() || (isLogout ? 'Déconnexion' : 'Connexion');
                    const btnClass = isLogout ? 'mobile-menu-action-danger' : 'mobile-menu-action-primary';
                    menuHTML += `
                        <a href="${btn.href}" class="mobile-menu-action ${btnClass}">
                            ${text}
                        </a>
                    `;
                });
                menuHTML += '</div>';
            }
            
            menuHTML += '</div>';
            mobileMenu.innerHTML = menuHTML;
            
            // Insérer après la navigation
            nav.appendChild(mobileMenu);
        }
    }
    
    /**
     * Attacher les événements
     */
    function attachEventListeners() {
        if (!hamburgerBtn || !mobileMenu) return;
        
        // Clic sur le bouton
        hamburgerBtn.addEventListener('click', handleToggle);
        
        // Clic sur les liens du menu
        if (CONFIG.closeOnLinkClick) {
            const links = mobileMenu.querySelectorAll('a');
            links.forEach(link => {
                link.addEventListener('click', () => {
                    setTimeout(closeMenu, 100);
                });
            });
        }
        
        // Clic en dehors du menu
        if (CONFIG.closeOnOutsideClick) {
            document.addEventListener('click', handleOutsideClick);
        }
        
        // Échap pour fermer
        document.addEventListener('keydown', handleEscape);
        
        // Redimensionnement
        window.addEventListener('resize', handleResize);
    }
    
    /**
     * Basculer le menu
     */
    function handleToggle(e) {
        e.preventDefault();
        e.stopPropagation();
        
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
        if (isOpen || !mobileMenu || !hamburgerBtn) return;
        
        isOpen = true;
        
        // Classes et attributs
        mobileMenu.classList.add('mobile-menu-open');
        mobileMenu.classList.remove('mobile-menu-closed');
        mobileMenu.setAttribute('aria-hidden', 'false');
        
        hamburgerBtn.classList.add('hamburger-open');
        hamburgerBtn.setAttribute('aria-expanded', 'true');
        
        // Empêcher le scroll
        document.body.style.overflow = 'hidden';
        
        // Animation d'entrée
        setTimeout(() => {
            mobileMenu.classList.add('mobile-menu-visible');
        }, 10);
    }
    
    /**
     * Fermer le menu
     */
    function closeMenu() {
        if (!isOpen || !mobileMenu || !hamburgerBtn) return;
        
        isOpen = false;
        
        // Retirer l'animation
        mobileMenu.classList.remove('mobile-menu-visible');
        
        // Attendre la fin de l'animation
        setTimeout(() => {
            mobileMenu.classList.remove('mobile-menu-open');
            mobileMenu.classList.add('mobile-menu-closed');
            mobileMenu.setAttribute('aria-hidden', 'true');
            
            hamburgerBtn.classList.remove('hamburger-open');
            hamburgerBtn.setAttribute('aria-expanded', 'false');
            
            // Réactiver le scroll
            document.body.style.overflow = '';
        }, CONFIG.animationDuration);
    }
    
    /**
     * Gérer le clic en dehors
     */
    function handleOutsideClick(e) {
        if (!isOpen || !mobileMenu || !hamburgerBtn) return;
        
        const isClickInside = mobileMenu.contains(e.target) || hamburgerBtn.contains(e.target);
        
        if (!isClickInside) {
            closeMenu();
        }
    }
    
    /**
     * Gérer la touche Échap
     */
    function handleEscape(e) {
        if (e.key === 'Escape' && isOpen) {
            closeMenu();
        }
    }
    
    /**
     * Gérer le redimensionnement
     */
    function handleResize() {
        if (window.innerWidth >= CONFIG.breakpoint) {
            if (isOpen) {
                closeMenu();
            }
            if (hamburgerBtn) {
                hamburgerBtn.style.display = 'none';
            }
            if (mobileMenu) {
                mobileMenu.style.display = 'none';
            }
        } else {
            if (hamburgerBtn) {
                hamburgerBtn.style.display = 'inline-flex';
            }
        }
    }
    
    /**
     * Ajouter les styles CSS
     */
    function addStyles() {
        if (document.getElementById('mobile-menu-styles')) return;
        
        const style = document.createElement('style');
        style.id = 'mobile-menu-styles';
        style.textContent = `
            /* Container du menu mobile */
            .mobile-menu-container {
                position: fixed;
                top: 64px; /* Hauteur de la nav */
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
                display: none;
                opacity: 0;
                transition: opacity ${CONFIG.animationDuration}ms ease;
            }
            
            .mobile-menu-open {
                display: block;
            }
            
            .mobile-menu-visible {
                opacity: 1;
            }
            
            .mobile-menu-closed {
                display: none;
            }
            
            /* Contenu du menu */
            .mobile-menu-content {
                background: white;
                max-height: calc(100vh - 64px);
                overflow-y: auto;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
                transform: translateY(-20px);
                transition: transform ${CONFIG.animationDuration}ms ease;
            }
            
            .mobile-menu-visible .mobile-menu-content {
                transform: translateY(0);
            }
            
            /* Dark mode */
            .dark .mobile-menu-content {
                background: #1f2937;
            }
            
            /* Header du menu */
            .mobile-menu-header {
                display: flex;
                align-items: center;
                padding: 1rem 1.5rem;
                border-bottom: 1px solid #e5e7eb;
                gap: 0.75rem;
            }
            
            .dark .mobile-menu-header {
                border-bottom-color: #374151;
            }
            
            .mobile-menu-header svg {
                width: 2rem;
                height: 2rem;
                color: #059669;
            }
            
            .mobile-menu-title {
                font-size: 1.25rem;
                font-weight: bold;
                color: #111827;
            }
            
            .dark .mobile-menu-title {
                color: white;
            }
            
            /* Navigation */
            .mobile-menu-nav {
                padding: 0.5rem;
            }
            
            .mobile-menu-link {
                display: block;
                padding: 0.875rem 1rem;
                color: #374151;
                font-weight: 500;
                border-radius: 0.5rem;
                transition: all 0.2s ease;
                margin-bottom: 0.25rem;
            }
            
            .mobile-menu-link:hover {
                background: #f3f4f6;
                color: #059669;
            }
            
            .mobile-menu-link-active {
                background: #059669;
                color: white !important;
            }
            
            .mobile-menu-link-active:hover {
                background: #047857;
            }
            
            .dark .mobile-menu-link {
                color: #d1d5db;
            }
            
            .dark .mobile-menu-link:hover {
                background: #374151;
                color: #10b981;
            }
            
            /* Actions (boutons) */
            .mobile-menu-actions {
                padding: 1rem;
                border-top: 1px solid #e5e7eb;
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .dark .mobile-menu-actions {
                border-top-color: #374151;
            }
            
            .mobile-menu-action {
                display: block;
                text-align: center;
                padding: 0.875rem 1rem;
                border-radius: 0.5rem;
                font-weight: 600;
                transition: all 0.2s ease;
            }
            
            .mobile-menu-action-primary {
                background: #059669;
                color: white;
            }
            
            .mobile-menu-action-primary:hover {
                background: #047857;
                transform: translateY(-2px);
                box-shadow: 0 4px 6px rgba(5, 150, 105, 0.3);
            }
            
            .mobile-menu-action-danger {
                background: #ef4444;
                color: white;
            }
            
            .mobile-menu-action-danger:hover {
                background: #dc2626;
                transform: translateY(-2px);
                box-shadow: 0 4px 6px rgba(239, 68, 68, 0.3);
            }
            
            /* Animation du bouton hamburger */
            .hamburger-icon {
                transition: transform 0.3s ease;
            }
            
            .hamburger-open .hamburger-icon {
                transform: rotate(90deg);
            }
            
            .hamburger-icon .line {
                transition: all 0.3s ease;
                transform-origin: center;
            }
            
            .hamburger-open .line-top {
                transform: translateY(6px) rotate(45deg);
            }
            
            .hamburger-open .line-middle {
                opacity: 0;
            }
            
            .hamburger-open .line-bottom {
                transform: translateY(-6px) rotate(-45deg);
            }
            
            /* Masquer le menu desktop sur mobile */
            @media (max-width: 767px) {
                nav .hidden.md\\:flex,
                nav .hidden.md\\:ml-6 {
                    display: none !important;
                }
            }
            
            /* Afficher le menu desktop sur tablette+ */
            @media (min-width: 768px) {
                .mobile-menu-container,
                #mobile-menu-btn {
                    display: none !important;
                }
            }
            
            /* Scrollbar du menu */
            .mobile-menu-content::-webkit-scrollbar {
                width: 6px;
            }
            
            .mobile-menu-content::-webkit-scrollbar-track {
                background: #f3f4f6;
            }
            
            .mobile-menu-content::-webkit-scrollbar-thumb {
                background: #059669;
                border-radius: 3px;
            }
            
            .dark .mobile-menu-content::-webkit-scrollbar-track {
                background: #1f2937;
            }
            
            /* Animation de pulsation pour les notifications */
            @keyframes pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.5; }
            }
            
            .mobile-menu-link.has-notification::after {
                content: '';
                position: absolute;
                right: 1rem;
                top: 50%;
                transform: translateY(-50%);
                width: 8px;
                height: 8px;
                background: #ef4444;
                border-radius: 50%;
                animation: pulse 2s infinite;
            }
        `;
        
        document.head.appendChild(style);
    }
    
    /**
     * API publique
     */
    window.HamburgerMenu = {
        open: openMenu,
        close: closeMenu,
        toggle: () => isOpen ? closeMenu() : openMenu(),
        isOpen: () => isOpen
    };
    
    // Initialiser au chargement
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})();