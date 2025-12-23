<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

requireAdmin(); // Seuls les admins peuvent accéder à cette API

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Récupérer les utilisateurs
            if (isset($_GET['id'])) {
                // Un seul utilisateur
                $userId = intval($_GET['id']);
                $user = getUserById($userId);
                
                if ($user) {
                    // Ne pas renvoyer le mot de passe
                    unset($user['password']);
                    
                    echo json_encode([
                        'success' => true,
                        'data' => $user
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Utilisateur non trouvé'
                    ]);
                }
            } else {
                // Tous les utilisateurs
                $users = getAllUsers();
                
                // Retirer les mots de passe
                foreach ($users as &$user) {
                    unset($user['password']);
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => $users,
                    'count' => count($users)
                ]);
            }
            break;
            
        case 'POST':
            // Créer un utilisateur
            $data = json_decode(file_get_contents('php://input'), true);
            
            $username = cleanInput($data['username'] ?? '');
            $email = cleanInput($data['email'] ?? '');
            $password = $data['password'] ?? '';
            $fullName = cleanInput($data['full_name'] ?? '');
            $roleId = intval($data['role_id'] ?? 2);
            
            // Validation
            if (empty($username) || empty($email) || empty($password) || empty($fullName)) {
                throw new Exception("Tous les champs sont requis");
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Email invalide");
            }
            
            if (strlen($password) < PASSWORD_MIN_LENGTH) {
                throw new Exception("Mot de passe trop court");
            }
            
            if (!in_array($roleId, [1, 2])) {
                throw new Exception("Rôle invalide");
            }
            
            $result = createUser($username, $email, $password, $fullName, $roleId);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Utilisateur créé avec succès'
                ]);
            } else {
                throw new Exception("Erreur lors de la création");
            }
            break;
            
        case 'PUT':
            // Mettre à jour un utilisateur
            $data = json_decode(file_get_contents('php://input'), true);
            
            $id = intval($data['id'] ?? 0);
            $username = cleanInput($data['username'] ?? '');
            $email = cleanInput($data['email'] ?? '');
            $fullName = cleanInput($data['full_name'] ?? '');
            $roleId = intval($data['role_id'] ?? 2);
            $isActive = intval($data['is_active'] ?? 1);
            
            if ($id <= 0) {
                throw new Exception("ID utilisateur invalide");
            }
            
            $result = updateUser($id, $username, $email, $fullName, $roleId, $isActive);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Utilisateur mis à jour avec succès'
                ]);
            } else {
                throw new Exception("Erreur lors de la mise à jour");
            }
            break;
            
        case 'DELETE':
            // Supprimer un utilisateur
            $userId = intval($_GET['id'] ?? 0);
            
            if ($userId <= 0) {
                throw new Exception("ID utilisateur invalide");
            }
            
            // Ne pas supprimer le super admin
            if ($userId === 1) {
                throw new Exception("Impossible de supprimer le super admin");
            }
            
            $result = deleteUser($userId);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Utilisateur supprimé avec succès'
                ]);
            } else {
                throw new Exception("Erreur lors de la suppression");
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
    error_log("Erreur API users: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}