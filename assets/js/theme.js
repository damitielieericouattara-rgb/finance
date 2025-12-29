/**
 * Gestion du thème sombre
 * À inclure dans toutes les pages : <script src="../assets/js/theme.js"></script>
 */

(function() {
    'use strict';
    
    const htmlElement = document.documentElement;
    
    // Charger le thème sauvegardé
    function loadTheme() {
        const savedTheme = localStorage.getItem('theme');
        
        if (savedTheme === 'dark') {
            htmlElement.classList.add('dark');
        } else if (savedTheme === 'light') {
            htmlElement.classList.remove('dark');
        } else {
            // Utiliser la préférence système par défaut
            if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                htmlElement.classList.add('dark');
            } else {
                htmlElement.classList.remove('dark');
            }
        }
    }
    
    // Basculer le thème
    function toggleTheme() {
        htmlElement.classList.toggle('dark');
        const isDark = htmlElement.classList.contains('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        
        // Animation de transition
        document.body.style.transition = 'background-color 0.3s ease';
        setTimeout(() => {
            document.body.style.transition = '';
        }, 300);
    }
    
    // Initialiser le thème au chargement
    loadTheme();
    
    // Attendre que le DOM soit chargé
    document.addEventListener('DOMContentLoaded', function() {
        // Trouver tous les boutons de bascule de thème
        const themeToggles = document.querySelectorAll('#themeToggle, .theme-toggle, [data-theme-toggle]');
        
        themeToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                toggleTheme();
            });
        });
        
        // Écouter les changements de préférence système
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem('theme')) {
                if (e.matches) {
                    htmlElement.classList.add('dark');
                } else {
                    htmlElement.classList.remove('dark');
                }
            }
        });
    });
    
    // Exposer les fonctions globalement si nécessaire
    window.darkModeToggle = toggleTheme;
    window.loadDarkMode = loadTheme;
})();