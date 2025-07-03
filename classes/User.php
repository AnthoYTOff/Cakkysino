<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    // Inscription d'un nouvel utilisateur
    public function register($username, $email, $password) {
        try {
            // Vérifier si l'utilisateur existe déjà
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Nom d\'utilisateur ou email déjà utilisé'];
            }
            
            // Hasher le mot de passe
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insérer le nouvel utilisateur
            $stmt = $this->db->prepare(
                "INSERT INTO users (username, email, password_hash, coins) VALUES (?, ?, ?, 1000)"
            );
            $stmt->execute([$username, $email, $password_hash]);
            
            $user_id = $this->db->lastInsertId();
            
            // Enregistrer la transaction initiale
            $this->addCoinTransaction($user_id, 'admin_adjustment', 1000, 1000, 'Coins de bienvenue');
            
            return ['success' => true, 'message' => 'Inscription réussie', 'user_id' => $user_id];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur lors de l\'inscription: ' . $e->getMessage()];
        }
    }
    
    // Connexion d'un utilisateur
    public function login($username, $password) {
        try {
            $stmt = $this->db->prepare("SELECT id, username, email, password_hash, coins, is_admin FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Nom d\'utilisateur ou mot de passe incorrect'];
            }
            
            $user = $stmt->fetch();
            
            if (!password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Nom d\'utilisateur ou mot de passe incorrect'];
            }
            
            // Mettre à jour la dernière connexion et le statut en ligne
            $stmt = $this->db->prepare("UPDATE users SET last_login = NOW(), is_online = TRUE WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Créer une session
            $session_token = $this->createSession($user['id']);
            
            return [
                'success' => true,
                'user' => $user,
                'session_token' => $session_token
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur lors de la connexion: ' . $e->getMessage()];
        }
    }
    
    // Créer une session
    private function createSession($user_id) {
        $session_token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $stmt = $this->db->prepare(
            "INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)"
        );
        $stmt->execute([$user_id, $session_token, $expires_at]);
        
        return $session_token;
    }
    
    // Vérifier une session
    public function verifySession($session_token) {
        $stmt = $this->db->prepare(
            "SELECT us.user_id, u.username, u.email, u.coins, u.is_admin 
             FROM user_sessions us 
             JOIN users u ON us.user_id = u.id 
             WHERE us.session_token = ? AND us.expires_at > NOW() AND us.is_active = TRUE"
        );
        $stmt->execute([$session_token]);
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch();
        }
        
        return false;
    }
    
    // Déconnexion
    public function logout($session_token) {
        // Désactiver la session
        $stmt = $this->db->prepare("UPDATE user_sessions SET is_active = FALSE WHERE session_token = ?");
        $stmt->execute([$session_token]);
        
        // Mettre à jour le statut en ligne
        $stmt = $this->db->prepare(
            "UPDATE users u 
             JOIN user_sessions us ON u.id = us.user_id 
             SET u.is_online = FALSE 
             WHERE us.session_token = ?"
        );
        $stmt->execute([$session_token]);
    }
    
    // Obtenir les informations d'un utilisateur
    public function getUserById($user_id) {
        $stmt = $this->db->prepare("SELECT id, username, email, coins, is_admin, created_at, last_login FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }
    
    // Mettre à jour les coins d'un utilisateur
    public function updateCoins($user_id, $amount, $transaction_type, $description = '') {
        try {
            $this->db->beginTransaction();
            
            // Obtenir le solde actuel
            $stmt = $this->db->prepare("SELECT coins FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $current_balance = $stmt->fetchColumn();
            
            $new_balance = $current_balance + $amount;
            
            // Vérifier que le solde ne devient pas négatif
            if ($new_balance < 0) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Solde insuffisant'];
            }
            
            // Mettre à jour le solde
            $stmt = $this->db->prepare("UPDATE users SET coins = ? WHERE id = ?");
            $stmt->execute([$new_balance, $user_id]);
            
            // Enregistrer la transaction
            $this->addCoinTransaction($user_id, $transaction_type, $amount, $new_balance, $description);
            
            $this->db->commit();
            return ['success' => true, 'new_balance' => $new_balance];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()];
        }
    }
    
    // Ajouter une transaction de coins
    private function addCoinTransaction($user_id, $type, $amount, $balance_after, $description) {
        $balance_before = $balance_after - $amount;
        $stmt = $this->db->prepare(
            "INSERT INTO coin_history (user_id, transaction_type, amount, balance_before, balance_after, description) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$user_id, $type, $amount, $balance_before, $balance_after, $description]);
    }
    
    // Obtenir l'historique des transactions
    public function getTransactionHistory($user_id, $limit = 50) {
        $limit = (int)$limit; // Convertir en entier pour éviter les erreurs SQL
        $limit = max(1, min($limit, 1000)); // Limiter entre 1 et 1000 pour la sécurité
        
        $stmt = $this->db->prepare(
            "SELECT transaction_type, amount, balance_before, balance_after, description, created_at 
             FROM coin_history 
             WHERE user_id = ? 
             ORDER BY created_at DESC 
             LIMIT " . $limit
        );
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
    
    // Obtenir tous les utilisateurs (pour l'admin)
    public function getAllUsers() {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, coins, is_admin, is_online, created_at, last_login 
             FROM users 
             ORDER BY created_at DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>