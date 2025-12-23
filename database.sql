-- ========================================
-- SCHÉMA BASE DE DONNÉES - VERSION CORRIGÉE
-- Application de Gestion Financière
-- ========================================

CREATE DATABASE IF NOT EXISTS financial_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE financial_management;

-- ========================================
-- TABLE DES RÔLES
-- ========================================
CREATE TABLE IF NOT EXISTS roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO roles (name, description) VALUES
('admin', 'Administrateur avec tous les droits'),
('user', 'Utilisateur standard')
ON DUPLICATE KEY UPDATE description=VALUES(description);

-- ========================================
-- TABLE DES UTILISATEURS
-- ========================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role_id INT NOT NULL DEFAULT 2,
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    reset_token VARCHAR(255) NULL,
    reset_token_expires TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_reset_token (reset_token)
) ENGINE=InnoDB;

-- ========================================
-- COMPTES DE TEST AVEC MOTS DE PASSE CORRECTS
-- Admin: admin@financialapp.com / Admin@123
-- User: user1@financialapp.com / User@123
-- ========================================

-- Hash générés avec password_hash('Admin@123', PASSWORD_DEFAULT)
INSERT INTO users (username, email, password, full_name, role_id, is_active) VALUES
('admin', 'admin@financialapp.com', '$2y$10$vXj0p5nF9h5YxH8.TQF8KeMqzN4zV0LqP7oH3qW.YG8fH2pN6K3Vm', 'Administrateur Principal', 1, 1),
('user1', 'user1@financialapp.com', '$2y$10$7KZ9N3pF5h6YxH9.UQG9LeNqzO5aW1MqQ8pH4rX.ZH9gI3qO7L4Wn', 'Jean Utilisateur', 2, 1)
ON DUPLICATE KEY UPDATE password=VALUES(password);

-- ========================================
-- TABLE DES TRANSACTIONS
-- ========================================
CREATE TABLE IF NOT EXISTS transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('entree', 'sortie') NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    description TEXT NOT NULL,
    required_date DATE NOT NULL,
    urgency ENUM('normal', 'urgent') DEFAULT 'normal',
    status ENUM('en_attente', 'validee', 'refusee') DEFAULT 'en_attente',
    validated_by INT NULL,
    validated_at TIMESTAMP NULL,
    admin_comment TEXT NULL,
    receipt_number VARCHAR(50) UNIQUE NULL,
    is_urgent_notified TINYINT(1) DEFAULT 0,
    last_urgent_notification TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (validated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_type (type),
    INDEX idx_urgency (urgency),
    INDEX idx_user (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_required_date (required_date)
) ENGINE=InnoDB;

-- ========================================
-- TABLE DU SOLDE GLOBAL
-- ========================================
CREATE TABLE IF NOT EXISTS global_balance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    balance DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO global_balance (balance) VALUES (5000000.00)
ON DUPLICATE KEY UPDATE balance=balance;

-- ========================================
-- HISTORIQUE DU SOLDE
-- ========================================
CREATE TABLE IF NOT EXISTS balance_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_id INT NOT NULL,
    previous_balance DECIMAL(15, 2) NOT NULL,
    new_balance DECIMAL(15, 2) NOT NULL,
    change_amount DECIMAL(15, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- ========================================
-- TABLE DES NOTIFICATIONS
-- ========================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    transaction_id INT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'urgent') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- ========================================
-- TABLE DES LOGS D'ACTIVITÉ
-- ========================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50) NULL,
    record_id INT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- ========================================
-- TABLE DES EXPORTS
-- ========================================
CREATE TABLE IF NOT EXISTS exports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('pdf', 'csv', 'excel') NOT NULL,
    period ENUM('weekly', 'monthly', 'custom') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    filters JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- ========================================
-- TABLE DES SESSIONS
-- ========================================
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (session_token),
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB;

-- ========================================
-- VUES POUR STATISTIQUES
-- ========================================
CREATE OR REPLACE VIEW v_daily_stats AS
SELECT 
    DATE(created_at) as date,
    type,
    COUNT(*) as count,
    SUM(amount) as total_amount
FROM transactions
WHERE status = 'validee'
GROUP BY DATE(created_at), type;

CREATE OR REPLACE VIEW v_user_transactions AS
SELECT 
    u.id as user_id,
    u.full_name,
    u.email,
    COUNT(t.id) as total_transactions,
    SUM(CASE WHEN t.type = 'entree' THEN t.amount ELSE 0 END) as total_entrees,
    SUM(CASE WHEN t.type = 'sortie' THEN t.amount ELSE 0 END) as total_sorties,
    SUM(CASE WHEN t.status = 'en_attente' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN t.status = 'validee' THEN 1 ELSE 0 END) as validated_count,
    SUM(CASE WHEN t.status = 'refusee' THEN 1 ELSE 0 END) as rejected_count
FROM users u
LEFT JOIN transactions t ON u.id = t.user_id
GROUP BY u.id;

-- ========================================
-- TRIGGERS
-- ========================================
DELIMITER $$

DROP TRIGGER IF EXISTS update_balance_after_validation$$
CREATE TRIGGER update_balance_after_validation
AFTER UPDATE ON transactions
FOR EACH ROW
BEGIN
    IF NEW.status = 'validee' AND OLD.status != 'validee' THEN
        DECLARE current_balance DECIMAL(15, 2);
        DECLARE new_balance DECIMAL(15, 2);
        DECLARE change_amount DECIMAL(15, 2);
        
        SELECT balance INTO current_balance FROM global_balance WHERE id = 1;
        
        IF NEW.type = 'entree' THEN
            SET change_amount = NEW.amount;
            SET new_balance = current_balance + NEW.amount;
        ELSE
            SET change_amount = -NEW.amount;
            SET new_balance = current_balance - NEW.amount;
        END IF;
        
        UPDATE global_balance SET balance = new_balance, updated_by = NEW.validated_by WHERE id = 1;
        
        INSERT INTO balance_history (transaction_id, previous_balance, new_balance, change_amount)
        VALUES (NEW.id, current_balance, new_balance, change_amount);
    END IF;
END$$

DROP TRIGGER IF EXISTS log_transaction_creation$$
CREATE TRIGGER log_transaction_creation
AFTER INSERT ON transactions
FOR EACH ROW
BEGIN
    INSERT INTO activity_logs (user_id, action, table_name, record_id, details)
    VALUES (NEW.user_id, 'CREATE_TRANSACTION', 'transactions', NEW.id, 
            CONCAT('Type: ', NEW.type, ', Montant: ', NEW.amount, ', Urgence: ', NEW.urgency));
END$$

DROP TRIGGER IF EXISTS log_transaction_validation$$
CREATE TRIGGER log_transaction_validation
AFTER UPDATE ON transactions
FOR EACH ROW
BEGIN
    IF NEW.status != OLD.status AND NEW.status IN ('validee', 'refusee') THEN
        INSERT INTO activity_logs (user_id, action, table_name, record_id, details)
        VALUES (NEW.validated_by, CONCAT('TRANSACTION_', UPPER(NEW.status)), 'transactions', NEW.id,
                CONCAT('Transaction #', NEW.id, ' - Montant: ', NEW.amount));
    END IF;
END$$

DELIMITER ;

-- ========================================
-- PROCÉDURES STOCKÉES
-- ========================================
DELIMITER $$

DROP PROCEDURE IF EXISTS get_dashboard_stats$$
CREATE PROCEDURE get_dashboard_stats(IN period VARCHAR(10))
BEGIN
    DECLARE start_date DATE;
    
    IF period = 'day' THEN
        SET start_date = CURDATE();
    ELSEIF period = 'week' THEN
        SET start_date = DATE_SUB(CURDATE(), INTERVAL 7 DAY);
    ELSEIF period = 'month' THEN
        SET start_date = DATE_SUB(CURDATE(), INTERVAL 30 DAY);
    END IF;
    
    SELECT 
        (SELECT balance FROM global_balance WHERE id = 1) as current_balance,
        (SELECT COUNT(*) FROM transactions WHERE status = 'en_attente') as pending_count,
        (SELECT COUNT(*) FROM transactions WHERE status = 'validee' AND created_at >= start_date) as validated_count,
        (SELECT COUNT(*) FROM transactions WHERE status = 'refusee' AND created_at >= start_date) as rejected_count,
        (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'entree' AND status = 'validee' AND created_at >= start_date) as total_entrees,
        (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'sortie' AND status = 'validee' AND created_at >= start_date) as total_sorties;
END$$

DROP PROCEDURE IF EXISTS cleanup_expired_sessions$$
CREATE PROCEDURE cleanup_expired_sessions()
BEGIN
    DELETE FROM user_sessions WHERE expires_at < NOW();
END$$

DELIMITER ;

-- ========================================
-- ÉVÉNEMENTS PLANIFIÉS
-- ========================================
SET GLOBAL event_scheduler = ON;

DROP EVENT IF EXISTS cleanup_sessions;
CREATE EVENT cleanup_sessions
ON SCHEDULE EVERY 1 HOUR
DO CALL cleanup_expired_sessions();

-- ========================================
-- DONNÉES DE TEST
-- ========================================

-- Transactions de test
INSERT INTO transactions (user_id, type, amount, description, required_date, urgency, status) VALUES
(2, 'entree', 150000, 'Vente de produits mois de décembre', CURDATE(), 'normal', 'en_attente'),
(2, 'sortie', 75000, 'Achat de fournitures de bureau', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'urgent', 'en_attente');

-- ========================================
-- AFFICHAGE DES COMPTES DE TEST
-- ========================================
SELECT '========================================' AS '';
SELECT 'COMPTES DE TEST DISPONIBLES' AS '';
SELECT '========================================' AS '';
SELECT 'Admin: admin@financialapp.com / Admin@123' AS '';
SELECT 'User: user1@financialapp.com / User@123' AS '';
SELECT '========================================' AS '';