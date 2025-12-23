<?php
// ========================================
// CONFIGURATION BASE DE DONNÉES
// ========================================

class Database {
    private $host = "localhost";
    private $db_name = "financial_management";
    private $username = "root";
    private $password = "";
    private $conn;
    
    /**
     * Établir la connexion à la base de données
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch(PDOException $e) {
            error_log("Erreur de connexion: " . $e->getMessage());
            die("Erreur de connexion à la base de données");
        }
        
        return $this->conn;
    }
    
    /**
     * Fermer la connexion
     */
    public function closeConnection() {
        $this->conn = null;
    }
}
?>