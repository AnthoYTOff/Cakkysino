<?php
// CakkySino - API Blackjack

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gestion des requêtes OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Blackjack.php';
require_once '../classes/CasinoBank.php';

session_start();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$blackjack = new Blackjack();
$user = new User();
$casinoBank = new CasinoBank();
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_games':
            // Récupérer toutes les parties actives
            $games = $blackjack->getActiveGames();
            
            // Ajouter les informations des croupiers
            foreach ($games as &$game) {
                $dealerInfo = $user->getUserInfo($game['dealer_id']);
                $game['dealer_name'] = $dealerInfo ? $dealerInfo['username'] : 'Inconnu';
                
                // Compter les joueurs
                $game['player_count'] = $blackjack->getGamePlayerCount($game['id']);
            }
            
            echo json_encode([
                'success' => true,
                'games' => $games
            ]);
            break;
            
        case 'create_game':
            // Créer une nouvelle partie (admin uniquement)
            $userInfo = $user->getUserInfo($_SESSION['user_id']);
            if (!$userInfo || !$userInfo['is_admin']) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Accès refusé - Admin requis'
                ]);
                break;
            }
            
            $gameId = $blackjack->createGame($_SESSION['user_id']);
            
            if ($gameId) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Partie créée avec succès',
                    'game_id' => $gameId
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur lors de la création de la partie'
                ]);
            }
            break;
            
        case 'join_game':
            // Rejoindre une partie
            $gameId = $input['game_id'] ?? 0;
            $betAmount = $input['bet_amount'] ?? 0;
            
            // Validation
            if ($gameId <= 0 || $betAmount <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Données invalides'
                ]);
                break;
            }
            
            // Vérifier le solde
            $userInfo = $user->getUserInfo($_SESSION['user_id']);
            if (!$userInfo || $userInfo['coins'] < $betAmount) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Solde insuffisant'
                ]);
                break;
            }
            
            // Rejoindre la partie
            $handId = $blackjack->joinGame($gameId, $_SESSION['user_id'], $betAmount);
            
            if ($handId) {
                // Débiter les coins
                $user->updateCoins($_SESSION['user_id'], -$betAmount, 'Mise blackjack #' . $gameId);
                
                $newBalance = $userInfo['coins'] - $betAmount;
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Vous avez rejoint la partie',
                    'hand_id' => $handId,
                    'new_balance' => $newBalance
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Impossible de rejoindre la partie'
                ]);
            }
            break;
            
        case 'start_game':
            // Démarrer une partie (admin uniquement)
            $gameId = $input['game_id'] ?? 0;
            $userInfo = $user->getUserInfo($_SESSION['user_id']);
            
            if (!$userInfo || !$userInfo['is_admin']) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Accès refusé - Admin requis'
                ]);
                break;
            }
            
            $result = $blackjack->startGame($gameId);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Partie démarrée',
                    'hands' => $result
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur lors du démarrage'
                ]);
            }
            break;
            
        case 'get_game_details':
            // Récupérer les détails d'une partie
            $gameId = $input['game_id'] ?? $_GET['game_id'] ?? 0;
            
            if ($gameId <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID de partie invalide'
                ]);
                break;
            }
            
            $game = $blackjack->getGame($gameId);
            $hands = $blackjack->getGameHands($gameId);
            
            if ($game) {
                // Ajouter les informations du croupier
                $dealerInfo = $user->getUserInfo($game['dealer_id']);
                $game['dealer_name'] = $dealerInfo ? $dealerInfo['username'] : 'Inconnu';
                
                // Ajouter les informations des joueurs
                foreach ($hands as &$hand) {
                    $playerInfo = $user->getUserInfo($hand['user_id']);
                    $hand['player_name'] = $playerInfo ? $playerInfo['username'] : 'Inconnu';
                }
                
                echo json_encode([
                    'success' => true,
                    'game' => $game,
                    'hands' => $hands
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Partie introuvable'
                ]);
            }
            break;
            
        case 'player_action':
            // Action du joueur (hit, stand, double)
            $handId = $input['hand_id'] ?? 0;
            $actionType = $input['action_type'] ?? '';
            
            // Vérifier que c'est bien la main du joueur
            $hand = $blackjack->getHand($handId);
            if (!$hand || $hand['user_id'] != $_SESSION['user_id']) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Main invalide ou non autorisée'
                ]);
                break;
            }
            
            $result = false;
            
            switch ($actionType) {
                case 'hit':
                    $result = $blackjack->playerHit($handId);
                    break;
                case 'stand':
                    $result = $blackjack->playerStand($handId);
                    break;
                case 'double':
                    // Vérifier le solde pour doubler
                    $userInfo = $user->getUserInfo($_SESSION['user_id']);
                    if ($userInfo['coins'] < $hand['bet_amount']) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Solde insuffisant pour doubler'
                        ]);
                        break 2;
                    }
                    
                    $result = $blackjack->playerDouble($handId);
                    if ($result) {
                        // Débiter les coins supplémentaires
                        $user->updateCoins($_SESSION['user_id'], -$hand['bet_amount'], 'Double blackjack #' . $hand['game_id']);
                    }
                    break;
                default:
                    echo json_encode([
                        'success' => false,
                        'message' => 'Action invalide'
                    ]);
                    break 2;
            }
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Action effectuée',
                    'hand' => $blackjack->getHand($handId)
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur lors de l\'action'
                ]);
            }
            break;
            
        case 'dealer_action':
            // Action du croupier (admin uniquement)
            $gameId = $input['game_id'] ?? 0;
            $actionType = $input['action_type'] ?? '';
            $userInfo = $user->getUserInfo($_SESSION['user_id']);
            
            if (!$userInfo || !$userInfo['is_admin']) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Accès refusé - Admin requis'
                ]);
                break;
            }
            
            $result = false;
            
            switch ($actionType) {
                case 'deal_card':
                    $handId = $input['hand_id'] ?? 0;
                    $result = $blackjack->dealCard($handId);
                    break;
                case 'finish_game':
                    $result = $blackjack->finishGame($gameId);
                    break;
                default:
                    echo json_encode([
                        'success' => false,
                        'message' => 'Action invalide'
                    ]);
                    break 2;
            }
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Action effectuée',
                    'result' => $result
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur lors de l\'action'
                ]);
            }
            break;
            
        case 'get_history':
            // Récupérer l'historique des parties
            $limit = $input['limit'] ?? 20;
            $offset = $input['offset'] ?? 0;
            
            $history = $blackjack->getGameHistory($limit, $offset);
            
            echo json_encode([
                'success' => true,
                'history' => $history
            ]);
            break;
            
        case 'get_user_history':
            // Récupérer l'historique des mains de l'utilisateur
            $limit = $input['limit'] ?? 20;
            $offset = $input['offset'] ?? 0;
            
            $history = $blackjack->getUserHistory($_SESSION['user_id'], $limit, $offset);
            
            echo json_encode([
                'success' => true,
                'history' => $history
            ]);
            break;
            
        case 'get_stats':
            // Récupérer les statistiques du blackjack
            $stats = $blackjack->getBlackjackStats();
            
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;
            
        case 'get_user_stats':
            // Récupérer les statistiques de l'utilisateur
            $stats = $blackjack->getUserStats($_SESSION['user_id']);
            
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Action non reconnue'
            ]);
            break;
    }
    
} catch (Exception $e) {
    error_log("Erreur API Blackjack: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur interne du serveur'
    ]);
}
?>