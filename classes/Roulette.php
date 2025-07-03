<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/CasinoBank.php';

class Roulette {
    private $db;
    private $user;
    private $bank;
    
    // Configuration de la roulette
    private $max_players = 6;
    private $betting_time = 30; // secondes
    private $spinning_time = 10; // secondes
    
    // Numéros rouges et noirs
    private $red_numbers = [1, 3, 5, 7, 9, 12, 14, 16, 18, 19, 21, 23, 25, 27, 30, 32, 34, 36];
    private $black_numbers = [2, 4, 6, 8, 10, 11, 13, 15, 17, 20, 22, 24, 26, 28, 29, 31, 33, 35];
    
    // Multiplicateurs de gains
    private $payouts = [
        'number' => 35,    // Mise sur un numéro
        'red' => 1,        // Rouge
        'black' => 1,      // Noir
        'even' => 1,       // Pair
        'odd' => 1,        // Impair
        'low' => 1,        // 1-18
        'high' => 1        // 19-36
    ];
    
    public function __construct() {
        $this->db = getDB();
        $this->user = new User();
        $this->bank = new CasinoBank();
    }
    
    // Créer une nouvelle partie
    public function createGame() {
        $stmt = $this->db->prepare(
            "INSERT INTO roulette_games (game_status) VALUES ('waiting')"
        );
        $stmt->execute();
        
        return $this->db->lastInsertId();
    }
    
    // Obtenir la partie active ou en créer une nouvelle
    public function getActiveGame() {
        $stmt = $this->db->prepare(
            "SELECT * FROM roulette_games 
             WHERE game_status IN ('waiting', 'betting') 
             ORDER BY created_at DESC 
             LIMIT 1"
        );
        $stmt->execute();
        $game = $stmt->fetch();
        
        if (!$game) {
            $game_id = $this->createGame();
            return $this->getGameById($game_id);
        }
        
        return $game;
    }
    
    // Obtenir une partie par ID
    public function getGameById($game_id) {
        $stmt = $this->db->prepare("SELECT * FROM roulette_games WHERE id = ?");
        $stmt->execute([$game_id]);
        return $stmt->fetch();
    }
    
    // Placer une mise
    public function placeBet($user_id, $bet_type, $bet_value, $bet_amount) {
        try {
            // Vérifier que l'utilisateur a assez de coins
            $user_data = $this->user->getUserById($user_id);
            if (!$user_data || $user_data['coins'] < $bet_amount) {
                return ['success' => false, 'message' => 'Solde insuffisant'];
            }
            
            // Obtenir la partie active
            $game = $this->getActiveGame();
            
            // Vérifier le statut de la partie
            if ($game['game_status'] === 'spinning' || $game['game_status'] === 'finished') {
                return ['success' => false, 'message' => 'Les mises sont fermées pour cette partie'];
            }
            
            // Vérifier le nombre de joueurs
            $current_players = $this->getGamePlayers($game['id']);
            if (count($current_players) >= $this->max_players && !in_array($user_id, array_column($current_players, 'user_id'))) {
                return ['success' => false, 'message' => 'Partie complète (maximum ' . $this->max_players . ' joueurs)'];
            }
            
            // Valider le type de mise
            if (!$this->validateBet($bet_type, $bet_value)) {
                return ['success' => false, 'message' => 'Type de mise invalide'];
            }
            
            $this->db->beginTransaction();
            
            // Déduire les coins de l'utilisateur
            $result = $this->user->updateCoins($user_id, -$bet_amount, 'bet_roulette', "Mise roulette: {$bet_amount} coins sur {$bet_type} {$bet_value}");
            if (!$result['success']) {
                $this->db->rollBack();
                return $result;
            }
            
            // Ajouter les coins à la banque
            $this->bank->receivePayment($bet_amount, "Mise roulette de l'utilisateur {$user_id}");
            
            // Enregistrer la mise
            $payout_multiplier = $this->payouts[$bet_type];
            $stmt = $this->db->prepare(
                "INSERT INTO roulette_bets (game_id, user_id, bet_type, bet_value, bet_amount, payout_multiplier) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$game['id'], $user_id, $bet_type, $bet_value, $bet_amount, $payout_multiplier]);
            
            // Mettre à jour le statut de la partie
            if ($game['game_status'] === 'waiting') {
                $stmt = $this->db->prepare("UPDATE roulette_games SET game_status = 'betting' WHERE id = ?");
                $stmt->execute([$game['id']]);
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Mise placée avec succès',
                'game_id' => $game['id'],
                'new_balance' => $result['new_balance']
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Erreur lors de la mise: ' . $e->getMessage()];
        }
    }
    
    // Valider une mise
    private function validateBet($bet_type, $bet_value) {
        switch ($bet_type) {
            case 'number':
                return is_numeric($bet_value) && $bet_value >= 0 && $bet_value <= 36;
            case 'red':
            case 'black':
            case 'even':
            case 'odd':
            case 'low':
            case 'high':
                return $bet_value === $bet_type;
            default:
                return false;
        }
    }
    
    // Obtenir les joueurs d'une partie
    public function getGamePlayers($game_id) {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT u.id as user_id, u.username 
             FROM roulette_bets rb 
             JOIN users u ON rb.user_id = u.id 
             WHERE rb.game_id = ?"
        );
        $stmt->execute([$game_id]);
        return $stmt->fetchAll();
    }
    
    // Obtenir les mises d'une partie
    public function getGameBets($game_id) {
        $stmt = $this->db->prepare(
            "SELECT rb.*, u.username 
             FROM roulette_bets rb 
             JOIN users u ON rb.user_id = u.id 
             WHERE rb.game_id = ? 
             ORDER BY rb.created_at ASC"
        );
        $stmt->execute([$game_id]);
        return $stmt->fetchAll();
    }
    
    // Lancer la roulette
    public function spinRoulette($game_id) {
        try {
            $game = $this->getGameById($game_id);
            if (!$game || $game['game_status'] !== 'betting') {
                return ['success' => false, 'message' => 'Partie non valide pour le lancement'];
            }
            
            $this->db->beginTransaction();
            
            // Générer le numéro gagnant
            $winning_number = rand(0, 36);
            $winning_color = $this->getNumberColor($winning_number);
            
            // Mettre à jour la partie
            $stmt = $this->db->prepare(
                "UPDATE roulette_games 
                 SET game_status = 'spinning', winning_number = ?, winning_color = ? 
                 WHERE id = ?"
            );
            $stmt->execute([$winning_number, $winning_color, $game_id]);
            
            $this->db->commit();
            
            // Programmer la fin du spin après le délai
            $this->scheduleGameFinish($game_id);
            
            return [
                'success' => true,
                'winning_number' => $winning_number,
                'winning_color' => $winning_color,
                'spinning_time' => $this->spinning_time
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Erreur lors du lancement: ' . $e->getMessage()];
        }
    }
    
    // Terminer une partie et calculer les gains
    public function finishGame($game_id) {
        try {
            $game = $this->getGameById($game_id);
            if (!$game || $game['game_status'] !== 'spinning') {
                return ['success' => false, 'message' => 'Partie non valide pour la finalisation'];
            }
            
            $this->db->beginTransaction();
            
            $winning_number = $game['winning_number'];
            $winning_color = $game['winning_color'];
            
            // Obtenir toutes les mises
            $bets = $this->getGameBets($game_id);
            $total_winnings = 0;
            
            foreach ($bets as $bet) {
                $won = $this->checkWinningBet($bet, $winning_number, $winning_color);
                
                if ($won) {
                    $winnings = $bet['bet_amount'] * ($bet['payout_multiplier'] + 1); // +1 pour récupérer la mise
                    $total_winnings += $winnings;
                    
                    // Mettre à jour la mise
                    $stmt = $this->db->prepare(
                        "UPDATE roulette_bets SET won = TRUE, winnings = ? WHERE id = ?"
                    );
                    $stmt->execute([$winnings, $bet['id']]);
                }
            }
            
            // Vérifier si la banque peut payer
            if ($total_winnings > 0 && !$this->bank->canPay($total_winnings)) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Fonds insuffisants dans la banque du casino'];
            }
            
            // Payer les gagnants
            foreach ($bets as $bet) {
                if ($this->checkWinningBet($bet, $winning_number, $winning_color)) {
                    $winnings = $bet['bet_amount'] * ($bet['payout_multiplier'] + 1);
                    
                    // Créditer l'utilisateur
                    $this->user->updateCoins(
                        $bet['user_id'], 
                        $winnings, 
                        'win_roulette', 
                        "Gain roulette: {$winnings} coins (numéro {$winning_number})"
                    );
                    
                    // Débiter la banque
                    $this->bank->processPayment($winnings, "Paiement gain roulette utilisateur {$bet['user_id']}");
                }
            }
            
            // Marquer la partie comme terminée
            $stmt = $this->db->prepare(
                "UPDATE roulette_games SET game_status = 'finished', finished_at = NOW() WHERE id = ?"
            );
            $stmt->execute([$game_id]);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'winning_number' => $winning_number,
                'winning_color' => $winning_color,
                'total_winnings' => $total_winnings,
                'winners' => $this->getGameWinners($game_id)
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Erreur lors de la finalisation: ' . $e->getMessage()];
        }
    }
    
    // Vérifier si une mise est gagnante
    private function checkWinningBet($bet, $winning_number, $winning_color) {
        switch ($bet['bet_type']) {
            case 'number':
                return (int)$bet['bet_value'] === $winning_number;
            case 'red':
                return $winning_color === 'red';
            case 'black':
                return $winning_color === 'black';
            case 'even':
                return $winning_number > 0 && $winning_number % 2 === 0;
            case 'odd':
                return $winning_number > 0 && $winning_number % 2 === 1;
            case 'low':
                return $winning_number >= 1 && $winning_number <= 18;
            case 'high':
                return $winning_number >= 19 && $winning_number <= 36;
            default:
                return false;
        }
    }
    
    // Obtenir la couleur d'un numéro
    private function getNumberColor($number) {
        if ($number === 0) {
            return 'green';
        } elseif (in_array($number, $this->red_numbers)) {
            return 'red';
        } else {
            return 'black';
        }
    }
    
    // Obtenir les gagnants d'une partie
    public function getGameWinners($game_id) {
        $stmt = $this->db->prepare(
            "SELECT rb.*, u.username 
             FROM roulette_bets rb 
             JOIN users u ON rb.user_id = u.id 
             WHERE rb.game_id = ? AND rb.won = TRUE"
        );
        $stmt->execute([$game_id]);
        return $stmt->fetchAll();
    }
    
    // Programmer la fin automatique d'une partie (simulation)
    private function scheduleGameFinish($game_id) {
        // Dans un vrai système, ceci serait géré par un cron job ou un système de queue
        // Pour la démo, on peut utiliser JavaScript côté client pour déclencher la fin
    }
    
    // Obtenir l'historique des parties
    public function getGameHistory($limit = 20) {
        $stmt = $this->db->prepare(
            "SELECT * FROM roulette_games 
             WHERE game_status = 'finished' 
             ORDER BY finished_at DESC 
             LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    // Obtenir les statistiques de la roulette
    public function getRouletteStats() {
        $stats = [];
        
        // Nombre total de parties
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM roulette_games WHERE game_status = 'finished'");
        $stmt->execute();
        $stats['total_games'] = $stmt->fetchColumn();
        
        // Total des mises
        $stmt = $this->db->prepare("SELECT SUM(bet_amount) FROM roulette_bets");
        $stmt->execute();
        $stats['total_bets'] = $stmt->fetchColumn() ?? 0;
        
        // Total des gains
        $stmt = $this->db->prepare("SELECT SUM(winnings) FROM roulette_bets WHERE won = TRUE");
        $stmt->execute();
        $stats['total_winnings'] = $stmt->fetchColumn() ?? 0;
        
        // Numéros les plus sortis
        $stmt = $this->db->prepare(
            "SELECT winning_number, COUNT(*) as count 
             FROM roulette_games 
             WHERE game_status = 'finished' AND winning_number IS NOT NULL 
             GROUP BY winning_number 
             ORDER BY count DESC 
             LIMIT 10"
        );
        $stmt->execute();
        $stats['hot_numbers'] = $stmt->fetchAll();
        
        return $stats;
    }
}
?>