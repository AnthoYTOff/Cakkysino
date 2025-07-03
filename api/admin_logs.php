<?php
// CakkySino - API Logs d'Administration

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

// Vérifier que l'utilisateur est connecté et admin
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$user = new User();
$userInfo = $user->getUserInfo($_SESSION['user_id']);

if (!$userInfo || !$userInfo['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? 'get_logs';

try {
    $db = getDB();
    
    switch ($action) {
        case 'get_logs':
            // Récupérer les logs récents
            $limit = $input['limit'] ?? $_GET['limit'] ?? 50;
            $offset = $input['offset'] ?? $_GET['offset'] ?? 0;
            
            $stmt = $db->prepare("
                SELECT 
                    al.*,
                    u.username as admin_username
                FROM admin_logs al
                LEFT JOIN users u ON al.admin_id = u.id
                ORDER BY al.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'logs' => $logs
            ]);
            break;
            
        case 'add_log':
            // Ajouter un log (pour les actions manuelles)
            $logAction = $input['log_action'] ?? '';
            $details = $input['details'] ?? '';
            
            if (empty($logAction)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Action requise'
                ]);
                break;
            }
            
            $stmt = $db->prepare("
                INSERT INTO admin_logs (admin_id, action, details, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $success = $stmt->execute([$_SESSION['user_id'], $logAction, $details]);
            
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Log ajouté' : 'Erreur lors de l\'ajout'
            ]);
            break;
            
        case 'get_system_stats':
            // Récupérer les statistiques système
            
            // Statistiques générales
            $stmt = $db->query("
                SELECT 
                    (SELECT COUNT(*) FROM users WHERE is_admin = 0) as total_users,
                    (SELECT COUNT(*) FROM users WHERE is_admin = 0 AND last_activity > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as active_users_24h,
                    (SELECT COUNT(*) FROM users WHERE is_admin = 0 AND last_activity > DATE_SUB(NOW(), INTERVAL 1 HOUR)) as active_users_1h,
                    (SELECT COUNT(*) FROM roulette_games WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as roulette_games_24h,
                    (SELECT COUNT(*) FROM blackjack_games WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as blackjack_games_24h,
                    (SELECT COUNT(*) FROM passive_earning_sessions WHERE start_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as passive_sessions_24h
            ");
            $systemStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Statistiques financières
            $stmt = $db->query("
                SELECT 
                    (SELECT COALESCE(SUM(amount), 0) FROM coin_transactions WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND amount > 0) as coins_earned_24h,
                    (SELECT COALESCE(SUM(ABS(amount)), 0) FROM coin_transactions WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND amount < 0) as coins_spent_24h,
                    (SELECT COALESCE(SUM(bet_amount), 0) FROM roulette_bets WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as roulette_bets_24h,
                    (SELECT COALESCE(SUM(winnings), 0) FROM roulette_bets WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND won = 1) as roulette_winnings_24h,
                    (SELECT COALESCE(SUM(bet_amount), 0) FROM blackjack_hands WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as blackjack_bets_24h,
                    (SELECT COALESCE(SUM(winnings), 0) FROM blackjack_hands WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND result = 'win') as blackjack_winnings_24h
            ");
            $financialStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Combiner les statistiques
            $stats = array_merge($systemStats, $financialStats);
            
            // Calculer les profits
            $stats['roulette_profit_24h'] = $stats['roulette_bets_24h'] - $stats['roulette_winnings_24h'];
            $stats['blackjack_profit_24h'] = $stats['blackjack_bets_24h'] - $stats['blackjack_winnings_24h'];
            $stats['total_profit_24h'] = $stats['roulette_profit_24h'] + $stats['blackjack_profit_24h'];
            
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;
            
        case 'get_user_activity':
            // Récupérer l'activité des utilisateurs
            $limit = $input['limit'] ?? 20;
            
            $stmt = $db->prepare("
                SELECT 
                    u.id,
                    u.username,
                    u.coins,
                    u.last_activity,
                    u.created_at as registration_date,
                    CASE 
                        WHEN u.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 'online'
                        WHEN u.last_activity > DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 'recent'
                        ELSE 'offline'
                    END as status,
                    (
                        SELECT COUNT(*) 
                        FROM passive_earning_sessions pes 
                        WHERE pes.user_id = u.id AND pes.start_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    ) as passive_sessions_24h,
                    (
                        SELECT COUNT(*) 
                        FROM roulette_bets rb 
                        WHERE rb.user_id = u.id AND rb.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    ) as roulette_bets_24h,
                    (
                        SELECT COUNT(*) 
                        FROM blackjack_hands bh 
                        WHERE bh.user_id = u.id AND bh.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    ) as blackjack_hands_24h
                FROM users u
                WHERE u.is_admin = 0
                ORDER BY u.last_activity DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $userActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'users' => $userActivity
            ]);
            break;
            
        case 'get_financial_history':
            // Récupérer l'historique financier
            $limit = $input['limit'] ?? 50;
            $days = $input['days'] ?? 7;
            
            $stmt = $db->prepare("
                SELECT 
                    DATE(created_at) as date,
                    SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as total_earned,
                    SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as total_spent,
                    SUM(amount) as net_change,
                    COUNT(*) as transaction_count
                FROM coin_transactions
                WHERE created_at > DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
                LIMIT ?
            ");
            $stmt->execute([$days, $limit]);
            $financialHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'history' => $financialHistory
            ]);
            break;
            
        case 'get_game_stats':
            // Récupérer les statistiques des jeux
            
            // Statistiques roulette
            $stmt = $db->query("
                SELECT 
                    COUNT(DISTINCT rg.id) as total_games,
                    COUNT(rb.id) as total_bets,
                    COALESCE(SUM(rb.bet_amount), 0) as total_bet_amount,
                    COALESCE(SUM(CASE WHEN rb.won = 1 THEN rb.winnings ELSE 0 END), 0) as total_winnings,
                    COALESCE(AVG(rb.bet_amount), 0) as avg_bet_amount
                FROM roulette_games rg
                LEFT JOIN roulette_bets rb ON rg.id = rb.game_id
                WHERE rg.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $rouletteStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Statistiques blackjack
            $stmt = $db->query("
                SELECT 
                    COUNT(DISTINCT bg.id) as total_games,
                    COUNT(bh.id) as total_hands,
                    COALESCE(SUM(bh.bet_amount), 0) as total_bet_amount,
                    COALESCE(SUM(CASE WHEN bh.result = 'win' THEN bh.winnings ELSE 0 END), 0) as total_winnings,
                    COALESCE(AVG(bh.bet_amount), 0) as avg_bet_amount
                FROM blackjack_games bg
                LEFT JOIN blackjack_hands bh ON bg.id = bh.game_id
                WHERE bg.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $blackjackStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Statistiques gains passifs
            $stmt = $db->query("
                SELECT 
                    COUNT(DISTINCT pes.id) as total_sessions,
                    COALESCE(SUM(pe.coins_earned), 0) as total_coins_earned,
                    COALESCE(SUM(pe.time_spent), 0) as total_time_spent,
                    COALESCE(AVG(pe.coins_earned), 0) as avg_coins_per_earning
                FROM passive_earning_sessions pes
                LEFT JOIN passive_earnings pe ON pes.id = pe.session_id
                WHERE pes.start_time > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $passiveStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'roulette' => $rouletteStats,
                'blackjack' => $blackjackStats,
                'passive' => $passiveStats
            ]);
            break;
            
        case 'clear_old_logs':
            // Nettoyer les anciens logs (plus de 30 jours)
            $stmt = $db->prepare("
                DELETE FROM admin_logs 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute();
            $deletedCount = $stmt->rowCount();
            
            // Ajouter un log de cette action
            $stmt = $db->prepare("
                INSERT INTO admin_logs (admin_id, action, details, created_at)
                VALUES (?, 'SYSTEM_CLEANUP', ?, NOW())
            ");
            $stmt->execute([$_SESSION['user_id'], "Suppression de $deletedCount anciens logs"]);
            
            echo json_encode([
                'success' => true,
                'message' => "$deletedCount logs supprimés",
                'deleted_count' => $deletedCount
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
    error_log("Erreur API Admin Logs: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur interne du serveur'
    ]);
}

// Fonction utilitaire pour ajouter un log automatiquement
function addAdminLog($adminId, $action, $details = '') {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO admin_logs (admin_id, action, details, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$adminId, $action, $details]);
        return true;
    } catch (Exception $e) {
        error_log("Erreur lors de l'ajout du log: " . $e->getMessage());
        return false;
    }
}
?>