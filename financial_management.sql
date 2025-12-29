-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : sam. 27 déc. 2025 à 06:43
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `financial_management`
--

DELIMITER $$
--
-- Procédures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_to_balance` (IN `p_admin_id` INT, IN `p_amount_to_add` DECIMAL(15,2), IN `p_notes` TEXT)   BEGIN
    DECLARE old_balance DECIMAL(15, 2);
    DECLARE new_balance DECIMAL(15, 2);
    
    -- Récupérer le solde actuel
    SELECT balance INTO old_balance FROM global_balance WHERE id = 1 FOR UPDATE;
    
    -- Calculer le nouveau solde (AJOUT, pas remplacement)
    SET new_balance = old_balance + p_amount_to_add;
    
    -- Mettre à jour le solde
    UPDATE global_balance SET balance = new_balance, updated_by = p_admin_id WHERE id = 1;
    
    -- Enregistrer dans l'historique
    INSERT INTO balance_history (previous_balance, new_balance, change_amount, change_type, admin_id, notes)
    VALUES (old_balance, new_balance, p_amount_to_add, 'manual_add', p_admin_id, p_notes);
    
    -- Logger l'activité
    INSERT INTO activity_logs (user_id, action, table_name, details)
    VALUES (p_admin_id, 'ADD_TO_BALANCE', 'global_balance', 
            CONCAT('Ajout de ', p_amount_to_add, ' FCFA. Ancien: ', old_balance, ' → Nouveau: ', new_balance));
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `cleanup_expired_sessions` ()   BEGIN
    DELETE FROM email_verifications WHERE expires_at < NOW();
    UPDATE users SET reset_token = NULL, reset_token_expires = NULL WHERE reset_token_expires < NOW();
    UPDATE users SET remember_token = NULL, remember_expires = NULL WHERE remember_expires < NOW();
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_dashboard_stats` (IN `period` VARCHAR(10))   BEGIN
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `set_manual_balance` (IN `p_admin_id` INT, IN `p_new_balance` DECIMAL(15,2), IN `p_notes` TEXT)   BEGIN
    DECLARE old_balance DECIMAL(15, 2);
    DECLARE change_amt DECIMAL(15, 2);
    
    SELECT balance INTO old_balance FROM global_balance WHERE id = 1 FOR UPDATE;
    SET change_amt = p_new_balance - old_balance;
    
    UPDATE global_balance SET balance = p_new_balance, updated_by = p_admin_id WHERE id = 1;
    
    INSERT INTO balance_history (previous_balance, new_balance, change_amount, change_type, admin_id, notes)
    VALUES (old_balance, p_new_balance, change_amt, 'manual_set', p_admin_id, p_notes);
    
    INSERT INTO activity_logs (user_id, action, table_name, details)
    VALUES (p_admin_id, 'MANUAL_BALANCE_SET', 'global_balance', 
            CONCAT('Solde modifié de ', old_balance, ' à ', p_new_balance, '. Raison: ', COALESCE(p_notes, 'Non spécifié')));
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, NULL, 'LOGIN_FAILED', 'users', NULL, 'Tentative échouée pour: admin@financialapp.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 05:26:59'),
(2, NULL, 'REGISTER', 'users', 1, 'Nouvel utilisateur enregistré avec le rôle: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 05:28:04'),
(3, NULL, 'REGISTER', 'users', 2, 'Nouvel utilisateur enregistré avec le rôle: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 05:41:56'),
(4, NULL, 'LOGIN', 'users', 2, 'Connexion réussie en tant que admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 05:42:27'),
(5, NULL, 'LOGOUT', 'users', 2, 'Déconnexion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 05:43:23'),
(6, NULL, 'REGISTER', 'users', 3, 'Nouvel utilisateur enregistré avec le rôle: user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 05:44:08'),
(7, NULL, 'LOGIN', 'users', 3, 'Connexion réussie en tant que user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 05:44:38'),
(8, NULL, 'LOGOUT', 'users', 3, 'Déconnexion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 05:45:27'),
(9, NULL, 'LOGIN', 'users', 2, 'Connexion réussie en tant que admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 05:45:49'),
(10, NULL, 'LOGOUT', 'users', 2, 'Déconnexion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 05:46:57'),
(11, NULL, 'LOGIN', 'users', 3, 'Connexion réussie en tant que user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 05:47:20'),
(12, NULL, 'LOGOUT', 'users', 3, 'Déconnexion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 05:48:06'),
(13, NULL, 'LOGIN_FAILED', 'users', NULL, 'Tentative échouée pour: admin@financialapp.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 14:07:04'),
(14, NULL, 'LOGIN_FAILED', 'users', NULL, 'Tentative échouée pour: admin@financialapp.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 14:07:23'),
(15, NULL, 'LOGIN_FAILED', 'users', NULL, 'Tentative échouée pour: admin@financialapp.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-24 15:24:15'),
(16, NULL, 'LOGIN_FAILED', 'users', NULL, 'Tentative échouée pour: KJDSLKJLSD@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-24 16:02:34'),
(17, NULL, 'REGISTER', 'users', 6, 'Nouvel utilisateur avec role_id: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 06:32:05'),
(18, NULL, 'LOGIN_FAILED', 'users', NULL, 'Tentative échouée pour: ericelie30@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 06:32:44'),
(19, NULL, 'LOGIN_FAILED', 'users', NULL, 'Tentative échouée pour: ericelie30@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 06:32:54'),
(20, NULL, 'LOGIN_FAILED', 'users', NULL, 'Tentative échouée pour: ericelie30@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 06:33:06'),
(21, NULL, 'REGISTER', 'users', 7, 'Nouvel utilisateur avec role_id: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 06:36:55'),
(22, NULL, 'LOGIN_FAILED', 'users', NULL, 'Tentative avec mauvais rôle pour: jean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 06:37:21'),
(23, NULL, 'LOGIN', 'users', 7, 'Connexion réussie en tant que user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 06:37:34'),
(24, NULL, 'LOGOUT', 'users', 7, 'Déconnexion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 06:38:37'),
(25, NULL, 'REGISTER', 'users', 8, 'Nouvel utilisateur avec role_id: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 06:39:35'),
(26, NULL, 'LOGIN', 'users', 8, 'Connexion réussie en tant que admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 06:39:57'),
(27, NULL, 'LOGOUT', 'users', 8, 'Déconnexion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 07:11:35'),
(28, 9, 'REGISTER', 'users', 9, 'Nouvel utilisateur avec role_id: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 07:12:49'),
(29, 10, 'REGISTER', 'users', 10, 'Nouvel utilisateur avec role_id: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 07:13:54'),
(30, 9, 'LOGIN', 'users', 9, 'Connexion réussie en tant que user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 07:14:17'),
(31, 9, 'LOGOUT', 'users', 9, 'Déconnexion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 07:15:27'),
(32, 10, 'LOGIN', 'users', 10, 'Connexion réussie en tant que admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 07:15:46'),
(33, 10, 'LOGOUT', 'users', 10, 'Déconnexion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 07:17:59'),
(34, 10, 'LOGIN', 'users', 10, 'Connexion réussie en tant que admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 07:36:08'),
(35, 10, 'LOGOUT', 'users', 10, 'Déconnexion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 07:38:16'),
(36, 9, 'LOGIN', 'users', 9, 'Connexion réussie en tant que user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 07:40:34'),
(37, 9, 'LOGOUT', 'users', 9, 'Déconnexion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 07:41:26'),
(38, 10, 'LOGIN', 'users', 10, 'Connexion réussie en tant que admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 07:41:46'),
(39, 10, 'LOGOUT', 'users', 10, 'Déconnexion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 07:42:26'),
(40, 10, 'LOGIN', 'users', 10, 'Connexion réussie en tant que admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 12:04:30'),
(41, 10, 'LOGOUT', 'users', 10, 'Déconnexion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 12:14:57'),
(42, 9, 'LOGIN', 'users', 9, 'Connexion réussie en tant que user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 12:15:30'),
(43, 9, 'LOGOUT', 'users', 9, 'Déconnexion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 12:31:43'),
(44, 10, 'LOGIN', 'users', 10, 'Connexion réussie en tant que admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 13:44:43'),
(45, 10, 'LOGOUT', 'users', 10, 'Déconnexion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 13:45:55'),
(46, 10, 'LOGIN', 'users', 10, 'Connexion réussie en tant que admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 13:46:22'),
(47, 10, 'LOGOUT', 'users', 10, 'Déconnexion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 13:46:35'),
(48, 9, 'LOGIN', 'users', 9, 'Connexion réussie en tant que user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 13:46:59'),
(49, 9, 'LOGOUT', 'users', 9, 'Déconnexion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 13:48:06'),
(50, 10, 'LOGIN', 'users', 10, 'Connexion réussie en tant que admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 13:48:25'),
(51, 10, 'LOGIN', 'users', 10, 'Connexion réussie en tant que admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 14:43:42'),
(52, 10, 'MANUAL_BALANCE_SET', 'global_balance', NULL, 'Solde modifié de 100000000.00 FCFA à 100000000.00 FCFA. Raison: s', NULL, NULL, '2025-12-26 14:50:24'),
(53, 10, 'MANUAL_BALANCE_SET', 'global_balance', NULL, 'Solde modifié de 100000000.00 FCFA à 20000.00 FCFA. Raison: X', NULL, NULL, '2025-12-26 14:50:37'),
(54, 10, 'MANUAL_BALANCE_SET', 'global_balance', NULL, 'Solde modifié de 0.00 FCFA à 10000000.00 FCFA. Raison: DSDS', NULL, NULL, '2025-12-26 14:53:01'),
(55, 10, 'LOGIN', 'users', 10, 'Connexion réussie en tant que admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 18:39:33'),
(56, 10, 'MANUAL_BALANCE_SET', 'global_balance', NULL, 'Solde modifié de 10000000.00 FCFA à 20000.00 FCFA. Raison: S', NULL, NULL, '2025-12-26 18:40:13'),
(57, 10, 'LOGIN', 'users', 10, 'Connexion réussie en tant que admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 20:48:02'),
(58, 10, 'LOGIN', 'users', 10, 'Connexion réussie en tant que admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 02:02:14'),
(59, 10, 'ADD_TO_BALANCE', 'global_balance', NULL, 'Ajout de 30000.00 FCFA. Ancien: 20000.00 → Nouveau: 50000.00', NULL, NULL, '2025-12-27 02:03:02'),
(60, 10, 'ADD_TO_BALANCE', 'global_balance', NULL, 'Ajout de 2000.00 FCFA. Ancien: 50000.00 → Nouveau: 52000.00', NULL, NULL, '2025-12-27 02:04:54'),
(61, 10, 'LOGOUT', 'users', 10, 'Déconnexion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 02:07:33'),
(62, 9, 'LOGIN', 'users', 9, 'Connexion réussie en tant que user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 02:07:52'),
(63, 9, 'LOGIN', 'users', 9, 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 03:22:54'),
(64, 10, 'LOGIN', 'users', 10, 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 04:20:20'),
(65, 10, 'BALANCE_ADDED', 'global_balance', 1, 'Ajout de 20 000 FCFA au solde. Ancien: 52 000 FCFA, Nouveau: 72 000 FCFA', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 04:21:04'),
(66, 10, 'LOGOUT', 'users', 10, 'Déconnexion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 04:21:34'),
(67, 9, 'LOGIN', 'users', 9, 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 04:21:51'),
(68, 9, 'LOGOUT', 'users', 9, 'Déconnexion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 04:24:35'),
(69, 10, 'LOGIN', 'users', 10, 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 04:25:05'),
(70, 10, 'LOGOUT', 'users', 10, 'Déconnexion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 04:36:28'),
(71, 10, 'LOGIN', 'users', 10, 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 04:40:27'),
(72, 10, 'LOGOUT', 'users', 10, 'Déconnexion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 04:45:19'),
(73, 10, 'PASSWORD_RESET_REQUEST', 'users', 10, 'Demande de réinitialisation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 04:46:40'),
(74, 9, 'LOGIN', 'users', 9, 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 04:47:09'),
(75, 9, 'LOGOUT', 'users', 9, 'Déconnexion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 04:48:24');

-- --------------------------------------------------------

--
-- Structure de la table `balance_history`
--

CREATE TABLE `balance_history` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `previous_balance` decimal(15,2) NOT NULL,
  `new_balance` decimal(15,2) NOT NULL,
  `change_amount` decimal(15,2) NOT NULL,
  `change_type` enum('manual_set','transaction_validation','transaction') NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `balance_history`
--

INSERT INTO `balance_history` (`id`, `transaction_id`, `previous_balance`, `new_balance`, `change_amount`, `change_type`, `admin_id`, `notes`, `created_at`) VALUES
(3, 3, -300000.00, -250000.00, 50000.00, 'manual_set', NULL, NULL, '2025-12-25 07:16:28'),
(4, 6, -250000.00, -230000.00, 20000.00, 'manual_set', NULL, NULL, '2025-12-26 13:49:15'),
(5, 7, -230000.00, -260000.00, -30000.00, 'manual_set', NULL, NULL, '2025-12-26 13:51:57'),
(6, NULL, 100000000.00, 100000000.00, 0.00, 'manual_set', 10, 's', '2025-12-26 14:50:24'),
(7, NULL, 100000000.00, 20000.00, -99980000.00, 'manual_set', 10, 'X', '2025-12-26 14:50:37'),
(8, 8, 20000.00, 0.00, -20000.00, 'manual_set', NULL, NULL, '2025-12-26 14:51:47'),
(9, NULL, 0.00, 10000000.00, 10000000.00, 'manual_set', 10, 'DSDS', '2025-12-26 14:53:01'),
(10, NULL, 10000000.00, 20000.00, -9980000.00, 'manual_set', 10, 'S', '2025-12-26 18:40:13'),
(11, NULL, 20000.00, 50000.00, 30000.00, '', 10, 's', '2025-12-27 02:03:02'),
(12, NULL, 50000.00, 52000.00, 2000.00, '', 10, 's', '2025-12-27 02:04:54'),
(13, NULL, 52000.00, 72000.00, 20000.00, '', 10, 'S', '2025-12-27 04:21:04'),
(14, 9, 72000.00, 0.00, -72000.00, 'transaction_validation', 10, 'Validation transaction #9', '2025-12-27 04:25:46');

-- --------------------------------------------------------

--
-- Structure de la table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `code` varchar(6) NOT NULL,
  `type` enum('registration','password_reset') NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `exports`
--

CREATE TABLE `exports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('pdf','csv','excel') NOT NULL,
  `period` enum('weekly','monthly','custom') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `global_balance`
--

CREATE TABLE `global_balance` (
  `id` int(11) NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `global_balance`
--

INSERT INTO `global_balance` (`id`, `balance`, `last_updated`, `updated_by`) VALUES
(1, 0.00, '2025-12-27 04:25:46', 10),
(2, 0.00, '2025-12-26 20:17:40', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','urgent') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `transaction_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(14, 9, NULL, 'Bienvenue sur Gestion Financière !', 'Votre compte utilisateur a été créé avec succès. Vous pouvez maintenant vous connecter.', 'success', 1, '2025-12-25 07:12:49'),
(22, 9, 4, 'Transaction refusée', 'Votre transaction de 1 400 000 FCFA a été refusée. Motif: DSDLKSDSD', 'warning', 1, '2025-12-25 07:16:15'),
(23, 9, 3, 'Transaction validée', 'Votre transaction de 50 000 FCFA a été validée.', 'success', 1, '2025-12-25 07:16:28'),
(27, 9, 5, 'Transaction refusée', 'Votre transaction de 10 000 FCFA a été refusée. Motif: qsqs', 'warning', 1, '2025-12-26 12:12:55'),
(30, 10, 6, '🔴 Nouvelle demande URGENTE', 'Une nouvelle demande de transaction nécessite votre attention.', 'urgent', 1, '2025-12-26 13:47:40'),
(31, 9, 6, 'Transaction validée', 'Votre transaction de 20 000 FCFA a été validée.', 'success', 1, '2025-12-26 13:49:15'),
(34, 10, 7, 'Nouvelle demande de transaction', 'Une nouvelle demande de transaction nécessite votre attention.', 'info', 1, '2025-12-26 13:51:20'),
(35, 10, 7, 'Transaction validée', 'Votre transaction de 30 000 FCFA a été validée.', 'success', 1, '2025-12-26 13:51:57'),
(38, 10, 8, '🔴 Nouvelle demande URGENTE', 'Une nouvelle demande de transaction nécessite votre attention.', 'urgent', 1, '2025-12-26 14:51:27'),
(39, 10, 8, 'Transaction validée', 'Votre transaction de 20 000 FCFA a été validée.', 'success', 1, '2025-12-26 14:51:47'),
(46, 10, 9, '🔴 Nouvelle demande URGENTE', 'Nouvelle demande de jacque : 72 000 FCFA', 'urgent', 0, '2025-12-27 04:24:07'),
(47, 9, 9, 'Transaction validée', 'Votre transaction de 72 000 FCFA a été validée.', 'success', 1, '2025-12-27 04:25:46');

-- --------------------------------------------------------

--
-- Structure de la table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'admin', 'Administrateur avec tous les droits', '2025-12-23 05:10:20'),
(2, 'user', 'Utilisateur standard', '2025-12-23 05:10:20');

-- --------------------------------------------------------

--
-- Structure de la table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('entree','sortie') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text NOT NULL,
  `required_date` date NOT NULL,
  `urgency` enum('normal','urgent') DEFAULT 'normal',
  `status` enum('en_attente','validee','refusee') DEFAULT 'en_attente',
  `validated_by` int(11) DEFAULT NULL,
  `validated_at` timestamp NULL DEFAULT NULL,
  `admin_comment` text DEFAULT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `is_urgent_notified` tinyint(1) DEFAULT 0,
  `last_urgent_notification` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `type`, `amount`, `description`, `required_date`, `urgency`, `status`, `validated_by`, `validated_at`, `admin_comment`, `receipt_number`, `is_urgent_notified`, `last_urgent_notification`, `created_at`, `updated_at`) VALUES
(3, 9, 'entree', 50000.00, 'DSKLDSLDS', '2025-12-25', 'urgent', 'validee', 10, '2025-12-25 07:16:28', 'DSLDKSDLS', 'REC-20251225-C9F61A', 0, NULL, '2025-12-25 07:14:53', '2025-12-25 07:16:28'),
(4, 9, 'sortie', 1400000.00, 'SLD?SLDSDS', '2025-12-25', 'normal', 'refusee', 10, '2025-12-25 07:16:15', 'DSDLKSDSD', NULL, 0, NULL, '2025-12-25 07:15:23', '2025-12-25 07:16:15'),
(5, 9, 'sortie', 10000.00, 'SDND.S?NDS', '2025-12-25', 'urgent', 'refusee', 10, '2025-12-26 12:12:55', 'qsqs', NULL, 0, NULL, '2025-12-25 07:41:24', '2025-12-26 12:12:55'),
(6, 9, 'entree', 20000.00, 'sldmlsds', '2025-12-26', 'urgent', 'validee', 10, '2025-12-26 13:49:15', 'SDSD', 'REC-20251226-BC0CF9', 0, NULL, '2025-12-26 13:47:40', '2025-12-26 13:49:15'),
(7, 10, 'sortie', 30000.00, 'SDKDLSDLSDSD', '2025-12-26', 'normal', 'validee', 10, '2025-12-26 13:51:57', '', 'REC-20251226-D19C05', 0, NULL, '2025-12-26 13:51:20', '2025-12-26 13:51:57'),
(8, 10, 'sortie', 20000.00, 'S', '2025-12-26', 'urgent', 'validee', 10, '2025-12-26 14:51:47', '', 'REC-20251226-329385', 0, NULL, '2025-12-26 14:51:27', '2025-12-26 14:51:47'),
(9, 9, 'sortie', 72000.00, 'SDJSKDDKSDS', '2025-12-27', 'urgent', 'validee', 10, '2025-12-27 04:25:46', '', 'REC-20251227-ABFA22', 0, NULL, '2025-12-27 04:24:07', '2025-12-27 04:25:46');

--
-- Déclencheurs `transactions`
--
DELIMITER $$
CREATE TRIGGER `update_balance_after_validation` AFTER UPDATE ON `transactions` FOR EACH ROW BEGIN
    -- Déclarations (OBLIGATOIREMENT EN PREMIER)
    DECLARE current_balance DECIMAL(15,2);
    DECLARE new_balance DECIMAL(15,2);
    DECLARE change_amount DECIMAL(15,2);

    -- Seulement lors du passage à "validee"
    IF NEW.status = 'validee' AND OLD.status <> 'validee' THEN

        -- Récupérer le solde actuel
        SELECT balance
        INTO current_balance
        FROM global_balance
        WHERE id = 1;

        -- Calcul selon le type de transaction
        IF NEW.type = 'entree' THEN
            SET change_amount = NEW.amount;
            SET new_balance = current_balance + NEW.amount;
        ELSE
            SET change_amount = -NEW.amount;
            SET new_balance = current_balance - NEW.amount;
        END IF;

        -- Mise à jour du solde global
        UPDATE global_balance
        SET balance = new_balance,
            updated_by = NEW.validated_by,
            last_updated = NOW()
        WHERE id = 1;

        -- Historique
        INSERT INTO balance_history (
            transaction_id,
            previous_balance,
            new_balance,
            change_amount,
            change_type,
            admin_id,
            notes,
            created_at
        )
        VALUES (
            NEW.id,
            current_balance,
            new_balance,
            change_amount,
            'transaction_validation',
            NEW.validated_by,
            CONCAT('Validation transaction #', NEW.id),
            NOW()
        );

    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL DEFAULT 2,
  `is_active` tinyint(1) DEFAULT 1,
  `email_verified` tinyint(1) DEFAULT 0,
  `phone` varchar(20) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role_id`, `is_active`, `email_verified`, `phone`, `whatsapp`, `last_login`, `reset_token`, `reset_token_expires`, `created_at`, `updated_at`) VALUES
(9, 'jaque', 'jaque@gmail.com', '$2y$10$fC2W0b1PJXvcN5yom7MTLerDvdUM10JsuhB6otvkpE341X.ogayoW', 'jacque', 2, 1, 1, '', '', '2025-12-27 04:47:09', NULL, NULL, '2025-12-25 07:12:49', '2025-12-27 04:47:09'),
(10, 'paul', 'paul@gmail.com', '$2y$10$Kjz.e5lrTgX.uwxWZqvA6uAn1bOzqkWtwG8/eCfZVR9vDuxF0UdiO', 'paul', 1, 1, 1, '', '', '2025-12-27 04:40:27', '7243c3073937b32cead1fc32880bf9d0b18f918082fdc27a54329ee983b004a4', '2025-12-27 05:46:40', '2025-12-25 07:13:54', '2025-12-27 04:46:40');

-- --------------------------------------------------------

--
-- Structure de la table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `theme` varchar(20) DEFAULT 'light',
  `language` varchar(10) DEFAULT 'fr',
  `notifications_enabled` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_daily_stats`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_daily_stats` (
`date` date
,`type` enum('entree','sortie')
,`count` bigint(21)
,`total_amount` decimal(37,2)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_user_transactions`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_user_transactions` (
`user_id` int(11)
,`full_name` varchar(255)
,`email` varchar(255)
,`total_transactions` bigint(21)
,`total_entrees` decimal(37,2)
,`total_sorties` decimal(37,2)
,`pending_count` decimal(22,0)
,`validated_count` decimal(22,0)
,`rejected_count` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Structure de la vue `v_daily_stats`
--
DROP TABLE IF EXISTS `v_daily_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_daily_stats`  AS SELECT cast(`transactions`.`created_at` as date) AS `date`, `transactions`.`type` AS `type`, count(0) AS `count`, sum(`transactions`.`amount`) AS `total_amount` FROM `transactions` WHERE `transactions`.`status` = 'validee' GROUP BY cast(`transactions`.`created_at` as date), `transactions`.`type` ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_user_transactions`
--
DROP TABLE IF EXISTS `v_user_transactions`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_user_transactions`  AS SELECT `u`.`id` AS `user_id`, `u`.`full_name` AS `full_name`, `u`.`email` AS `email`, count(`t`.`id`) AS `total_transactions`, sum(case when `t`.`type` = 'entree' then `t`.`amount` else 0 end) AS `total_entrees`, sum(case when `t`.`type` = 'sortie' then `t`.`amount` else 0 end) AS `total_sorties`, sum(case when `t`.`status` = 'en_attente' then 1 else 0 end) AS `pending_count`, sum(case when `t`.`status` = 'validee' then 1 else 0 end) AS `validated_count`, sum(case when `t`.`status` = 'refusee' then 1 else 0 end) AS `rejected_count` FROM (`users` `u` left join `transactions` `t` on(`u`.`id` = `t`.`user_id`)) GROUP BY `u`.`id` ;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Index pour la table `balance_history`
--
ALTER TABLE `balance_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Index pour la table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_code` (`email`,`code`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Index pour la table `exports`
--
ALTER TABLE `exports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Index pour la table `global_balance`
--
ALTER TABLE `global_balance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Index pour la table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Index pour la table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD KEY `validated_by` (`validated_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_urgency` (`urgency`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_required_date` (`required_date`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_reset_token` (`reset_token`);

--
-- Index pour la table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Index pour la table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `idx_token` (`session_token`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT pour la table `balance_history`
--
ALTER TABLE `balance_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `exports`
--
ALTER TABLE `exports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `global_balance`
--
ALTER TABLE `global_balance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT pour la table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `balance_history`
--
ALTER TABLE `balance_history`
  ADD CONSTRAINT `balance_history_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `exports`
--
ALTER TABLE `exports`
  ADD CONSTRAINT `exports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `global_balance`
--
ALTER TABLE `global_balance`
  ADD CONSTRAINT `global_balance_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`validated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

--
-- Contraintes pour la table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
