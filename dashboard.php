<?php
session_start();
require_once 'classes/User.php';
require_once 'classes/CasinoBank.php';
require_once 'classes/PassiveEarnings.php';
require_once 'classes/Roulette.php';
require_once 'classes/Blackjack.php';

$user_obj = new User();
$bank = new CasinoBank();
$passive = new PassiveEarnings();
$roulette = new Roulette();
$blackjack = new Blackjack();

// Vérifier l'authentification
if (!isset($_COOKIE['session_token'])) {
    header('Location: index.php');
    exit;
}

$current_user = $user_obj->verifySession($_COOKIE['session_token']);
if (!$current_user) {
    setcookie('session_token', '', time() - 3600, '/');
    header('Location: index.php');
    exit;
}

// Obtenir les données utilisateur actualisées
$user_data = $user_obj->getUserById($current_user['user_id']);
$transaction_history = $user_obj->getTransactionHistory($current_user['user_id'], 10);
$earnings_stats = $passive->getUserEarningsStats($current_user['user_id']);

// Obtenir les jeux actifs
$active_roulette = $roulette->getActiveGame();
$active_blackjack_games = $blackjack->getActiveGames();

// Traitement de la déconnexion
if (isset($_POST['action']) && $_POST['action'] === 'logout') {
    $user_obj->logout($_COOKIE['session_token']);
    setcookie('session_token', '', time() - 3600, '/');
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CakkySino</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard">
    <nav class="navbar">
        <div class="nav-brand">
            <h1>🎰 CakkySino</h1>
        </div>
        <div class="nav-user">
            <span class="user-info">
                <strong><?php echo htmlspecialchars($user_data['username']); ?></strong>
                <?php if ($user_data['is_admin']): ?>
                    <span class="admin-badge">ADMIN</span>
                <?php endif; ?>
            </span>
            <span class="user-coins">
                💰 <span id="user-coins"><?php echo number_format($user_data['coins']); ?></span> coins
            </span>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="btn btn-secondary btn-sm">Déconnexion</button>
            </form>
        </div>
    </nav>
    
    <div class="dashboard-container">
        <div class="dashboard-sidebar">
            <div class="sidebar-menu">
                <a href="#" class="menu-item active" onclick="showSection('overview')">📊 Vue d'ensemble</a>
                <a href="#" class="menu-item" onclick="showSection('passive-earning')">💰 Gains Passifs</a>
                <a href="#" class="menu-item" onclick="showSection('roulette')">🎰 Roulette</a>
                <a href="#" class="menu-item" onclick="showSection('blackjack')">♠️ Blackjack</a>
                <a href="#" class="menu-item" onclick="showSection('history')">📜 Historique</a>
                <?php if ($user_data['is_admin']): ?>
                    <a href="admin.php" class="menu-item admin-link">⚙️ Administration</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="dashboard-content">
            <!-- Vue d'ensemble -->
            <div id="overview-section" class="content-section active">
                <h2>Vue d'ensemble</h2>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-info">
                            <h3><?php echo number_format($user_data['coins']); ?></h3>
                            <p>Coins disponibles</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">⏱️</div>
                        <div class="stat-info">
                            <h3><?php echo number_format($earnings_stats['today']['today_coins'] ?? 0); ?></h3>
                            <p>Coins gagnés aujourd'hui</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🎯</div>
                        <div class="stat-info">
                            <h3><?php echo number_format($earnings_stats['total']['total_coins_earned'] ?? 0); ?></h3>
                            <p>Total gains passifs</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🎮</div>
                        <div class="stat-info">
                            <h3><?php echo count($active_blackjack_games); ?></h3>
                            <p>Parties actives</p>
                        </div>
                    </div>
                </div>
                
                <div class="quick-actions">
                    <h3>Actions rapides</h3>
                    <div class="action-buttons">
                        <button class="btn btn-primary" onclick="showSection('passive-earning')">
                            💰 Commencer à gagner des coins
                        </button>
                        <button class="btn btn-success" onclick="showSection('roulette')">
                            🎰 Jouer à la roulette
                        </button>
                        <button class="btn btn-warning" onclick="showSection('blackjack')">
                            ♠️ Rejoindre une table de blackjack
                        </button>
                    </div>
                </div>
                
                <div class="recent-activity">
                    <h3>Activité récente</h3>
                    <div class="transaction-list">
                        <?php if (empty($transaction_history)): ?>
                            <p class="no-data">Aucune transaction récente</p>
                        <?php else: ?>
                            <?php foreach ($transaction_history as $transaction): ?>
                                <div class="transaction-item">
                                    <div class="transaction-type">
                                        <?php
                                        $icons = [
                                            'earn_passive' => '💰',
                                            'bet_roulette' => '🎰',
                                            'win_roulette' => '🎰✅',
                                            'bet_blackjack' => '♠️',
                                            'win_blackjack' => '♠️✅',
                                            'admin_adjustment' => '⚙️'
                                        ];
                                        echo $icons[$transaction['transaction_type']] ?? '💫';
                                        ?>
                                    </div>
                                    <div class="transaction-details">
                                        <div class="transaction-description">
                                            <?php echo htmlspecialchars($transaction['description']); ?>
                                        </div>
                                        <div class="transaction-date">
                                            <?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?>
                                        </div>
                                    </div>
                                    <div class="transaction-amount <?php echo $transaction['amount'] > 0 ? 'positive' : 'negative'; ?>">
                                        <?php echo $transaction['amount'] > 0 ? '+' : ''; ?><?php echo number_format($transaction['amount']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Gains Passifs -->
            <div id="passive-earning-section" class="content-section">
                <h2>💰 Gains Passifs</h2>
                <p>Gagnez 1 coin toutes les 30 secondes en restant actif sur cette page !</p>
                
                <div class="earning-status" id="earning-status">
                    <div class="status-inactive">
                        <h3>Session inactive</h3>
                        <p>Cliquez sur "Commencer" pour débuter vos gains passifs</p>
                        <button class="btn btn-primary" onclick="startEarning()">Commencer à gagner</button>
                    </div>
                </div>
                
                <div class="earning-stats">
                    <h3>Statistiques de gains</h3>
                    <div class="stats-row">
                        <div class="stat-item">
                            <strong>Aujourd'hui:</strong> 
                            <?php echo number_format($earnings_stats['today']['today_coins'] ?? 0); ?> coins
                        </div>
                        <div class="stat-item">
                            <strong>Total:</strong> 
                            <?php echo number_format($earnings_stats['total']['total_coins_earned'] ?? 0); ?> coins
                        </div>
                        <div class="stat-item">
                            <strong>Sessions:</strong> 
                            <?php echo number_format($earnings_stats['total']['total_sessions'] ?? 0); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Roulette -->
            <div id="roulette-section" class="content-section">
                <h2>🎰 Roulette Française</h2>
                <div id="roulette-game">
                    <!-- Le contenu sera chargé dynamiquement -->
                    <p>Chargement de la roulette...</p>
                </div>
            </div>
            
            <!-- Blackjack -->
            <div id="blackjack-section" class="content-section">
                <h2>♠️ Blackjack</h2>
                <div id="blackjack-games">
                    <!-- Le contenu sera chargé dynamiquement -->
                    <p>Chargement des tables de blackjack...</p>
                </div>
            </div>
            
            <!-- Historique -->
            <div id="history-section" class="content-section">
                <h2>📜 Historique des transactions</h2>
                <div class="transaction-history">
                    <?php if (empty($transaction_history)): ?>
                        <p class="no-data">Aucune transaction dans l'historique</p>
                    <?php else: ?>
                        <div class="transaction-table">
                            <div class="table-header">
                                <div>Type</div>
                                <div>Description</div>
                                <div>Montant</div>
                                <div>Solde après</div>
                                <div>Date</div>
                            </div>
                            <?php foreach ($user_obj->getTransactionHistory($current_user['user_id'], 50) as $transaction): ?>
                                <div class="table-row">
                                    <div class="transaction-type-cell">
                                        <?php
                                        $types = [
                                            'earn_passive' => 'Gains passifs',
                                            'bet_roulette' => 'Mise roulette',
                                            'win_roulette' => 'Gain roulette',
                                            'bet_blackjack' => 'Mise blackjack',
                                            'win_blackjack' => 'Gain blackjack',
                                            'admin_adjustment' => 'Ajustement admin'
                                        ];
                                        echo $types[$transaction['transaction_type']] ?? $transaction['transaction_type'];
                                        ?>
                                    </div>
                                    <div><?php echo htmlspecialchars($transaction['description']); ?></div>
                                    <div class="amount <?php echo $transaction['amount'] > 0 ? 'positive' : 'negative'; ?>">
                                        <?php echo $transaction['amount'] > 0 ? '+' : ''; ?><?php echo number_format($transaction['amount']); ?>
                                    </div>
                                    <div><?php echo number_format($transaction['balance_after']); ?></div>
                                    <div><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/dashboard.js"></script>
    <script>
        // Initialiser le dashboard
        document.addEventListener('DOMContentLoaded', function() {
            // Charger les jeux
            loadRouletteGame();
            loadBlackjackGames();
            
            // Vérifier le statut des gains passifs
            checkEarningStatus();
        });
    </script>
</body>
</html>