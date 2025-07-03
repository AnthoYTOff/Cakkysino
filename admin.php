<?php
// CakkySino - Interface d'Administration

require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'classes/CasinoBank.php';
require_once 'classes/Blackjack.php';
require_once 'classes/Roulette.php';
require_once 'classes/PassiveEarnings.php';

session_start();

// Vérifier que l'utilisateur est connecté et admin
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user = new User();
$userInfo = $user->getUserInfo($_SESSION['user_id']);

if (!$userInfo || !$userInfo['is_admin']) {
    header('Location: dashboard.php');
    exit;
}

// Initialiser les classes
$casinoBank = new CasinoBank();
$blackjack = new Blackjack();
$roulette = new Roulette();
$passiveEarnings = new PassiveEarnings();

// Récupérer les statistiques
$bankStats = $casinoBank->getBankStats();
$allUsers = $user->getAllUsers();
$activeBlackjackGames = $blackjack->getActiveGames();
$currentRouletteGame = $roulette->getCurrentGame();

// Gestion des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_bank':
            $amount = floatval($_POST['amount'] ?? 0);
            $reason = $_POST['reason'] ?? 'Ajustement admin';
            
            if ($amount != 0) {
                $casinoBank->updateBalance($amount, $reason);
                $message = "Banque mise à jour: " . ($amount > 0 ? "+" : "") . number_format($amount) . " coins";
            }
            break;
            
        case 'create_blackjack':
            $gameId = $blackjack->createGame($_SESSION['user_id']);
            if ($gameId) {
                $message = "Nouvelle table de blackjack créée (#$gameId)";
            } else {
                $error = "Erreur lors de la création de la table";
            }
            break;
            
        case 'force_roulette_spin':
            if ($currentRouletteGame && $currentRouletteGame['game_status'] === 'betting') {
                $result = $roulette->spinRoulette($currentRouletteGame['id']);
                if ($result) {
                    $message = "Roulette forcée - Numéro gagnant: " . $result['winning_number'];
                } else {
                    $error = "Erreur lors du lancement forcé";
                }
            }
            break;
    }
    
    // Recharger les données après action
    $bankStats = $casinoBank->getBankStats();
    $activeBlackjackGames = $blackjack->getActiveGames();
    $currentRouletteGame = $roulette->getCurrentGame();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - CakkySino</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }
        
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .admin-card {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }
        
        .admin-card h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .stat-item:last-child {
            border-bottom: none;
        }
        
        .stat-value {
            font-weight: bold;
            color: var(--accent-color);
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .users-table th,
        .users-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .users-table th {
            background: var(--primary-color);
            color: white;
        }
        
        .online-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .online {
            background: #28a745;
        }
        
        .offline {
            background: #6c757d;
        }
        
        .admin-form {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 15px;
        }
        
        .admin-form input {
            flex: 1;
        }
        
        .game-controls {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid #28a745;
            color: #28a745;
        }
        
        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid #dc3545;
            color: #dc3545;
        }
        
        .logs-container {
            max-height: 400px;
            overflow-y: auto;
            background: var(--bg-dark);
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 12px;
        }
        
        .log-entry {
            margin-bottom: 5px;
            padding: 5px;
            border-left: 3px solid var(--primary-color);
            padding-left: 10px;
        }
        
        .blackjack-table {
            background: var(--bg-dark);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .table-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-waiting {
            background: #ffc107;
            color: #000;
        }
        
        .status-playing {
            background: #28a745;
            color: #fff;
        }
        
        .status-finished {
            background: #6c757d;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- En-tête Admin -->
        <div class="admin-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>🎰 Administration CakkySino</h1>
                    <p>Connecté en tant que: <strong><?= htmlspecialchars($userInfo['username']) ?></strong></p>
                </div>
                <div>
                    <a href="dashboard.php" class="btn btn-secondary">Retour au Dashboard</a>
                    <a href="index.php?logout=1" class="btn btn-danger">Déconnexion</a>
                </div>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Statistiques principales -->
        <div class="admin-grid">
            <!-- Banque du Casino -->
            <div class="admin-card">
                <h3>💰 Banque du Casino</h3>
                <div class="stat-item">
                    <span>Solde actuel:</span>
                    <span class="stat-value"><?= number_format($bankStats['current_balance']) ?> coins</span>
                </div>
                <div class="stat-item">
                    <span>Mises aujourd'hui:</span>
                    <span class="stat-value"><?= number_format($bankStats['daily_bets']) ?> coins</span>
                </div>
                <div class="stat-item">
                    <span>Gains distribués:</span>
                    <span class="stat-value"><?= number_format($bankStats['daily_winnings']) ?> coins</span>
                </div>
                <div class="stat-item">
                    <span>Profit/Perte:</span>
                    <span class="stat-value" style="color: <?= $bankStats['daily_profit'] >= 0 ? '#28a745' : '#dc3545' ?>">
                        <?= ($bankStats['daily_profit'] >= 0 ? '+' : '') . number_format($bankStats['daily_profit']) ?> coins
                    </span>
                </div>
                
                <form method="POST" class="admin-form">
                    <input type="hidden" name="action" value="update_bank">
                    <input type="number" name="amount" placeholder="Montant (+/-)" step="1">
                    <input type="text" name="reason" placeholder="Raison" required>
                    <button type="submit" class="btn btn-primary">Ajuster</button>
                </form>
            </div>
            
            <!-- Utilisateurs en ligne -->
            <div class="admin-card">
                <h3>👥 Utilisateurs (<?= count($allUsers) ?>)</h3>
                <div class="stat-item">
                    <span>Joueurs actifs:</span>
                    <span class="stat-value"><?= $bankStats['active_players'] ?></span>
                </div>
                <div class="stat-item">
                    <span>Coins en circulation:</span>
                    <span class="stat-value"><?= number_format($bankStats['coins_in_circulation']) ?> coins</span>
                </div>
                
                <div style="max-height: 200px; overflow-y: auto; margin-top: 15px;">
                    <?php foreach ($allUsers as $u): ?>
                        <?php if (!$u['is_admin']): ?>
                            <div style="display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid var(--border-color);">
                                <span>
                                    <span class="online-indicator <?= (time() - strtotime($u['last_activity']) < 300) ? 'online' : 'offline' ?>"></span>
                                    <?= htmlspecialchars($u['username']) ?>
                                </span>
                                <span><?= number_format($u['coins']) ?> coins</span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Contrôle Roulette -->
            <div class="admin-card">
                <h3>🎯 Contrôle Roulette</h3>
                <?php if ($currentRouletteGame): ?>
                    <div class="stat-item">
                        <span>Partie active:</span>
                        <span class="stat-value">#<?= $currentRouletteGame['id'] ?></span>
                    </div>
                    <div class="stat-item">
                        <span>Statut:</span>
                        <span class="stat-value"><?= ucfirst($currentRouletteGame['game_status']) ?></span>
                    </div>
                    <div class="stat-item">
                        <span>Joueurs:</span>
                        <span class="stat-value"><?= $roulette->getGamePlayers($currentRouletteGame['id']) ? count($roulette->getGamePlayers($currentRouletteGame['id'])) : 0 ?>/6</span>
                    </div>
                    
                    <?php if ($currentRouletteGame['game_status'] === 'betting'): ?>
                        <form method="POST" class="admin-form">
                            <input type="hidden" name="action" value="force_roulette_spin">
                            <button type="submit" class="btn btn-warning">Forcer le lancement</button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <p>Aucune partie active</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Tables de Blackjack -->
        <div class="admin-card">
            <h3>♠️ Gestion Blackjack</h3>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <span>Tables actives: <?= count($activeBlackjackGames) ?></span>
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="create_blackjack">
                    <button type="submit" class="btn btn-success">Créer une nouvelle table</button>
                </form>
            </div>
            
            <?php if (empty($activeBlackjackGames)): ?>
                <p class="no-data">Aucune table de blackjack active</p>
            <?php else: ?>
                <?php foreach ($activeBlackjackGames as $game): ?>
                    <div class="blackjack-table">
                        <div class="table-header">
                            <h4>Table #<?= $game['id'] ?></h4>
                            <span class="table-status status-<?= $game['game_status'] ?>">
                                <?= ucfirst($game['game_status']) ?>
                            </span>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                            <div>
                                <strong>Informations:</strong><br>
                                Joueurs: <?= $blackjack->getGamePlayerCount($game['id']) ?>/4<br>
                                Créée: <?= date('H:i', strtotime($game['created_at'])) ?>
                            </div>
                            
                            <?php if ($game['game_status'] === 'playing'): ?>
                                <div>
                                    <strong>Actions:</strong><br>
                                    <button class="btn btn-sm btn-primary" onclick="viewGameDetails(<?= $game['id'] ?>)">Voir détails</button>
                                    <button class="btn btn-sm btn-warning" onclick="finishGame(<?= $game['id'] ?>)">Terminer</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Logs récents -->
        <div class="admin-card">
            <h3>📋 Logs récents</h3>
            <div class="logs-container" id="logs-container">
                <!-- Les logs seront chargés via JavaScript -->
                <div class="log-entry">Chargement des logs...</div>
            </div>
            <button class="btn btn-secondary" onclick="refreshLogs()" style="margin-top: 10px;">Actualiser</button>
        </div>
    </div>
    
    <script>
        // Actualiser automatiquement les données
        setInterval(function() {
            location.reload();
        }, 30000); // Toutes les 30 secondes
        
        // Fonctions pour le blackjack
        function viewGameDetails(gameId) {
            // Ouvrir une popup ou rediriger vers une page de détails
            window.open('admin_blackjack.php?game_id=' + gameId, '_blank', 'width=800,height=600');
        }
        
        function finishGame(gameId) {
            if (confirm('Êtes-vous sûr de vouloir terminer cette partie ?')) {
                fetch('api/blackjack.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'dealer_action',
                        game_id: gameId,
                        action_type: 'finish_game'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Partie terminée avec succès');
                        location.reload();
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur de connexion');
                });
            }
        }
        
        // Charger les logs
        function refreshLogs() {
            fetch('api/admin_logs.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const container = document.getElementById('logs-container');
                    container.innerHTML = '';
                    
                    data.logs.forEach(log => {
                        const logEntry = document.createElement('div');
                        logEntry.className = 'log-entry';
                        logEntry.innerHTML = `
                            <strong>[${log.created_at}]</strong> 
                            ${log.action} - ${log.details}
                        `;
                        container.appendChild(logEntry);
                    });
                } else {
                    console.error('Erreur lors du chargement des logs');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
        }
        
        // Charger les logs au démarrage
        document.addEventListener('DOMContentLoaded', function() {
            refreshLogs();
        });
    </script>
</body>
</html>