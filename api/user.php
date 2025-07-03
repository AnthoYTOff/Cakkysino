<?php
// CakkySino - API Utilisateur

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

session_start();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$user = new User();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_coins':
            // Récupérer le solde de l'utilisateur
            $userInfo = $user->getUserInfo($_SESSION['user_id']);
            if ($userInfo) {
                echo json_encode([
                    'success' => true,
                    'coins' => $userInfo['coins']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Utilisateur introuvable'
                ]);
            }
            break;
            
        case 'get_profile':
            // Récupérer les informations du profil
            $userInfo = $user->getUserInfo($_SESSION['user_id']);
            if ($userInfo) {
                // Ne pas exposer le mot de passe
                unset($userInfo['password']);
                echo json_encode([
                    'success' => true,
                    'user' => $userInfo
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Utilisateur introuvable'
                ]);
            }
            break;
            
        case 'get_history':
            // Récupérer l'historique des coins
            $history = $user->getCoinsHistory($_SESSION['user_id']);
            echo json_encode([
                'success' => true,
                'history' => $history
            ]);
            break;
            
        case 'update_profile':
            // Mettre à jour le profil (nom d'utilisateur uniquement)
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['username']) || empty(trim($input['username']))) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Nom d\'utilisateur requis'
                ]);
                break;
            }
            
            $username = trim($input['username']);
            
            // Vérifier que le nom d'utilisateur n'est pas déjà pris
            $db = getDB();
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $_SESSION['user_id']]);
            
            if ($stmt->fetch()) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Ce nom d\'utilisateur est déjà pris'
                ]);
                break;
            }
            
            // Mettre à jour
            $stmt = $db->prepare("UPDATE users SET username = ?, updated_at = NOW() WHERE id = ?");
            $success = $stmt->execute([$username, $_SESSION['user_id']]);
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Profil mis à jour avec succès'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur lors de la mise à jour'
                ]);
            }
            break;
            
        case 'change_password':
            // Changer le mot de passe
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['current_password']) || !isset($input['new_password'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Mots de passe requis'
                ]);
                break;
            }
            
            $currentPassword = $input['current_password'];
            $newPassword = $input['new_password'];
            
            // Vérifier le mot de passe actuel
            $userInfo = $user->getUserInfo($_SESSION['user_id']);
            if (!$userInfo || !password_verify($currentPassword, $userInfo['password'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Mot de passe actuel incorrect'
                ]);
                break;
            }
            
            // Valider le nouveau mot de passe
            if (strlen($newPassword) < 6) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Le nouveau mot de passe doit contenir au moins 6 caractères'
                ]);
                break;
            }
            
            // Mettre à jour le mot de passe
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $db = getDB();
            $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $success = $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Mot de passe changé avec succès'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur lors du changement de mot de passe'
                ]);
            }
            break;
            
        case 'get_stats':
            // Récupérer les statistiques de l'utilisateur
            $db = getDB();
            
            // Statistiques générales
            $stmt = $db->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM roulette_bets WHERE user_id = ?) as roulette_games,
                    (SELECT COUNT(*) FROM blackjack_hands WHERE user_id = ?) as blackjack_games,
                    (SELECT COALESCE(SUM(amount), 0) FROM passive_earnings WHERE user_id = ?) as passive_earnings,
                    (SELECT COALESCE(SUM(amount), 0) FROM roulette_bets WHERE user_id = ?) as total_roulette_bets,
                    (SELECT COALESCE(SUM(winnings), 0) FROM roulette_bets WHERE user_id = ? AND is_winner = 1) as total_roulette_wins,
                    (SELECT COALESCE(SUM(bet_amount), 0) FROM blackjack_hands WHERE user_id = ?) as total_blackjack_bets,
                    (SELECT COALESCE(SUM(winnings), 0) FROM blackjack_hands WHERE user_id = ? AND result = 'win') as total_blackjack_wins
            ");
            $stmt->execute([
                $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'],
                $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']
            ]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calculer les profits/pertes
            $stats['roulette_profit'] = $stats['total_roulette_wins'] - $stats['total_roulette_bets'];
            $stats['blackjack_profit'] = $stats['total_blackjack_wins'] - $stats['total_blackjack_bets'];
            $stats['total_profit'] = $stats['roulette_profit'] + $stats['blackjack_profit'] + $stats['passive_earnings'];
            
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
    error_log("Erreur API User: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur interne du serveur'
    ]);
}
?>