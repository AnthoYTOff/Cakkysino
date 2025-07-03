<?php
// CakkySino - API Roulette

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
require_once '../classes/Roulette.php';
require_once '../classes/CasinoBank.php';

session_start();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$roulette = new Roulette();
$user = new User();
$casinoBank = new CasinoBank();
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_game':
            // Récupérer la partie active ou en créer une nouvelle
            $game = $roulette->getCurrentGame();
            
            if (!$game) {
                // Créer une nouvelle partie
                $gameId = $roulette->createGame();
                $game = $roulette->getGame($gameId);
            }
            
            // Récupérer les mises et les joueurs
            $bets = $roulette->getGameBets($game['id']);
            $players = $roulette->getGamePlayers($game['id']);
            
            echo json_encode([
                'success' => true,
                'game' => $game,
                'bets' => $bets,
                'players' => $players
            ]);
            break;
            
        case 'place_bet':
            // Placer une mise
            $betType = $input['bet_type'] ?? '';
            $betValue = $input['bet_value'] ?? '';
            $betAmount = $input['bet_amount'] ?? 0;
            
            // Validation des données
            if (empty($betType) || empty($betValue) || $betAmount <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Données de mise invalides'
                ]);
                break;
            }
            
            // Vérifier le solde de l'utilisateur
            $userInfo = $user->getUserInfo($_SESSION['user_id']);
            if (!$userInfo || $userInfo['coins'] < $betAmount) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Solde insuffisant'
                ]);
                break;
            }
            
            // Récupérer ou créer une partie
            $game = $roulette->getCurrentGame();
            if (!$game) {
                $gameId = $roulette->createGame();
                $game = $roulette->getGame($gameId);
            }
            
            // Vérifier que la partie accepte encore les mises
            if ($game['game_status'] !== 'waiting' && $game['game_status'] !== 'betting') {
                echo json_encode([
                    'success' => false,
                    'message' => 'Les mises ne sont plus acceptées pour cette partie'
                ]);
                break;
            }
            
            // Vérifier que l'utilisateur n'a pas déjà misé sur cette partie
            $existingBet = $roulette->getUserBetInGame($_SESSION['user_id'], $game['id']);
            if ($existingBet) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Vous avez déjà misé sur cette partie'
                ]);
                break;
            }
            
            // Placer la mise
            $betId = $roulette->placeBet($game['id'], $_SESSION['user_id'], $betType, $betValue, $betAmount);
            
            if ($betId) {
                // Débiter les coins de l'utilisateur
                $user->updateCoins($_SESSION['user_id'], -$betAmount, 'Mise roulette #' . $game['id']);
                
                // Mettre à jour le statut de la partie
                $players = $roulette->getGamePlayers($game['id']);
                if (count($players) >= 6) {
                    // Partie pleine, lancer automatiquement
                    $roulette->spinRoulette($game['id']);
                } else {
                    // Mettre à jour le statut à "betting"
                    $roulette->updateGameStatus($game['id'], 'betting');
                }
                
                $newBalance = $userInfo['coins'] - $betAmount;
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Mise placée avec succès',
                    'bet_id' => $betId,
                    'new_balance' => $newBalance
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur lors de la mise'
                ]);
            }
            break;
            
        case 'spin_roulette':
            // Lancer la roulette (admin uniquement ou automatique)
            $gameId = $input['game_id'] ?? null;
            
            if (!$gameId) {
                $game = $roulette->getCurrentGame();
                $gameId = $game ? $game['id'] : null;
            }
            
            if (!$gameId) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Aucune partie active'
                ]);
                break;
            }
            
            $result = $roulette->spinRoulette($gameId);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Roulette lancée',
                    'winning_number' => $result['winning_number'],
                    'winning_color' => $result['winning_color'],
                    'winners' => $result['winners']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur lors du lancement'
                ]);
            }
            break;
            
        case 'get_history':
            // Récupérer l'historique des parties
            $limit = $input['limit'] ?? 20;
            $offset = $input['offset'] ?? 0;
            
            $history = $roulette->getGameHistory($limit, $offset);
            
            echo json_encode([
                'success' => true,
                'history' => $history
            ]);
            break;
            
        case 'get_user_history':
            // Récupérer l'historique des mises de l'utilisateur
            $limit = $input['limit'] ?? 20;
            $offset = $input['offset'] ?? 0;
            
            $history = $roulette->getUserBetsHistory($_SESSION['user_id'], $limit, $offset);
            
            echo json_encode([
                'success' => true,
                'history' => $history
            ]);
            break;
            
        case 'get_stats':
            // Récupérer les statistiques de la roulette
            $stats = $roulette->getRouletteStats();
            
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;
            
        case 'get_user_stats':
            // Récupérer les statistiques de l'utilisateur
            $stats = $roulette->getUserStats($_SESSION['user_id']);
            
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;
            
        case 'check_auto_spin':
            // Vérifier si une partie doit être lancée automatiquement
            $game = $roulette->getCurrentGame();
            
            if ($game && $game['game_status'] === 'betting') {
                // Vérifier le temps écoulé depuis la première mise
                $db = getDB();
                $stmt = $db->prepare("
                    SELECT MIN(created_at) as first_bet_time
                    FROM roulette_bets 
                    WHERE game_id = ?
                ");
                $stmt->execute([$game['id']]);
                $result = $stmt->fetch();
                
                if ($result && $result['first_bet_time']) {
                    $firstBetTime = strtotime($result['first_bet_time']);
                    $currentTime = time();
                    $timeElapsed = $currentTime - $firstBetTime;
                    
                    // Lancer automatiquement après 60 secondes
                    if ($timeElapsed >= 60) {
                        $spinResult = $roulette->spinRoulette($game['id']);
                        
                        echo json_encode([
                            'success' => true,
                            'auto_spin' => true,
                            'result' => $spinResult
                        ]);
                        break;
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'auto_spin' => false
            ]);
            break;
            
        case 'get_leaderboard':
            // Récupérer le classement des joueurs
            $limit = $input['limit'] ?? 10;
            
            $db = getDB();
            $stmt = $db->prepare("
                SELECT 
                    u.username,
                    COUNT(rb.id) as games_played,
                    COALESCE(SUM(rb.bet_amount), 0) as total_bets,
                    COALESCE(SUM(CASE WHEN rb.is_winner = 1 THEN rb.winnings ELSE 0 END), 0) as total_winnings,
                    COALESCE(SUM(CASE WHEN rb.is_winner = 1 THEN rb.winnings ELSE 0 END) - SUM(rb.bet_amount), 0) as net_profit
                FROM users u
                LEFT JOIN roulette_bets rb ON u.id = rb.user_id
                WHERE u.is_admin = 0
                GROUP BY u.id, u.username
                HAVING games_played > 0
                ORDER BY net_profit DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'leaderboard' => $leaderboard
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
    error_log("Erreur API Roulette: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur interne du serveur'
    ]);
}
?>