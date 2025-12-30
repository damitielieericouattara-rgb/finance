 
// SCRIPT MENU HAMBURGER
 

document.addEventListener('DOMContentLoaded', function() {
    initMobileMenu();
    initSmoothScroll();
    initNavbarScroll();
});

 
// MENU MOBILE
 
function initMobileMenu() {
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (!mobileMenuBtn || !mobileMenu) return;
    
    // Toggle du menu
    mobileMenuBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        toggleMenu();
    });
    
    // Fermer le menu en cliquant sur un lien
    const menuLinks = mobileMenu.querySelectorAll('a');
    menuLinks.forEach(link => {
        link.addEventListener('click', function() {
            closeMenu();
        });
    });
    
    // Fermer le menu en cliquant à l'extérieur
    document.addEventListener('click', function(e) {
        if (!mobileMenu.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
            closeMenu();
        }
    });
    
    // Fermer le menu avec la touche Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeMenu();
        }
    });
    
    // Fermer le menu lors du redimensionnement vers desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            closeMenu();
        }
    });
}

function toggleMenu() {
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    
    if (mobileMenu.classList.contains('active')) {
        closeMenu();
    } else {
        openMenu();
    }
}

function openMenu() {
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    
    mobileMenu.classList.remove('hidden');
    mobileMenu.classList.add('active');
    
    // Animation de l'icône hamburger
    const svg = mobileMenuBtn.querySelector('svg');
    if (svg) {
        svg.style.transform = 'rotate(90deg)';
    }
    
    // Empêcher le scroll du body
    document.body.style.overflow = 'hidden';
}

function closeMenu() {
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    
    if (!mobileMenu) return;
    
    mobileMenu.classList.remove('active');
    
    setTimeout(() => {
        mobileMenu.classList.add('hidden');
    }, 300);
    
    // Réinitialiser l'icône hamburger
    const svg = mobileMenuBtn?.querySelector('svg');
    if (svg) {
        svg.style.transform = 'rotate(0deg)';
    }
    
    // Réactiver le scroll du body
    document.body.style.overflow = '';
}

 
// DÉFILEMENT FLUIDE
 
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            // Ignorer les liens vides ou #
            if (!href || href === '#') return;
            
            const target = document.querySelector(href);
            
            if (target) {
                e.preventDefault();
                
                // Calculer la position en tenant compte de la navbar fixe
                const navHeight = document.querySelector('nav')?.offsetHeight || 64;
                const targetPosition = target.offsetTop - navHeight;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
                
                // Fermer le menu mobile
                closeMenu();
            }
        });
    });
}

 
// NAVBAR AU SCROLL
 
function initNavbarScroll() {
    const navbar = document.querySelector('nav');
    if (!navbar) return;
    
    let lastScroll = 0;
    
    window.addEventListener('scroll', function() {
        const currentScroll = window.pageYOffset;
        
        // Ajouter/retirer l'ombre
        if (currentScroll > 50) {
            navbar.classList.add('scrolled', 'shadow-lg');
        } else {
            navbar.classList.remove('scrolled', 'shadow-lg');
        }
        
        lastScroll = currentScroll;
    });
}

 
// DÉTECTION RESPONSIVE
 
function isMobile() {
    return window.innerWidth < 768;
}

function isTablet() {
    return window.innerWidth >= 768 && window.innerWidth < 1024;
}

function isDesktop() {
    return window.innerWidth >= 1024;
}

 
// GESTION DES TABLEAUX
 
function makeTablesResponsive() {
    const tables = document.querySelectorAll('table:not(.table-responsive)');
    
    tables.forEach(table => {
        // Créer un wrapper si nécessaire
        if (!table.parentElement.classList.contains('table-container')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-container';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
        
        table.classList.add('table-responsive');
    });
}

// Appliquer au chargement
document.addEventListener('DOMContentLoaded', makeTablesResponsive);

 
// GESTION DES IMAGES
 
function optimizeImages() {
    const images = document.querySelectorAll('img:not([loading])');
    
    images.forEach(img => {
        img.setAttribute('loading', 'lazy');
        
        // Gérer les erreurs d'images
        img.addEventListener('error', function() {
            if (!this.classList.contains('error-handled')) {
                this.classList.add('error-handled');
                this.style.display = 'none';
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', optimizeImages);

 
// UTILITAIRES
 

// Empêcher le zoom lors du focus sur mobile
if (isMobile()) {
    const inputs = document.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        if (!input.style.fontSize) {
            input.style.fontSize = '16px';
        }
    });
}

// Gérer l'orientation de l'appareil
window.addEventListener('orientationchange', function() {
    closeMenu();
    
    // Attendre la fin de l'animation d'orientation
    setTimeout(() => {
        window.scrollTo(0, window.pageYOffset);
    }, 100);
});

// Éviter les débordements horizontaux
function preventHorizontalScroll() {
    let elements = document.querySelectorAll('*');
    
    elements.forEach(el => {
        if (el.scrollWidth > document.documentElement.clientWidth) {
            console.warn('Élément débordant détecté:', el);
        }
    });
}

// Exécuter en mode développement
if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    window.addEventListener('load', preventHorizontalScroll);
}

 
// EXPORT
 
window.responsiveUtils = {
    isMobile,
    isTablet,
    isDesktop,
    openMenu,
    closeMenu,
    toggleMenu
};