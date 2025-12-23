/**
 * SYSTÈME DE THÈME DARK/LIGHT
 * Gestion du mode sombre et clair avec sauvegarde localStorage
 */

(function() {
    'use strict';

    const THEME_KEY = 'app_theme';
    const THEME_DARK = 'dark';
    const THEME_LIGHT = 'light';

    /**
     * Obtenir le thème actuel
     */
    function getCurrentTheme() {
        return localStorage.getItem(THEME_KEY) || THEME_LIGHT;
    }

    /**
     * Appliquer le thème
     */
    function applyTheme(theme) {
        const html = document.documentElement;
        
        if (theme === THEME_DARK) {
            html.classList.add('dark');
        } else {
            html.classList.remove('dark');
        }
        
        localStorage.setItem(THEME_KEY, theme);
        
        // Mettre à jour l'icône du bouton
        updateThemeButton(theme);
    }

    /**
     * Basculer entre les thèmes
     */
    function toggleTheme() {
        const currentTheme = getCurrentTheme();
        const newTheme = currentTheme === THEME_DARK ? THEME_LIGHT : THEME_DARK;
        applyTheme(newTheme);
    }

    /**
     * Mettre à jour l'icône du bouton
     */
    function updateThemeButton(theme) {
        const buttons = document.querySelectorAll('[data-theme-toggle]');
        
        buttons.forEach(button => {
            const sunIcon = button.querySelector('[data-theme-icon="sun"]');
            const moonIcon = button.querySelector('[data-theme-icon="moon"]');
            
            if (sunIcon && moonIcon) {
                if (theme === THEME_DARK) {
                    sunIcon.classList.remove('hidden');
                    moonIcon.classList.add('hidden');
                } else {
                    sunIcon.classList.add('hidden');
                    moonIcon.classList.remove('hidden');
                }
            }
        });
    }

    /**
     * Initialiser le système de thème
     */
    function initTheme() {
        // Appliquer le thème sauvegardé
        const savedTheme = getCurrentTheme();
        applyTheme(savedTheme);
        
        // Écouter les clics sur les boutons de basculement
        document.addEventListener('click', function(e) {
            const toggleButton = e.target.closest('[data-theme-toggle]');
            if (toggleButton) {
                e.preventDefault();
                toggleTheme();
            }
        });
        
        // Écouter les changements de préférence système (optionnel)
        if (window.matchMedia) {
            const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
            
            darkModeQuery.addEventListener('change', function(e) {
                // Ne changer que si l'utilisateur n'a pas de préférence sauvegardée
                if (!localStorage.getItem(THEME_KEY)) {
                    applyTheme(e.matches ? THEME_DARK : THEME_LIGHT);
                }
            });
        }
    }

    /**
     * Créer le bouton de basculement de thème
     */
    function createThemeToggleButton() {
        const button = document.createElement('button');
        button.setAttribute('data-theme-toggle', '');
        button.className = 'p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors';
        button.title = 'Changer de thème';
        
        button.innerHTML = `
            <svg data-theme-icon="sun" class="h-6 w-6 text-gray-700 dark:text-gray-300 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <svg data-theme-icon="moon" class="h-6 w-6 text-gray-700 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
        `;
        
        return button;
    }

    /**
     * Injecter le bouton de thème dans la navigation
     */
    function injectThemeToggle() {
        // Chercher un conteneur approprié dans la navigation
        const navContainers = document.querySelectorAll('nav .flex.items-center.space-x-4, nav .flex.items-center.space-x-2');
        
        navContainers.forEach(container => {
            // Vérifier qu'il n'y a pas déjà un bouton
            if (!container.querySelector('[data-theme-toggle]')) {
                const button = createThemeToggleButton();
                // Insérer avant le dernier élément (généralement le bouton de déconnexion)
                if (container.lastElementChild) {
                    container.insertBefore(button, container.lastElementChild);
                } else {
                    container.appendChild(button);
                }
            }
        });
    }

    // Initialiser dès que le DOM est prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initTheme();
            injectThemeToggle();
        });
    } else {
        initTheme();
        injectThemeToggle();
    }

    // Exposer les fonctions globalement si nécessaire
    window.ThemeManager = {
        toggle: toggleTheme,
        apply: applyTheme,
        getCurrent: getCurrentTheme
    };

})();

/**
 * CONFIGURATION TAILWIND POUR LE MODE SOMBRE
 * 
 * Pour activer complètement le mode sombre, ajoutez ceci dans votre HTML :
 * 
 * <script src="https://cdn.tailwindcss.com"></script>
 * <script>
 *   tailwind.config = {
 *     darkMode: 'class',
 *     theme: {
 *       extend: {}
 *     }
 *   }
 * </script>
 * 
 * Ensuite, utilisez les classes dark: dans votre HTML :
 * 
 * Exemples :
 * - bg-white dark:bg-gray-800
 * - text-gray-900 dark:text-white
 * - border-gray-300 dark:border-gray-600
 * - hover:bg-gray-100 dark:hover:bg-gray-700
 */