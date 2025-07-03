<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/User.php';

class PassiveEarnings {
    private $db;
    private $user;
    
    // Configuration des gains
    private $coins_per_interval = 1; // Coins gagnés par intervalle
    private $interval_seconds = 30; // Intervalle en secondes
    private $max_inactive_time = 60; // Temps maximum d'inactivité autorisé (secondes)
    
    public function __construct() {
        $this->db = getDB();
        $this->user = new User();
    }
    
    // Démarrer une session de gains passifs
    public function startEarningSession($user_id) {
        session_start();
        $_SESSION['earning_start_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['total_earned'] = 0;
        
        return [
            'success' => true,
            'message' => 'Session de gains démarrée',
            'coins_per_interval' => $this->coins_per_interval,
            'interval_seconds' => $this->interval_seconds
        ];
    }
    
    // Mettre à jour l'activité de l'utilisateur (anti-AFK)
    public function updateActivity($user_id) {
        session_start();
        
        if (!isset($_SESSION['earning_start_time'])) {
            return ['success' => false, 'message' => 'Session non démarrée'];
        }
        
        $current_time = time();
        $last_activity = $_SESSION['last_activity'] ?? $current_time;
        
        // Vérifier si l'utilisateur n'était pas AFK trop longtemps
        if ($current_time - $last_activity > $this->max_inactive_time) {
            return [
                'success' => false, 
                'message' => 'Session expirée due à l\'inactivité',
                'afk' => true
            ];
        }
        
        $_SESSION['last_activity'] = $current_time;
        
        return [
            'success' => true,
            'message' => 'Activité mise à jour',
            'last_activity' => $current_time
        ];
    }
    
    // Calculer et attribuer les gains
    public function calculateEarnings($user_id) {
        session_start();
        
        if (!isset($_SESSION['earning_start_time']) || !isset($_SESSION['last_activity'])) {
            return ['success' => false, 'message' => 'Session non valide'];
        }
        
        $current_time = time();
        $last_activity = $_SESSION['last_activity'];
        $earning_start = $_SESSION['earning_start_time'];
        
        // Vérifier l'activité récente
        if ($current_time - $last_activity > $this->max_inactive_time) {
            return [
                'success' => false, 
                'message' => 'Session expirée due à l\'inactivité',
                'afk' => true
            ];
        }
        
        // Calculer le temps écoulé depuis le début
        $time_elapsed = $current_time - $earning_start;
        
        // Calculer le nombre d'intervalles complets
        $intervals_completed = floor($time_elapsed / $this->interval_seconds);
        
        // Calculer les coins à gagner
        $coins_to_earn = $intervals_completed * $this->coins_per_interval;
        
        // Soustraire ce qui a déjà été gagné
        $already_earned = $_SESSION['total_earned'] ?? 0;
        $new_coins = $coins_to_earn - $already_earned;
        
        if ($new_coins > 0) {
            // Attribuer les coins
            $result = $this->user->updateCoins(
                $user_id, 
                $new_coins, 
                'earn_passive', 
                "Gains passifs: {$new_coins} coins pour {$time_elapsed} secondes"
            );
            
            if ($result['success']) {
                // Enregistrer les gains passifs
                $this->recordPassiveEarning($user_id, $new_coins, $time_elapsed);
                
                $_SESSION['total_earned'] = $coins_to_earn;
                
                return [
                    'success' => true,
                    'coins_earned' => $new_coins,
                    'total_earned' => $coins_to_earn,
                    'time_elapsed' => $time_elapsed,
                    'new_balance' => $result['new_balance']
                ];
            } else {
                return $result;
            }
        }
        
        return [
            'success' => true,
            'coins_earned' => 0,
            'total_earned' => $already_earned,
            'time_elapsed' => $time_elapsed,
            'next_earning_in' => $this->interval_seconds - ($time_elapsed % $this->interval_seconds)
        ];
    }
    
    // Enregistrer les gains passifs en base
    private function recordPassiveEarning($user_id, $coins_earned, $time_spent) {
        // Note: La table passive_earnings nécessite session_id, earnings_rate et time_period_minutes
        // Pour l'instant, on utilise des valeurs par défaut
        $stmt = $this->db->prepare(
            "INSERT INTO passive_earnings (user_id, session_id, amount, earnings_rate, time_period_minutes) 
             VALUES (?, 1, ?, 1.00, ?)"
        );
        $time_minutes = ceil($time_spent / 60); // Convertir secondes en minutes
        $stmt->execute([$user_id, $coins_earned, $time_minutes]);
    }
    
    // Arrêter la session de gains
    public function stopEarningSession($user_id) {
        session_start();
        
        if (isset($_SESSION['earning_start_time'])) {
            $total_time = time() - $_SESSION['earning_start_time'];
            $total_earned = $_SESSION['total_earned'] ?? 0;
            
            // Nettoyer la session
            unset($_SESSION['earning_start_time']);
            unset($_SESSION['last_activity']);
            unset($_SESSION['total_earned']);
            
            return [
                'success' => true,
                'message' => 'Session terminée',
                'total_time' => $total_time,
                'total_earned' => $total_earned
            ];
        }
        
        return ['success' => false, 'message' => 'Aucune session active'];
    }
    
    // Obtenir les statistiques de gains passifs d'un utilisateur
    public function getUserEarningsStats($user_id) {
        // Total des gains passifs
        $stmt = $this->db->prepare(
            "SELECT 
                COUNT(*) as total_sessions,
                SUM(amount) as total_coins_earned,
                SUM(time_period_minutes * 60) as total_time_spent,
                AVG(amount) as avg_coins_per_session,
                MAX(amount) as best_session
             FROM passive_earnings 
             WHERE user_id = ?"
        );
        $stmt->execute([$user_id]);
        $stats = $stmt->fetch();
        
        // Gains aujourd'hui
        $stmt = $this->db->prepare(
            "SELECT 
                COUNT(*) as today_sessions,
                COALESCE(SUM(amount), 0) as today_coins,
                COALESCE(SUM(time_period_minutes * 60), 0) as today_time
             FROM passive_earnings 
             WHERE user_id = ? AND DATE(created_at) = CURDATE()"
        );
        $stmt->execute([$user_id]);
        $today_stats = $stmt->fetch();
        
        return [
            'total' => $stats,
            'today' => $today_stats
        ];
    }
    
    // Obtenir l'historique des gains passifs
    public function getEarningsHistory($user_id, $limit = 20) {
        $limit = (int)$limit; // Convertir en entier pour éviter les erreurs SQL
        $limit = max(1, min($limit, 100)); // Limiter entre 1 et 100 pour la sécurité
        
        $stmt = $this->db->prepare(
            "SELECT amount as coins_earned, time_period_minutes * 60 as time_spent_seconds, created_at as earned_at 
             FROM passive_earnings 
             WHERE user_id = ? 
             ORDER BY created_at DESC 
             LIMIT " . $limit
        );
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
    
    // Obtenir le statut actuel de la session
    public function getSessionStatus() {
        session_start();
        
        if (!isset($_SESSION['earning_start_time'])) {
            return ['active' => false];
        }
        
        $current_time = time();
        $start_time = $_SESSION['earning_start_time'];
        $last_activity = $_SESSION['last_activity'] ?? $current_time;
        $total_earned = $_SESSION['total_earned'] ?? 0;
        
        $time_elapsed = $current_time - $start_time;
        $time_since_activity = $current_time - $last_activity;
        
        return [
            'active' => true,
            'start_time' => $start_time,
            'time_elapsed' => $time_elapsed,
            'last_activity' => $last_activity,
            'time_since_activity' => $time_since_activity,
            'total_earned' => $total_earned,
            'is_afk' => $time_since_activity > $this->max_inactive_time,
            'next_earning_in' => $this->interval_seconds - ($time_elapsed % $this->interval_seconds)
        ];
    }
}
?>