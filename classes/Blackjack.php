<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/CasinoBank.php';

class Blackjack {
    private $db;
    private $user;
    private $bank;
    
    // Configuration du blackjack
    private $max_players = 4;
    
    // Valeurs des cartes
    private $card_values = [
        'A' => [1, 11], '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6,
        '7' => 7, '8' => 8, '9' => 9, '10' => 10, 'J' => 10, 'Q' => 10, 'K' => 10
    ];
    
    private $suits = ['♠', '♥', '♦', '♣'];
    private $ranks = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];
    
    public function __construct() {
        $this->db = getDB();
        $this->user = new User();
        $this->bank = new CasinoBank();
    }
    
    // Créer une nouvelle partie (par un admin/croupier)
    public function createGame($dealer_id) {
        // Vérifier que le dealer est admin
        $dealer = $this->user->getUserById($dealer_id);
        if (!$dealer || !$dealer['is_admin']) {
            return ['success' => false, 'message' => 'Seuls les administrateurs peuvent créer des parties de blackjack'];
        }
        
        $stmt = $this->db->prepare(
            "INSERT INTO blackjack_games (dealer_id, game_status) VALUES (?, 'waiting')"
        );
        $stmt->execute([$dealer_id]);
        
        $game_id = $this->db->lastInsertId();
        
        return [
            'success' => true,
            'game_id' => $game_id,
            'message' => 'Partie de blackjack créée'
        ];
    }
    
    // Obtenir une partie par ID
    public function getGameById($game_id) {
        $stmt = $this->db->prepare(
            "SELECT bg.*, u.username as dealer_name 
             FROM blackjack_games bg 
             JOIN users u ON bg.dealer_id = u.id 
             WHERE bg.id = ?"
        );
        $stmt->execute([$game_id]);
        return $stmt->fetch();
    }
    
    // Rejoindre une partie
    public function joinGame($game_id, $user_id, $bet_amount) {
        try {
            // Vérifier que l'utilisateur a assez de coins
            $user_data = $this->user->getUserById($user_id);
            if (!$user_data || $user_data['coins'] < $bet_amount) {
                return ['success' => false, 'message' => 'Solde insuffisant'];
            }
            
            // Vérifier la partie
            $game = $this->getGameById($game_id);
            if (!$game) {
                return ['success' => false, 'message' => 'Partie introuvable'];
            }
            
            if ($game['game_status'] !== 'waiting') {
                return ['success' => false, 'message' => 'La partie a déjà commencé'];
            }
            
            // Vérifier le nombre de joueurs
            $current_players = $this->getGamePlayers($game_id);
            if (count($current_players) >= $this->max_players) {
                return ['success' => false, 'message' => 'Partie complète (maximum ' . $this->max_players . ' joueurs)'];
            }
            
            // Vérifier si le joueur n'est pas déjà dans la partie
            foreach ($current_players as $player) {
                if ($player['user_id'] == $user_id) {
                    return ['success' => false, 'message' => 'Vous êtes déjà dans cette partie'];
                }
            }
            
            $this->db->beginTransaction();
            
            // Déduire les coins de l'utilisateur
            $result = $this->user->updateCoins($user_id, -$bet_amount, 'bet_blackjack', "Mise blackjack: {$bet_amount} coins");
            if (!$result['success']) {
                $this->db->rollBack();
                return $result;
            }
            
            // Ajouter les coins à la banque
            $this->bank->receivePayment($bet_amount, "Mise blackjack de l'utilisateur {$user_id}");
            
            // Créer la main du joueur
            $stmt = $this->db->prepare(
                "INSERT INTO blackjack_hands (game_id, user_id, cards, bet_amount, hand_status) 
                 VALUES (?, ?, '[]', ?, 'playing')"
            );
            $stmt->execute([$game_id, $user_id, $bet_amount]);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Rejoint la partie avec succès',
                'new_balance' => $result['new_balance']
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Erreur lors de l\'inscription: ' . $e->getMessage()];
        }
    }
    
    // Obtenir les joueurs d'une partie
    public function getGamePlayers($game_id) {
        $stmt = $this->db->prepare(
            "SELECT bh.*, u.username 
             FROM blackjack_hands bh 
             JOIN users u ON bh.user_id = u.id 
             WHERE bh.game_id = ? 
             ORDER BY bh.created_at ASC"
        );
        $stmt->execute([$game_id]);
        return $stmt->fetchAll();
    }
    
    // Démarrer la partie (distribuer les cartes initiales)
    public function startGame($game_id, $dealer_id) {
        try {
            $game = $this->getGameById($game_id);
            if (!$game || $game['dealer_id'] != $dealer_id) {
                return ['success' => false, 'message' => 'Accès non autorisé'];
            }
            
            if ($game['game_status'] !== 'waiting') {
                return ['success' => false, 'message' => 'La partie ne peut pas être démarrée'];
            }
            
            $players = $this->getGamePlayers($game_id);
            if (count($players) === 0) {
                return ['success' => false, 'message' => 'Aucun joueur dans la partie'];
            }
            
            $this->db->beginTransaction();
            
            // Créer et mélanger le deck
            $deck = $this->createDeck();
            $this->shuffleDeck($deck);
            
            // Distribuer 2 cartes à chaque joueur
            foreach ($players as $player) {
                $cards = [
                    array_pop($deck),
                    array_pop($deck)
                ];
                
                $stmt = $this->db->prepare(
                    "UPDATE blackjack_hands SET cards = ? WHERE id = ?"
                );
                $stmt->execute([json_encode($cards), $player['id']]);
            }
            
            // Distribuer 2 cartes au croupier (1 cachée)
            $dealer_cards = [
                array_pop($deck),
                array_pop($deck)
            ];
            
            // Mettre à jour la partie
            $stmt = $this->db->prepare(
                "UPDATE blackjack_games 
                 SET game_status = 'dealing', dealer_cards = ? 
                 WHERE id = ?"
            );
            $stmt->execute([json_encode($dealer_cards), $game_id]);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Partie démarrée, cartes distribuées',
                'dealer_visible_card' => $dealer_cards[0]
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Erreur lors du démarrage: ' . $e->getMessage()];
        }
    }
    
    // Tirer une carte pour un joueur
    public function hitCard($game_id, $hand_id, $dealer_id) {
        try {
            $game = $this->getGameById($game_id);
            if (!$game || $game['dealer_id'] != $dealer_id) {
                return ['success' => false, 'message' => 'Accès non autorisé'];
            }
            
            // Obtenir la main du joueur
            $stmt = $this->db->prepare("SELECT * FROM blackjack_hands WHERE id = ? AND game_id = ?");
            $stmt->execute([$hand_id, $game_id]);
            $hand = $stmt->fetch();
            
            if (!$hand || $hand['hand_status'] !== 'playing') {
                return ['success' => false, 'message' => 'Main non valide ou terminée'];
            }
            
            // Créer un nouveau deck (simplifié pour la démo)
            $deck = $this->createDeck();
            $this->shuffleDeck($deck);
            $new_card = array_pop($deck);
            
            // Ajouter la carte à la main
            $cards = json_decode($hand['cards'], true);
            $cards[] = $new_card;
            
            // Calculer la nouvelle valeur
            $hand_value = $this->calculateHandValue($cards);
            $new_status = $hand['hand_status'];
            
            if ($hand_value > 21) {
                $new_status = 'bust';
            }
            
            // Mettre à jour la main
            $stmt = $this->db->prepare(
                "UPDATE blackjack_hands SET cards = ?, hand_status = ? WHERE id = ?"
            );
            $stmt->execute([json_encode($cards), $new_status, $hand_id]);
            
            return [
                'success' => true,
                'new_card' => $new_card,
                'hand_value' => $hand_value,
                'hand_status' => $new_status,
                'cards' => $cards
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur lors du tirage: ' . $e->getMessage()];
        }
    }
    
    // Rester (stand)
    public function stand($game_id, $hand_id, $dealer_id) {
        $game = $this->getGameById($game_id);
        if (!$game || $game['dealer_id'] != $dealer_id) {
            return ['success' => false, 'message' => 'Accès non autorisé'];
        }
        
        $stmt = $this->db->prepare(
            "UPDATE blackjack_hands SET hand_status = 'stand' WHERE id = ? AND game_id = ?"
        );
        $stmt->execute([$hand_id, $game_id]);
        
        return ['success' => true, 'message' => 'Joueur reste'];
    }
    
    // Doubler la mise
    public function doubleDown($game_id, $hand_id, $dealer_id) {
        try {
            $game = $this->getGameById($game_id);
            if (!$game || $game['dealer_id'] != $dealer_id) {
                return ['success' => false, 'message' => 'Accès non autorisé'];
            }
            
            // Obtenir la main
            $stmt = $this->db->prepare("SELECT * FROM blackjack_hands WHERE id = ? AND game_id = ?");
            $stmt->execute([$hand_id, $game_id]);
            $hand = $stmt->fetch();
            
            if (!$hand || $hand['hand_status'] !== 'playing' || $hand['doubled']) {
                return ['success' => false, 'message' => 'Impossible de doubler'];
            }
            
            // Vérifier que le joueur a assez de coins
            $user_data = $this->user->getUserById($hand['user_id']);
            if (!$user_data || $user_data['coins'] < $hand['bet_amount']) {
                return ['success' => false, 'message' => 'Solde insuffisant pour doubler'];
            }
            
            $this->db->beginTransaction();
            
            // Déduire les coins supplémentaires
            $result = $this->user->updateCoins(
                $hand['user_id'], 
                -$hand['bet_amount'], 
                'bet_blackjack', 
                "Double mise blackjack: {$hand['bet_amount']} coins"
            );
            
            if (!$result['success']) {
                $this->db->rollBack();
                return $result;
            }
            
            // Ajouter à la banque
            $this->bank->receivePayment($hand['bet_amount'], "Double mise blackjack utilisateur {$hand['user_id']}");
            
            // Tirer une carte et terminer la main
            $deck = $this->createDeck();
            $this->shuffleDeck($deck);
            $new_card = array_pop($deck);
            
            $cards = json_decode($hand['cards'], true);
            $cards[] = $new_card;
            
            $hand_value = $this->calculateHandValue($cards);
            $new_status = $hand_value > 21 ? 'bust' : 'stand';
            $new_bet_amount = $hand['bet_amount'] * 2;
            
            // Mettre à jour la main
            $stmt = $this->db->prepare(
                "UPDATE blackjack_hands 
                 SET cards = ?, hand_status = ?, doubled = TRUE, bet_amount = ? 
                 WHERE id = ?"
            );
            $stmt->execute([json_encode($cards), $new_status, $new_bet_amount, $hand_id]);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'new_card' => $new_card,
                'hand_value' => $hand_value,
                'hand_status' => $new_status,
                'new_bet_amount' => $new_bet_amount,
                'new_balance' => $result['new_balance']
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Erreur lors du double: ' . $e->getMessage()];
        }
    }
    
    // Terminer la partie (jouer la main du croupier)
    public function finishGame($game_id, $dealer_id) {
        try {
            $game = $this->getGameById($game_id);
            if (!$game || $game['dealer_id'] != $dealer_id) {
                return ['success' => false, 'message' => 'Accès non autorisé'];
            }
            
            $this->db->beginTransaction();
            
            // Jouer la main du croupier
            $dealer_cards = json_decode($game['dealer_cards'], true);
            $dealer_value = $this->calculateHandValue($dealer_cards);
            
            // Le croupier tire jusqu'à 17
            $deck = $this->createDeck();
            $this->shuffleDeck($deck);
            
            while ($dealer_value < 17) {
                $dealer_cards[] = array_pop($deck);
                $dealer_value = $this->calculateHandValue($dealer_cards);
            }
            
            // Déterminer les gagnants
            $players = $this->getGamePlayers($game_id);
            $total_winnings = 0;
            
            foreach ($players as $player) {
                $player_cards = json_decode($player['cards'], true);
                $player_value = $this->calculateHandValue($player_cards);
                
                $result = $this->determineWinner($player_value, $dealer_value, $player['hand_status']);
                $winnings = 0;
                
                switch ($result) {
                    case 'win':
                        $winnings = $player['bet_amount'] * 2; // Récupère la mise + gain
                        break;
                    case 'blackjack':
                        $winnings = floor($player['bet_amount'] * 2.5); // Blackjack paye 3:2
                        break;
                    case 'push':
                        $winnings = $player['bet_amount']; // Récupère juste la mise
                        break;
                    case 'lose':
                    default:
                        $winnings = 0;
                        break;
                }
                
                if ($winnings > 0) {
                    $total_winnings += $winnings;
                }
                
                // Mettre à jour la main
                $stmt = $this->db->prepare(
                    "UPDATE blackjack_hands SET hand_status = ?, winnings = ? WHERE id = ?"
                );
                $stmt->execute([$result, $winnings, $player['id']]);
            }
            
            // Vérifier si la banque peut payer
            if ($total_winnings > 0 && !$this->bank->canPay($total_winnings)) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Fonds insuffisants dans la banque du casino'];
            }
            
            // Payer les gagnants
            foreach ($players as $player) {
                $player_cards = json_decode($player['cards'], true);
                $player_value = $this->calculateHandValue($player_cards);
                $result = $this->determineWinner($player_value, $dealer_value, $player['hand_status']);
                
                if ($result === 'win' || $result === 'blackjack' || $result === 'push') {
                    $winnings = 0;
                    switch ($result) {
                        case 'win':
                            $winnings = $player['bet_amount'] * 2;
                            break;
                        case 'blackjack':
                            $winnings = floor($player['bet_amount'] * 2.5);
                            break;
                        case 'push':
                            $winnings = $player['bet_amount'];
                            break;
                    }
                    
                    if ($winnings > 0) {
                        // Créditer l'utilisateur
                        $this->user->updateCoins(
                            $player['user_id'], 
                            $winnings, 
                            'win_blackjack', 
                            "Gain blackjack: {$winnings} coins ({$result})"
                        );
                        
                        // Débiter la banque
                        $this->bank->processPayment($winnings, "Paiement gain blackjack utilisateur {$player['user_id']}");
                    }
                }
            }
            
            // Mettre à jour la partie
            $stmt = $this->db->prepare(
                "UPDATE blackjack_games 
                 SET game_status = 'finished', dealer_cards = ?, finished_at = NOW() 
                 WHERE id = ?"
            );
            $stmt->execute([json_encode($dealer_cards), $game_id]);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'dealer_cards' => $dealer_cards,
                'dealer_value' => $dealer_value,
                'total_winnings' => $total_winnings,
                'results' => $this->getGameResults($game_id)
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Erreur lors de la finalisation: ' . $e->getMessage()];
        }
    }
    
    // Déterminer le gagnant
    private function determineWinner($player_value, $dealer_value, $player_status) {
        if ($player_status === 'bust') {
            return 'lose';
        }
        
        if ($player_value === 21 && count(json_decode($player_status, true)) === 2) {
            if ($dealer_value === 21) {
                return 'push';
            }
            return 'blackjack';
        }
        
        if ($dealer_value > 21) {
            return 'win';
        }
        
        if ($player_value > $dealer_value) {
            return 'win';
        } elseif ($player_value === $dealer_value) {
            return 'push';
        } else {
            return 'lose';
        }
    }
    
    // Créer un deck de cartes
    private function createDeck() {
        $deck = [];
        foreach ($this->suits as $suit) {
            foreach ($this->ranks as $rank) {
                $deck[] = ['rank' => $rank, 'suit' => $suit];
            }
        }
        return $deck;
    }
    
    // Mélanger le deck
    private function shuffleDeck(&$deck) {
        shuffle($deck);
    }
    
    // Calculer la valeur d'une main
    private function calculateHandValue($cards) {
        $value = 0;
        $aces = 0;
        
        foreach ($cards as $card) {
            if ($card['rank'] === 'A') {
                $aces++;
                $value += 11;
            } else {
                $value += $this->card_values[$card['rank']];
            }
        }
        
        // Ajuster pour les As
        while ($value > 21 && $aces > 0) {
            $value -= 10;
            $aces--;
        }
        
        return $value;
    }
    
    // Obtenir les résultats d'une partie
    public function getGameResults($game_id) {
        $stmt = $this->db->prepare(
            "SELECT bh.*, u.username 
             FROM blackjack_hands bh 
             JOIN users u ON bh.user_id = u.id 
             WHERE bh.game_id = ?"
        );
        $stmt->execute([$game_id]);
        return $stmt->fetchAll();
    }
    
    // Obtenir les parties actives
    public function getActiveGames() {
        $stmt = $this->db->prepare(
            "SELECT bg.*, u.username as dealer_name, 
                    COUNT(bh.id) as player_count
             FROM blackjack_games bg 
             JOIN users u ON bg.dealer_id = u.id 
             LEFT JOIN blackjack_hands bh ON bg.id = bh.game_id 
             WHERE bg.game_status IN ('waiting', 'dealing', 'playing') 
             GROUP BY bg.id 
             ORDER BY bg.created_at DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Obtenir l'historique des parties
    public function getGameHistory($limit = 20) {
        $stmt = $this->db->prepare(
            "SELECT bg.*, u.username as dealer_name 
             FROM blackjack_games bg 
             JOIN users u ON bg.dealer_id = u.id 
             WHERE bg.game_status = 'finished' 
             ORDER BY bg.finished_at DESC 
             LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
?>