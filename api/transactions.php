<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

requireLogin();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['user_id'];
$isAdmin = isAdmin();

try {
    switch ($method) {
        case 'GET':
            // Récupérer les transactions
            $filters = [];
            
            if (!$isAdmin) {
                $filters['user_id'] = $userId;
            }
            
            if (isset($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }
            
            if (isset($_GET['type'])) {
                $filters['type'] = $_GET['type'];
            }
            
            if (isset($_GET['urgency'])) {
                $filters['urgency'] = $_GET['urgency'];
            }
            
            $page = max(1, intval($_GET['page'] ?? 1));
            $perPage = min(100, max(10, intval($_GET['per_page'] ?? 20)));
            
            $transactions = getTransactions($filters, $page, $perPage);
            $total = countTransactions($filters);
            
            echo json_encode([
                'success' => true,
                'data' => $transactions,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage)
                ]
            ]);
            break;
            
        case 'POST':
            // Créer une transaction
            $data = json_decode(file_get_contents('php://input'), true);
            
            $type = $data['type'] ?? '';
            $amount = floatval($data['amount'] ?? 0);
            $description = cleanInput($data['description'] ?? '');
            $requiredDate = $data['required_date'] ?? '';
            $urgency = $data['urgency'] ?? 'normal';
            
            // Validation
            if (!in_array($type, ['entree', 'sortie'])) {
                throw new Exception("Type de transaction invalide");
            }
            
            if ($amount <= 0) {
                throw new Exception("Le montant doit être supérieur à zéro");
            }
            
            if (empty($description)) {
                throw new Exception("La description est requise");
            }
            
            if (empty($requiredDate) || strtotime($requiredDate) < strtotime('today')) {
                throw new Exception("Date invalide");
            }
            
            $transactionId = createTransaction($userId, $type, $amount, $description, $requiredDate, $urgency);
            
            if ($transactionId) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Transaction créée avec succès',
                    'transaction_id' => $transactionId
                ]);
            } else {
                throw new Exception("Erreur lors de la création");
            }
            break;
            
        case 'PUT':
            // Mettre à jour une transaction (admin seulement)
            if (!$isAdmin) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ]);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $transactionId = intval($data['transaction_id'] ?? 0);
            $action = $data['action'] ?? '';
            $comment = cleanInput($data['comment'] ?? '');
            
            if ($action === 'validate') {
                $result = validateTransaction($transactionId, $userId, $comment);
            } elseif ($action === 'reject') {
                if (empty($comment)) {
                    throw new Exception("Un motif de refus est requis");
                }
                $result = rejectTransaction($transactionId, $userId, $comment);
            } else {
                throw new Exception("Action invalide");
            }
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Transaction mise à jour avec succès'
                ]);
            } else {
                throw new Exception("Erreur lors de la mise à jour");
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
    error_log("Erreur API transactions: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}