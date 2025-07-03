-- CakkySino - Structure de base de données MySQL
-- Créé pour un système de casino en ligne complet

CREATE DATABASE IF NOT EXISTS rsneay_cakkysin_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rsneay_cakkysin_db;

-- Table des utilisateurs
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    coins BIGINT DEFAULT 1000,
    is_admin BOOLEAN DEFAULT FALSE,
    is_online BOOLEAN DEFAULT FALSE,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_is_online (is_online),
    INDEX idx_last_activity (last_activity),
    INDEX idx_last_login (last_login)
);

-- Table de la banque du casino
CREATE TABLE casino_bank (
    id INT AUTO_INCREMENT PRIMARY KEY,
    balance BIGINT NOT NULL DEFAULT 1000000,
    daily_bets BIGINT DEFAULT 0,
    daily_winnings BIGINT DEFAULT 0,
    last_reset_date DATE DEFAULT (CURDATE()),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_last_reset (last_reset_date)
);

-- Insérer le solde initial de la banque
INSERT INTO casino_bank (balance, daily_bets, daily_winnings, last_reset_date) 
VALUES (1000000, 0, 0, CURDATE());

-- Table de l'historique des coins des utilisateurs
CREATE TABLE coin_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount BIGINT NOT NULL,
    transaction_type ENUM('win', 'lose', 'bonus', 'admin_adjustment', 'passive_earning', 'bet_placed', 'bet_won', 'bet_refund') NOT NULL,
    description TEXT,
    game_type ENUM('roulette', 'blackjack', 'passive', 'admin', 'system') DEFAULT NULL,
    game_id INT DEFAULT NULL,
    balance_before BIGINT NOT NULL,
    balance_after BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_game_type (game_type),
    INDEX idx_created_at (created_at),
    INDEX idx_user_created (user_id, created_at)
);

-- Table de l'historique de la banque du casino
CREATE TABLE bank_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    amount BIGINT NOT NULL,
    transaction_type ENUM('game_payout', 'game_income', 'admin_adjustment') NOT NULL,
    description TEXT,
    game_type ENUM('roulette', 'blackjack', 'admin') DEFAULT NULL,
    game_id INT DEFAULT NULL,
    balance_before BIGINT NOT NULL,
    balance_after BIGINT NOT NULL,
    admin_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_game_type (game_type),
    INDEX idx_created_at (created_at),
    INDEX idx_admin_id (admin_id)
);

-- Table des sessions de gains passifs
CREATE TABLE passive_earnings_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_earned BIGINT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    earnings_rate DECIMAL(10,2) DEFAULT 1.00,
    activity_checks INT DEFAULT 0,
    inactive_periods INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_active (is_active),
    INDEX idx_start_time (start_time),
    INDEX idx_last_activity (last_activity)
);

-- Table des gains passifs individuels
CREATE TABLE passive_earnings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id INT NOT NULL,
    amount BIGINT NOT NULL,
    earnings_rate DECIMAL(10,2) NOT NULL,
    activity_bonus DECIMAL(10,2) DEFAULT 1.00,
    time_period_minutes INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES passive_earnings_sessions(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_created_at (created_at),
    INDEX idx_user_created (user_id, created_at)
);

-- Table des parties de roulette
CREATE TABLE roulette_games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_status ENUM('waiting', 'betting', 'spinning', 'finished') DEFAULT 'waiting',
    winning_number INT DEFAULT NULL,
    winning_color ENUM('red', 'black', 'green') DEFAULT NULL,
    total_bets BIGINT DEFAULT 0,
    total_winnings BIGINT DEFAULT 0,
    players_count INT DEFAULT 0,
    auto_spin_time TIMESTAMP NULL,
    spin_time TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    finished_at TIMESTAMP NULL,
    INDEX idx_game_status (game_status),
    INDEX idx_created_at (created_at),
    INDEX idx_auto_spin_time (auto_spin_time),
    INDEX idx_winning_number (winning_number)
);

-- Table des mises de roulette
CREATE TABLE roulette_bets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    user_id INT NOT NULL,
    bet_type ENUM('number', 'red', 'black', 'even', 'odd', 'low', 'high', 'dozen1', 'dozen2', 'dozen3', 'column1', 'column2', 'column3') NOT NULL,
    bet_value VARCHAR(10) NOT NULL,
    amount BIGINT NOT NULL,
    payout_ratio DECIMAL(10,2) NOT NULL,
    is_winner BOOLEAN DEFAULT FALSE,
    winnings BIGINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES roulette_games(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_game_id (game_id),
    INDEX idx_user_id (user_id),
    INDEX idx_bet_type (bet_type),
    INDEX idx_is_winner (is_winner),
    INDEX idx_created_at (created_at),
    INDEX idx_user_game (user_id, game_id)
);

-- Table des parties de blackjack
CREATE TABLE blackjack_games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_status ENUM('waiting', 'playing', 'finished') DEFAULT 'waiting',
    dealer_id INT DEFAULT NULL,
    dealer_cards JSON DEFAULT NULL,
    dealer_value INT DEFAULT 0,
    dealer_bust BOOLEAN DEFAULT FALSE,
    players_count INT DEFAULT 0,
    total_bets BIGINT DEFAULT 0,
    total_winnings BIGINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    finished_at TIMESTAMP NULL,
    FOREIGN KEY (dealer_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_game_status (game_status),
    INDEX idx_dealer_id (dealer_id),
    INDEX idx_created_at (created_at),
    INDEX idx_started_at (started_at)
);

-- Table des mains de blackjack
CREATE TABLE blackjack_hands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    user_id INT NOT NULL,
    bet_amount BIGINT NOT NULL,
    cards JSON DEFAULT NULL,
    hand_value INT DEFAULT 0,
    hand_status ENUM('playing', 'stand', 'bust', 'blackjack', 'finished') DEFAULT 'playing',
    doubled BOOLEAN DEFAULT FALSE,
    is_winner BOOLEAN DEFAULT FALSE,
    result ENUM('win', 'lose', 'push') DEFAULT NULL,
    winnings BIGINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    finished_at TIMESTAMP NULL,
    FOREIGN KEY (game_id) REFERENCES blackjack_games(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_game_id (game_id),
    INDEX idx_user_id (user_id),
    INDEX idx_hand_status (hand_status),
    INDEX idx_result (result),
    INDEX idx_created_at (created_at),
    INDEX idx_user_game (user_id, game_id)
);

-- Table des logs d'administration
CREATE TABLE admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT DEFAULT NULL,
    action_type ENUM('bank_adjustment', 'user_management', 'game_control', 'system_action', 'login', 'logout') NOT NULL,
    description TEXT NOT NULL,
    target_user_id INT DEFAULT NULL,
    target_game_id INT DEFAULT NULL,
    game_type ENUM('roulette', 'blackjack') DEFAULT NULL,
    amount BIGINT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_admin_id (admin_id),
    INDEX idx_action_type (action_type),
    INDEX idx_target_user_id (target_user_id),
    INDEX idx_game_type (game_type),
    INDEX idx_created_at (created_at)
);

-- Table des sessions utilisateur (pour le tracking)
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_session_token (session_token),
    INDEX idx_is_active (is_active),
    INDEX idx_last_activity (last_activity),
    INDEX idx_expires_at (expires_at)
);

-- Table des statistiques quotidiennes
CREATE TABLE daily_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stat_date DATE NOT NULL UNIQUE,
    total_users INT DEFAULT 0,
    active_users INT DEFAULT 0,
    new_users INT DEFAULT 0,
    total_bets BIGINT DEFAULT 0,
    total_winnings BIGINT DEFAULT 0,
    casino_profit BIGINT DEFAULT 0,
    roulette_games INT DEFAULT 0,
    blackjack_games INT DEFAULT 0,
    passive_earnings BIGINT DEFAULT 0,
    coins_in_circulation BIGINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_stat_date (stat_date),
    INDEX idx_created_at (created_at)
);

-- Créer un utilisateur administrateur par défaut
INSERT INTO users (username, email, password_hash, coins, is_admin) 
VALUES ('admin', 'admin@cakkysino.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 100000, TRUE);
-- Mot de passe par défaut: 'password'

-- Créer quelques utilisateurs de test
INSERT INTO users (username, email, password_hash, coins, is_admin) VALUES
('player1', 'player1@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5000, FALSE),
('player2', 'player2@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3000, FALSE),
('croupier', 'croupier@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 10000, TRUE);

-- Procédure stockée pour nettoyer les anciennes sessions
DELIMITER //
CREATE PROCEDURE CleanExpiredSessions()
BEGIN
    DELETE FROM user_sessions WHERE expires_at < NOW();
END //
DELIMITER ;

-- Procédure stockée pour mettre à jour les statistiques quotidiennes
DELIMITER //
CREATE PROCEDURE UpdateDailyStats()
BEGIN
    DECLARE today DATE DEFAULT CURDATE();
    
    INSERT INTO daily_stats (stat_date, total_users, active_users, new_users, 
                           total_bets, total_winnings, casino_profit,
                           roulette_games, blackjack_games, passive_earnings,
                           coins_in_circulation)
    SELECT 
        today,
        (SELECT COUNT(*) FROM users),
        (SELECT COUNT(*) FROM users WHERE DATE(last_activity) = today),
        (SELECT COUNT(*) FROM users WHERE DATE(created_at) = today),
        COALESCE((SELECT SUM(amount) FROM roulette_bets rb 
                  JOIN roulette_games rg ON rb.game_id = rg.id 
                  WHERE DATE(rg.created_at) = today), 0) +
        COALESCE((SELECT SUM(bet_amount) FROM blackjack_hands bh 
                  JOIN blackjack_games bg ON bh.game_id = bg.id 
                  WHERE DATE(bg.created_at) = today), 0),
        COALESCE((SELECT SUM(winnings) FROM roulette_bets rb 
                  JOIN roulette_games rg ON rb.game_id = rg.id 
                  WHERE DATE(rg.created_at) = today), 0) +
        COALESCE((SELECT SUM(winnings) FROM blackjack_hands bh 
                  JOIN blackjack_games bg ON bh.game_id = bg.id 
                  WHERE DATE(bg.created_at) = today), 0),
        (SELECT balance FROM casino_bank LIMIT 1) - 1000000,
        (SELECT COUNT(*) FROM roulette_games WHERE DATE(created_at) = today),
        (SELECT COUNT(*) FROM blackjack_games WHERE DATE(created_at) = today),
        COALESCE((SELECT SUM(amount) FROM passive_earnings WHERE DATE(created_at) = today), 0),
        (SELECT SUM(coins) FROM users)
    ON DUPLICATE KEY UPDATE
        total_users = VALUES(total_users),
        active_users = VALUES(active_users),
        total_bets = VALUES(total_bets),
        total_winnings = VALUES(total_winnings),
        casino_profit = VALUES(casino_profit),
        roulette_games = VALUES(roulette_games),
        blackjack_games = VALUES(blackjack_games),
        passive_earnings = VALUES(passive_earnings),
        coins_in_circulation = VALUES(coins_in_circulation),
        updated_at = CURRENT_TIMESTAMP;
END //
DELIMITER ;

-- Procédure stockée pour réinitialiser les statistiques quotidiennes de la banque
DELIMITER //
CREATE PROCEDURE ResetDailyBankStats()
BEGIN
    UPDATE casino_bank 
    SET daily_bets = 0, daily_winnings = 0, last_reset_date = CURDATE()
    WHERE last_reset_date < CURDATE();
END //
DELIMITER ;

-- Fonction pour calculer le taux de gains passifs basé sur l'activité
DELIMITER //
CREATE FUNCTION CalculatePassiveRate(activity_checks INT, inactive_periods INT) 
RETURNS DECIMAL(10,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE base_rate DECIMAL(10,2) DEFAULT 1.00;
    DECLARE activity_ratio DECIMAL(10,2);
    
    IF activity_checks = 0 THEN
        RETURN 0.50; -- Taux réduit si aucune activité
    END IF;
    
    SET activity_ratio = (activity_checks - inactive_periods) / activity_checks;
    
    IF activity_ratio >= 0.9 THEN
        RETURN base_rate * 1.5; -- Bonus pour haute activité
    ELSEIF activity_ratio >= 0.7 THEN
        RETURN base_rate * 1.2;
    ELSEIF activity_ratio >= 0.5 THEN
        RETURN base_rate;
    ELSE
        RETURN base_rate * 0.7; -- Pénalité pour faible activité
    END IF;
END //
DELIMITER ;

-- Trigger pour mettre à jour automatiquement last_activity des utilisateurs
DELIMITER //
CREATE TRIGGER update_user_activity
AFTER INSERT ON coin_history
FOR EACH ROW
BEGIN
    UPDATE users SET last_activity = CURRENT_TIMESTAMP WHERE id = NEW.user_id;
END //
DELIMITER ;

-- Trigger pour maintenir les statistiques de la banque
DELIMITER //
CREATE TRIGGER update_bank_daily_stats
AFTER INSERT ON bank_history
FOR EACH ROW
BEGIN
    IF NEW.transaction_type = 'game_income' THEN
        UPDATE casino_bank SET daily_bets = daily_bets + NEW.amount;
    ELSEIF NEW.transaction_type = 'game_payout' THEN
        UPDATE casino_bank SET daily_winnings = daily_winnings + ABS(NEW.amount);
    END IF;
END //
DELIMITER ;

-- Vues pour faciliter les requêtes courantes

-- Vue des statistiques utilisateur
CREATE VIEW user_stats AS
SELECT 
    u.id,
    u.username,
    u.coins,
    u.is_admin,
    u.is_online,
    u.last_activity,
    u.created_at,
    COALESCE(pe_stats.total_passive_earned, 0) as total_passive_earned,
    COALESCE(r_stats.roulette_games, 0) as roulette_games_played,
    COALESCE(r_stats.roulette_profit, 0) as roulette_net_profit,
    COALESCE(b_stats.blackjack_games, 0) as blackjack_games_played,
    COALESCE(b_stats.blackjack_profit, 0) as blackjack_net_profit
FROM users u
LEFT JOIN (
    SELECT user_id, SUM(amount) as total_passive_earned
    FROM passive_earnings
    GROUP BY user_id
) pe_stats ON u.id = pe_stats.user_id
LEFT JOIN (
    SELECT 
        user_id, 
        COUNT(DISTINCT game_id) as roulette_games,
        SUM(winnings) - SUM(amount) as roulette_profit
    FROM roulette_bets
    GROUP BY user_id
) r_stats ON u.id = r_stats.user_id
LEFT JOIN (
    SELECT 
        user_id, 
        COUNT(DISTINCT game_id) as blackjack_games,
        SUM(winnings) - SUM(bet_amount) as blackjack_profit
    FROM blackjack_hands
    GROUP BY user_id
) b_stats ON u.id = b_stats.user_id;

-- Vue des parties actives
CREATE VIEW active_games AS
SELECT 
    'roulette' as game_type,
    id as game_id,
    game_status,
    players_count,
    total_bets,
    created_at
FROM roulette_games 
WHERE game_status IN ('waiting', 'betting', 'spinning')
UNION ALL
SELECT 
    'blackjack' as game_type,
    id as game_id,
    game_status,
    players_count,
    total_bets,
    created_at
FROM blackjack_games 
WHERE game_status IN ('waiting', 'playing');

-- Index composites pour optimiser les performances
CREATE INDEX idx_coin_history_user_type_date ON coin_history(user_id, transaction_type, created_at);
CREATE INDEX idx_roulette_bets_game_user ON roulette_bets(game_id, user_id, created_at);
CREATE INDEX idx_blackjack_hands_game_user ON blackjack_hands(game_id, user_id, created_at);
CREATE INDEX idx_passive_earnings_user_date ON passive_earnings(user_id, created_at);
CREATE INDEX idx_admin_logs_admin_date ON admin_logs(admin_id, created_at);

-- Événement pour nettoyer automatiquement les anciennes données
CREATE EVENT IF NOT EXISTS cleanup_old_data
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    -- Nettoyer les sessions expirées
    CALL CleanExpiredSessions();
    
    -- Nettoyer les anciens logs (garder 30 jours)
    DELETE FROM admin_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    -- Mettre à jour les statistiques quotidiennes
    CALL UpdateDailyStats();
    
    -- Réinitialiser les stats quotidiennes de la banque si nécessaire
    CALL ResetDailyBankStats();
END;

-- Activer l'événement scheduler
SET GLOBAL event_scheduler = ON;

-- Commentaires sur les tables principales
ALTER TABLE users COMMENT = 'Table des utilisateurs du casino avec informations de base et solde';
ALTER TABLE casino_bank COMMENT = 'Table de gestion du solde et des statistiques de la banque du casino';
ALTER TABLE coin_history COMMENT = 'Historique complet de toutes les transactions de coins';
ALTER TABLE passive_earnings_sessions COMMENT = 'Sessions de gains passifs avec tracking anti-triche';
ALTER TABLE roulette_games COMMENT = 'Parties de roulette avec statuts et résultats';
ALTER TABLE blackjack_games COMMENT = 'Parties de blackjack avec gestion du croupier';
ALTER TABLE admin_logs COMMENT = 'Logs de toutes les actions administratives';

-- Afficher un résumé de la création
SELECT 'Base de données CakkySino créée avec succès!' as message;
SELECT COUNT(*) as total_tables FROM information_schema.tables WHERE table_schema = 'cakkysino';
SELECT COUNT(*) as total_users FROM users;
SELECT balance as casino_balance FROM casino_bank LIMIT 1;