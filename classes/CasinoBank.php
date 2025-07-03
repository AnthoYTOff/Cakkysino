<?php
require_once __DIR__ . '/../config/database.php';

class CasinoBank {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    // Obtenir le solde actuel de la banque
    public function getBalance() {
        $stmt = $this->db->prepare("SELECT total_coins FROM casino_bank ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result ? $result['total_coins'] : 0;
    }
    
    // Mettre à jour le solde de la banque
    public function updateBalance($amount, $description = '') {
        try {
            $this->db->beginTransaction();
            
            $current_balance = $this->getBalance();
            $new_balance = $current_balance + $amount;
            
            // Vérifier que la banque ne devient pas négative
            if ($new_balance < 0) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Fonds insuffisants dans la banque du casino'];
            }
            
            // Mettre à jour le solde
            $stmt = $this->db->prepare("UPDATE casino_bank SET total_coins = ?, updated_at = NOW()");
            $stmt->execute([$new_balance]);
            
            $this->db->commit();
            return ['success' => true, 'new_balance' => $new_balance];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour de la banque: ' . $e->getMessage()];
        }
    }
    
    // Vérifier si la banque peut payer un montant
    public function canPay($amount) {
        return $this->getBalance() >= $amount;
    }
    
    // Traiter un paiement (retirer de la banque)
    public function processPayment($amount, $description = '') {
        return $this->updateBalance(-$amount, $description);
    }
    
    // Recevoir un paiement (ajouter à la banque)
    public function receivePayment($amount, $description = '') {
        return $this->updateBalance($amount, $description);
    }
    
    // Obtenir les statistiques financières
    public function getFinancialStats() {
        $stats = [];
        
        // Solde actuel
        $stats['current_balance'] = $this->getBalance();
        
        // Total des mises aujourd'hui
        $stmt = $this->db->prepare(
            "SELECT 
                COALESCE(SUM(rb.bet_amount), 0) as roulette_bets,
                COALESCE(SUM(bh.bet_amount), 0) as blackjack_bets
             FROM 
                (SELECT bet_amount FROM roulette_bets WHERE DATE(created_at) = CURDATE()) rb,
                (SELECT bet_amount FROM blackjack_hands WHERE DATE(created_at) = CURDATE()) bh"
        );
        $stmt->execute();
        $daily_bets = $stmt->fetch();
        $stats['daily_total_bets'] = ($daily_bets['roulette_bets'] ?? 0) + ($daily_bets['blackjack_bets'] ?? 0);
        
        // Total des gains payés aujourd'hui
        $stmt = $this->db->prepare(
            "SELECT 
                COALESCE(SUM(rb.winnings), 0) as roulette_winnings,
                COALESCE(SUM(bh.winnings), 0) as blackjack_winnings
             FROM 
                (SELECT winnings FROM roulette_bets WHERE DATE(created_at) = CURDATE() AND won = TRUE) rb,
                (SELECT winnings FROM blackjack_hands WHERE DATE(created_at) = CURDATE() AND winnings > 0) bh"
        );
        $stmt->execute();
        $daily_winnings = $stmt->fetch();
        $stats['daily_total_winnings'] = ($daily_winnings['roulette_winnings'] ?? 0) + ($daily_winnings['blackjack_winnings'] ?? 0);
        
        // Profit/Perte du jour
        $stats['daily_profit'] = $stats['daily_total_bets'] - $stats['daily_total_winnings'];
        
        // Nombre de joueurs actifs aujourd'hui
        $stmt = $this->db->prepare(
            "SELECT COUNT(DISTINCT user_id) as active_players 
             FROM (
                 SELECT user_id FROM roulette_bets WHERE DATE(created_at) = CURDATE()
                 UNION
                 SELECT user_id FROM blackjack_hands WHERE DATE(created_at) = CURDATE()
                 UNION
                 SELECT user_id FROM passive_earnings WHERE DATE(earned_at) = CURDATE()
             ) as players"
        );
        $stmt->execute();
        $stats['daily_active_players'] = $stmt->fetchColumn();
        
        // Total des coins en circulation
        $stmt = $this->db->prepare("SELECT SUM(coins) as total_user_coins FROM users");
        $stmt->execute();
        $stats['total_user_coins'] = $stmt->fetchColumn();
        
        return $stats;
    }
    
    // Obtenir l'historique des changements de la banque
    public function getBankHistory($limit = 100) {
        $stmt = $this->db->prepare(
            "SELECT total_coins, updated_at 
             FROM casino_bank 
             ORDER BY updated_at DESC 
             LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
?>