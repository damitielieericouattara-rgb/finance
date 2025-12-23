<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// API pour gérer les notifications
requireLogin();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['user_id'];

try {
    switch ($method) {
        case 'GET':
            // Récupérer les notifications
            $unreadOnly = isset($_GET['unread']) && $_GET['unread'] === 'true';
            
            $db = getDB();
            $sql = "SELECT n.*, t.type as transaction_type, t.amount as transaction_amount
                    FROM notifications n
                    LEFT JOIN transactions t ON n.transaction_id = t.id
                    WHERE n.user_id = ?";
            
            if ($unreadOnly) {
                $sql .= " AND n.is_read = 0";
            }
            
            $sql .= " ORDER BY n.created_at DESC LIMIT 50";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$userId]);
            $notifications = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $notifications,
                'count' => count($notifications)
            ]);
            break;
            
        case 'PUT':
            // Marquer comme lu
            $data = json_decode(file_get_contents('php://input'), true);
            $notificationId = intval($data['notification_id'] ?? 0);
            
            if ($notificationId > 0) {
                $db = getDB();
                $stmt = $db->prepare("
                    UPDATE notifications 
                    SET is_read = 1 
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$notificationId, $userId]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Notification marquée comme lue'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID de notification invalide'
                ]);
            }
            break;
            
        case 'POST':
            // Marquer toutes comme lues
            $db = getDB();
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Toutes les notifications ont été marquées comme lues'
            ]);
            break;
            
        case 'DELETE':
            // Supprimer une notification
            $notificationId = intval($_GET['id'] ?? 0);
            
            if ($notificationId > 0) {
                $db = getDB();
                $stmt = $db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
                $stmt->execute([$notificationId, $userId]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Notification supprimée'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID de notification invalide'
                ]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Méthode non autorisée'
            ]);
    }
} catch (Exception $e) {
    error_log("Erreur API notifications: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue'
    ]);
}