<?php
// CakkySino - API Gains Passifs

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
require_once '../classes/PassiveEarnings.php';

session_start();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$passiveEarnings = new PassiveEarnings();
$user = new User();
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'start':
            // Démarrer une session de gains passifs
            $result = $passiveEarnings->startEarning($_SESSION['user_id']);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Session de gains démarrée'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Impossible de démarrer la session'
                ]);
            }
            break;
            
        case 'stop':
            // Arrêter la session de gains passifs
            $result = $passiveEarnings->stopEarning($_SESSION['user_id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Session arrêtée'
            ]);
            break;
            
        case 'update_activity':
            // Mettre à jour l'activité de l'utilisateur
            $result = $passiveEarnings->updateActivity($_SESSION['user_id']);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Activité mise à jour'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Session expirée',
                    'afk' => true
                ]);
            }
            break;
            
        case 'calculate':
            // Calculer et attribuer les gains
            $result = $passiveEarnings->calculateEarnings($_SESSION['user_id']);
            
            if ($result === false) {
                // Session expirée ou utilisateur AFK
                echo json_encode([
                    'success' => false,
                    'message' => 'Session expirée',
                    'afk' => true
                ]);
            } else {
                // Récupérer les informations de la session
                $sessionInfo = $passiveEarnings->getActiveSession($_SESSION['user_id']);
                $userInfo = $user->getUserInfo($_SESSION['user_id']);
                
                $response = [
                    'success' => true,
                    'coins_earned' => $result,
                    'new_balance' => $userInfo['coins'],
                    'time_elapsed' => 0,
                    'next_earning_in' => 30
                ];
                
                if ($sessionInfo) {
                    $startTime = strtotime($sessionInfo['start_time']);
                    $currentTime = time();
                    $timeElapsed = $currentTime - $startTime;
                    
                    $response['time_elapsed'] = $timeElapsed;
                    $response['next_earning_in'] = 30 - ($timeElapsed % 30);
                }
                
                echo json_encode($response);
            }
            break;
            
        case 'status':
            // Vérifier le statut de la session
            $sessionInfo = $passiveEarnings->getActiveSession($_SESSION['user_id']);
            
            if ($sessionInfo) {
                $startTime = strtotime($sessionInfo['start_time']);
                $currentTime = time();
                $timeElapsed = $currentTime - $startTime;
                
                echo json_encode([
                    'success' => true,
                    'active' => true,
                    'session_id' => $sessionInfo['id'],
                    'start_time' => $sessionInfo['start_time'],
                    'time_elapsed' => $timeElapsed,
                    'next_earning_in' => 30 - ($timeElapsed % 30),
                    'total_earned' => $sessionInfo['total_earned']
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'active' => false
                ]);
            }
            break;
            
        case 'get_history':
            // Récupérer l'historique des gains
            $limit = $input['limit'] ?? 20;
            $offset = $input['offset'] ?? 0;
            
            $history = $passiveEarnings->getEarningsHistory($_SESSION['user_id'], $limit, $offset);
            $stats = $passiveEarnings->getEarningsStats($_SESSION['user_id']);
            
            echo json_encode([
                'success' => true,
                'history' => $history,
                'stats' => $stats
            ]);
            break;
            
        case 'get_stats':
            // Récupérer les statistiques des gains passifs
            $stats = $passiveEarnings->getEarningsStats($_SESSION['user_id']);
            
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;
            
        case 'get_leaderboard':
            // Récupérer le classement des gains passifs
            $limit = $input['limit'] ?? 10;
            
            $db = getDB();
            $stmt = $db->prepare("
                SELECT 
                    u.username,
                    COALESCE(SUM(pe.amount), 0) as total_earned,
                    COUNT(DISTINCT pe.session_id) as sessions_count,
                    COALESCE(SUM(pe.time_period_minutes * 60), 0) as total_time
                FROM users u
                LEFT JOIN passive_earnings pe ON u.id = pe.user_id
                WHERE u.is_admin = 0
                GROUP BY u.id, u.username
                HAVING total_earned > 0
                ORDER BY total_earned DESC
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
    error_log("Erreur API Passive Earnings: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur interne du serveur'
    ]);
}
?>