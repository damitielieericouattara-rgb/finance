<?php
// ========================================
// FONCTIONS DE NOTIFICATIONS
// ========================================

/**
 * Envoyer une notification push (si implémenté)
 */
function sendPushNotification($userId, $title, $message) {
    // À implémenter avec un service de push notifications
    // Exemple : Firebase Cloud Messaging, OneSignal, etc.
    return true;
}

/**
 * Envoyer une notification par email
 */
function sendEmailNotification($userId, $subject, $body) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT email, full_name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user) {
            return sendEmail($user['email'], $subject, $body, true);
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Erreur email notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtenir toutes les notifications d'un utilisateur
 */
function getAllNotifications($userId, $limit = 50) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT n.*, t.type as transaction_type, t.amount as transaction_amount
            FROM notifications n
            LEFT JOIN transactions t ON n.transaction_id = t.id
            WHERE n.user_id = ?
            ORDER BY n.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Erreur récupération notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Marquer toutes les notifications comme lues
 */
function markAllNotificationsAsRead($userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        return $stmt->execute([$userId]);
    } catch (Exception $e) {
        error_log("Erreur marquage notifications: " . $e->getMessage());
        return false;
    }
}

/**
 * Supprimer une notification
 */
function deleteNotification($notificationId, $userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        return $stmt->execute([$notificationId, $userId]);
    } catch (Exception $e) {
        error_log("Erreur suppression notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Supprimer les anciennes notifications
 */
function cleanupOldNotifications($daysOld = 30) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            DELETE FROM notifications 
            WHERE is_read = 1 
            AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        return $stmt->execute([$daysOld]);
    } catch (Exception $e) {
        error_log("Erreur nettoyage notifications: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtenir les statistiques de notifications
 */
function getNotificationStats($userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
                SUM(CASE WHEN type = 'urgent' THEN 1 ELSE 0 END) as urgent
            FROM notifications
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Erreur stats notifications: " . $e->getMessage());
        return [
            'total' => 0,
            'unread' => 0,
            'urgent' => 0
        ];
    }
}