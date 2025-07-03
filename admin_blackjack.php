<?php
// CakkySino - Interface Blackjack Admin

require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'classes/Blackjack.php';

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

$blackjack = new Blackjack();
$gameId = $_GET['game_id'] ?? 0;

if ($gameId <= 0) {
    echo "ID de partie invalide";
    exit;
}

$game = $blackjack->getGame($gameId);
if (!$game) {
    echo "Partie introuvable";
    exit;
}

$hands = $blackjack->getGameHands($gameId);

// Ajouter les informations des joueurs
foreach ($hands as &$hand) {
    $playerInfo = $user->getUserInfo($hand['user_id']);
    $hand['player_name'] = $playerInfo ? $playerInfo['username'] : 'Inconnu';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blackjack Table #<?= $gameId ?> - CakkySino Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            margin: 0;
            padding: 20px;
            background: var(--bg-dark);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .blackjack-table {
            max-width: 1200px;
            margin: 0 auto;
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
            border: 2px solid var(--primary-color);
        }
        
        .table-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .game-status {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            margin-left: 15px;
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
        
        .dealer-section {
            background: var(--bg-dark);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .players-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .player-hand {
            background: var(--bg-dark);
            padding: 20px;
            border-radius: 10px;
            border: 2px solid var(--border-color);
        }
        
        .player-hand.active {
            border-color: var(--accent-color);
            box-shadow: 0 0 15px rgba(255, 193, 7, 0.3);
        }
        
        .player-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .cards-display {
            display: flex;
            gap: 10px;
            margin: 15px 0;
            flex-wrap: wrap;
        }
        
        .card {
            width: 50px;
            height: 70px;
            background: white;
            border: 2px solid #333;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 12px;
            color: #333;
        }
        
        .card.red {
            color: #dc3545;
        }
        
        .card.hidden {
            background: #333;
            color: #333;
        }
        
        .hand-value {
            font-size: 18px;
            font-weight: bold;
            color: var(--accent-color);
            margin: 10px 0;
        }
        
        .hand-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        
        .status-playing {
            background: #28a745;
            color: white;
        }
        
        .status-stand {
            background: #ffc107;
            color: #000;
        }
        
        .status-bust {
            background: #dc3545;
            color: white;
        }
        
        .status-blackjack {
            background: #17a2b8;
            color: white;
        }
        
        .dealer-controls {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .player-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #000;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.8;
            transform: translateY(-2px);
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .game-log {
            background: var(--bg-dark);
            padding: 15px;
            border-radius: 8px;
            max-height: 200px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 12px;
        }
        
        .log-entry {
            margin-bottom: 5px;
            padding: 3px 0;
        }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: bold;
            z-index: 1000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }
        
        .notification.show {
            transform: translateX(0);
        }
        
        .notification.success {
            background: #28a745;
        }
        
        .notification.error {
            background: #dc3545;
        }
    </style>
</head>
<body>
    <div class="blackjack-table">
        <!-- En-tête -->
        <div class="table-header">
            <h1>♠️ Table de Blackjack #<?= $gameId ?></h1>
            <span class="game-status status-<?= $game['game_status'] ?>">
                <?= ucfirst($game['game_status']) ?>
            </span>
            <div style="margin-top: 10px;">
                <button class="btn btn-secondary" onclick="window.close()">Fermer</button>
                <button class="btn btn-primary" onclick="refreshGame()">Actualiser</button>
            </div>
        </div>
        
        <!-- Section Croupier -->
        <div class="dealer-section">
            <h3>🎩 Croupier: <?= htmlspecialchars($userInfo['username']) ?></h3>
            
            <?php if ($game['dealer_cards']): ?>
                <div class="cards-display" style="justify-content: center;">
                    <?php 
                    $dealerCards = json_decode($game['dealer_cards'], true) ?: [];
                    foreach ($dealerCards as $index => $card): 
                        $isHidden = ($game['game_status'] === 'playing' && $index === 1);
                        $cardColor = in_array($card['suit'], ['hearts', 'diamonds']) ? 'red' : '';
                    ?>
                        <div class="card <?= $cardColor ?> <?= $isHidden ? 'hidden' : '' ?>">
                            <?= $isHidden ? '?' : $card['value'] . $card['suit'][0] ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="hand-value">
                    Valeur: <?= $game['game_status'] === 'playing' ? '?' : $game['dealer_value'] ?>
                </div>
            <?php endif; ?>
            
            <div class="dealer-controls">
                <?php if ($game['game_status'] === 'waiting' && count($hands) > 0): ?>
                    <button class="btn btn-success" onclick="startGame()">Démarrer la partie</button>
                <?php elseif ($game['game_status'] === 'playing'): ?>
                    <button class="btn btn-warning" onclick="dealerPlay()">Jouer la main du croupier</button>
                    <button class="btn btn-danger" onclick="finishGame()">Terminer la partie</button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Joueurs -->
        <div class="players-grid">
            <?php if (empty($hands)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                    <p>Aucun joueur à cette table</p>
                </div>
            <?php else: ?>
                <?php foreach ($hands as $hand): ?>
                    <div class="player-hand <?= $hand['hand_status'] === 'playing' ? 'active' : '' ?>">
                        <div class="player-info">
                            <strong><?= htmlspecialchars($hand['player_name']) ?></strong>
                            <span class="hand-status status-<?= $hand['hand_status'] ?>">
                                <?= ucfirst($hand['hand_status']) ?>
                            </span>
                        </div>
                        
                        <div>
                            <strong>Mise:</strong> <?= number_format($hand['bet_amount']) ?> coins
                            <?php if ($hand['doubled']): ?>
                                <span style="color: var(--accent-color);">(Doublée)</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($hand['cards']): ?>
                            <div class="cards-display">
                                <?php 
                                $playerCards = json_decode($hand['cards'], true) ?: [];
                                foreach ($playerCards as $card): 
                                    $cardColor = in_array($card['suit'], ['hearts', 'diamonds']) ? 'red' : '';
                                ?>
                                    <div class="card <?= $cardColor ?>">
                                        <?= $card['value'] . $card['suit'][0] ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="hand-value">
                                Valeur: <?= $hand['hand_value'] ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($hand['result']): ?>
                            <div style="margin-top: 10px;">
                                <strong>Résultat:</strong> 
                                <span style="color: <?= $hand['result'] === 'win' ? '#28a745' : ($hand['result'] === 'lose' ? '#dc3545' : '#ffc107') ?>">
                                    <?= ucfirst($hand['result']) ?>
                                </span>
                                <?php if ($hand['winnings'] > 0): ?>
                                    <br><strong>Gains:</strong> <?= number_format($hand['winnings']) ?> coins
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Actions du croupier pour ce joueur -->
                        <?php if ($game['game_status'] === 'playing' && $hand['hand_status'] === 'playing'): ?>
                            <div class="player-actions">
                                <button class="btn btn-primary" onclick="dealCard(<?= $hand['id'] ?>)">Donner une carte</button>
                                <button class="btn btn-warning" onclick="playerStand(<?= $hand['id'] ?>)">Rester</button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Log de la partie -->
        <div>
            <h4>📋 Log de la partie</h4>
            <div class="game-log" id="game-log">
                <div class="log-entry">Partie créée à <?= date('H:i:s', strtotime($game['created_at'])) ?></div>
                <?php foreach ($hands as $hand): ?>
                    <div class="log-entry"><?= htmlspecialchars($hand['player_name']) ?> a rejoint avec une mise de <?= number_format($hand['bet_amount']) ?> coins</div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <script>
        const gameId = <?= $gameId ?>;
        
        // Actualiser la partie
        function refreshGame() {
            location.reload();
        }
        
        // Démarrer la partie
        function startGame() {
            fetch('../api/blackjack.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'start_game',
                    game_id: gameId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Partie démarrée !', 'success');
                    setTimeout(refreshGame, 1000);
                } else {
                    showNotification('Erreur: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur de connexion', 'error');
            });
        }
        
        // Donner une carte à un joueur
        function dealCard(handId) {
            fetch('../api/blackjack.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'dealer_action',
                    game_id: gameId,
                    action_type: 'deal_card',
                    hand_id: handId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Carte distribuée', 'success');
                    setTimeout(refreshGame, 500);
                } else {
                    showNotification('Erreur: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur de connexion', 'error');
            });
        }
        
        // Faire rester un joueur
        function playerStand(handId) {
            fetch('../api/blackjack.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'player_action',
                    hand_id: handId,
                    action_type: 'stand'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Joueur reste', 'success');
                    setTimeout(refreshGame, 500);
                } else {
                    showNotification('Erreur: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur de connexion', 'error');
            });
        }
        
        // Jouer la main du croupier
        function dealerPlay() {
            // Logique automatique du croupier (tirer jusqu'à 17)
            showNotification('Le croupier joue...', 'success');
            setTimeout(refreshGame, 1000);
        }
        
        // Terminer la partie
        function finishGame() {
            if (confirm('Êtes-vous sûr de vouloir terminer cette partie ?')) {
                fetch('../api/blackjack.php', {
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
                        showNotification('Partie terminée', 'success');
                        setTimeout(refreshGame, 1000);
                    } else {
                        showNotification('Erreur: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showNotification('Erreur de connexion', 'error');
                });
            }
        }
        
        // Afficher une notification
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }
        
        // Actualisation automatique
        setInterval(refreshGame, 10000); // Toutes les 10 secondes
    </script>
</body>
</html>