/**
 * SYSTÈME DE NOTIFICATIONS EN TEMPS RÉEL
 * Gestion des notifications web avec mise à jour automatique
 */

(function() {
    'use strict';

    const NOTIFICATION_CHECK_INTERVAL = 30000; // 30 secondes
    let notificationInterval = null;
    let isInitialized = false;

    /**
     * Récupérer les notifications non lues
     */
    async function fetchUnreadNotifications() {
        try {
            const response = await fetch('../api/notifications.php?unread=true');
            
            if (!response.ok) {
                throw new Error('Erreur réseau');
            }
            
            const data = await response.json();
            
            if (data.success) {
                updateNotificationUI(data.data, data.count);
                return data;
            } else {
                console.error('Erreur API notifications:', data.message);
                return null;
            }
        } catch (error) {
            console.error('Erreur fetch notifications:', error);
            return null;
        }
    }

    /**
     * Mettre à jour l'interface utilisateur
     */
    function updateNotificationUI(notifications, count) {
        // Mettre à jour le badge du compteur
        updateBadge(count);
        
        // Mettre à jour la liste déroulante
        updateDropdown(notifications);
        
        // Afficher une notification navigateur si nouvelle notification urgente
        checkForUrgentNotifications(notifications);
    }

    /**
     * Mettre à jour le badge de compteur
     */
    function updateBadge(count) {
        const badges = document.querySelectorAll('[data-notification-badge]');
        
        badges.forEach(badge => {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        });
    }

    /**
     * Mettre à jour le dropdown des notifications
     */
    function updateDropdown(notifications) {
        const dropdowns = document.querySelectorAll('[data-notification-dropdown]');
        
        dropdowns.forEach(dropdown => {
            if (notifications.length === 0) {
                dropdown.innerHTML = `
                    <div class="p-4 text-center text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        <p class="text-sm">Aucune notification</p>
                    </div>
                `;
            } else {
                let html = '';
                
                notifications.slice(0, 5).forEach(notif => {
                    const typeColors = {
                        'urgent': 'bg-red-50 border-l-4 border-red-500',
                        'warning': 'bg-yellow-50 border-l-4 border-yellow-500',
                        'success': 'bg-green-50 border-l-4 border-green-500',
                        'info': 'bg-blue-50 border-l-4 border-blue-500'
                    };
                    
                    const typeIcons = {
                        'urgent': '🔴',
                        'warning': '⚠️',
                        'success': '✅',
                        'info': 'ℹ️'
                    };
                    
                    const colorClass = typeColors[notif.type] || typeColors['info'];
                    const icon = typeIcons[notif.type] || typeIcons['info'];
                    const timeAgo = formatTimeAgo(notif.created_at);
                    
                    html += `
                        <div class="p-4 ${colorClass} hover:bg-opacity-80 cursor-pointer transition" 
                             data-notification-id="${notif.id}" 
                             onclick="markAsRead(${notif.id}, ${notif.transaction_id || 'null'})">
                            <div class="flex items-start">
                                <span class="text-xl mr-2">${icon}</span>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-gray-900">${escapeHtml(notif.title)}</p>
                                    <p class="text-sm text-gray-700 mt-1">${escapeHtml(notif.message)}</p>
                                    <p class="text-xs text-gray-500 mt-2">${timeAgo}</p>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                if (notifications.length > 5) {
                    html += `
                        <div class="p-3 text-center bg-gray-50 border-t">
                            <a href="../user/notifications.php" class="text-sm text-green-600 hover:text-green-700 font-medium">
                                Voir toutes les notifications (${notifications.length})
                            </a>
                        </div>
                    `;
                }
                
                dropdown.innerHTML = html;
            }
        });
    }

    /**
     * Vérifier les notifications urgentes
     */
    function checkForUrgentNotifications(notifications) {
        const urgentNotifications = notifications.filter(n => n.type === 'urgent' && n.is_read === '0');
        
        urgentNotifications.forEach(notif => {
            showBrowserNotification(notif);
        });
    }

    /**
     * Afficher une notification navigateur
     */
    function showBrowserNotification(notification) {
        if (!("Notification" in window)) {
            return;
        }
        
        if (Notification.permission === "granted") {
            createNotification(notification);
        } else if (Notification.permission !== "denied") {
            Notification.requestPermission().then(permission => {
                if (permission === "granted") {
                    createNotification(notification);
                }
            });
        }
    }

    /**
     * Créer une notification navigateur
     */
    function createNotification(notification) {
        const options = {
            body: notification.message,
            icon: '../assets/img/logo.png',
            badge: '../assets/img/badge.png',
            tag: 'notification-' + notification.id,
            requireInteraction: notification.type === 'urgent',
            vibrate: notification.type === 'urgent' ? [200, 100, 200] : [200]
        };
        
        const notif = new Notification(notification.title, options);
        
        notif.onclick = function() {
            window.focus();
            markAsRead(notification.id, notification.transaction_id);
            notif.close();
        };
        
        // Auto-fermer après 10 secondes si pas urgent
        if (notification.type !== 'urgent') {
            setTimeout(() => notif.close(), 10000);
        }
    }

    /**
     * Marquer une notification comme lue
     */
    window.markAsRead = async function(notificationId, transactionId) {
        try {
            const response = await fetch('../api/notifications.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    notification_id: notificationId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Rafraîchir les notifications
                await fetchUnreadNotifications();
                
                // Rediriger vers la transaction si applicable
                if (transactionId) {
                    const isAdmin = document.body.classList.contains('admin-page');
                    const url = isAdmin 
                        ? `../admin/transactions.php?id=${transactionId}`
                        : `../user/history.php?id=${transactionId}`;
                    window.location.href = url;
                }
            }
        } catch (error) {
            console.error('Erreur marquage notification:', error);
        }
    };

    /**
     * Marquer toutes les notifications comme lues
     */
    window.markAllAsRead = async function() {
        try {
            const response = await fetch('../api/notifications.php', {
                method: 'POST'
            });
            
            const data = await response.json();
            
            if (data.success) {
                await fetchUnreadNotifications();
            }
        } catch (error) {
            console.error('Erreur marquage toutes notifications:', error);
        }
    };

    /**
     * Basculer le dropdown
     */
    function toggleDropdown() {
        const dropdowns = document.querySelectorAll('[data-notification-dropdown]');
        dropdowns.forEach(dropdown => {
            dropdown.classList.toggle('hidden');
        });
    }

    /**
     * Formater le temps écoulé
     */
    function formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        if (seconds < 60) return 'À l\'instant';
        if (seconds < 3600) return Math.floor(seconds / 60) + ' min';
        if (seconds < 86400) return Math.floor(seconds / 3600) + 'h';
        if (seconds < 604800) return Math.floor(seconds / 86400) + 'j';
        
        return date.toLocaleDateString('fr-FR');
    }

    /**
     * Échapper le HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Demander la permission pour les notifications
     */
    function requestNotificationPermission() {
        if ("Notification" in window && Notification.permission === "default") {
            Notification.requestPermission();
        }
    }

    /**
     * Initialiser le système de notifications
     */
    function initNotifications() {
        if (isInitialized) return;
        
        // Demander la permission
        requestNotificationPermission();
        
        // Charger les notifications initiales
        fetchUnreadNotifications();
        
        // Démarrer le polling
        notificationInterval = setInterval(fetchUnreadNotifications, NOTIFICATION_CHECK_INTERVAL);
        
        // Écouter les clics sur le bouton de notification
        document.addEventListener('click', function(e) {
            const notifButton = e.target.closest('[data-notification-toggle]');
            if (notifButton) {
                e.preventDefault();
                e.stopPropagation();
                toggleDropdown();
            }
            
            // Fermer le dropdown si clic ailleurs
            if (!e.target.closest('[data-notification-dropdown]') && !notifButton) {
                document.querySelectorAll('[data-notification-dropdown]').forEach(d => {
                    d.classList.add('hidden');
                });
            }
        });
        
        isInitialized = true;
    }

    /**
     * Nettoyer à la fermeture de la page
     */
    window.addEventListener('beforeunload', function() {
        if (notificationInterval) {
            clearInterval(notificationInterval);
        }
    });

    // Initialiser dès que le DOM est prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNotifications);
    } else {
        initNotifications();
    }

    // Exposer globalement
    window.NotificationManager = {
        fetch: fetchUnreadNotifications,
        markAsRead: window.markAsRead,
        markAllAsRead: window.markAllAsRead
    };

})();