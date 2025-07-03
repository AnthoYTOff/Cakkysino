<?php
// CakkySino - Page de Blackjack

require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'classes/Blackjack.php';

session_start();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user = new User();
$userInfo = $user->getUserInfo($_SESSION['user_id']);
$blackjack = new Blackjack();

// Récupérer les statistiques
$userStats = $blackjack->getUserStats($_SESSION['user_id']);
$generalStats = $blackjack->getGeneralStats();
$history = $blackjack->getUserHistory($_SESSION['user_id'], 10);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blackjack - CakkySino</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .blackjack-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .blackjack-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 30px;
            background: linear-gradient(135deg, #333, #ffd700);
            border-radius: 15px;
            color: white;
        }
        
        .blackjack-header h1 {
            margin: 0 0 10px 0;
            font-size: 2.5em;
        }
        
        .tables-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .tables-list {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
        }
        
        .table-item {
            background: var(--bg-dark);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .table-item:hover {
            border-color: var(--accent-color);
            transform: translateY(-2px);
        }
        
        .table-item.full {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .table-id {
            font-size: 1.3em;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .table-status {
            padding: 6px 12px;
            border-radius: 15px;
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
        
        .table-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
        }
        
        .info-label {
            color: var(--text-secondary);
        }
        
        .info-value {
            font-weight: bold;
            color: var(--accent-color);
        }
        
        .players-preview {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .player-chip {
            background: var(--primary-color);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .join-controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .bet-input {
            flex: 1;
            padding: 8px 12px;
            border: 2px solid var(--border-color);
            border-radius: 6px;
            background: var(--bg-dark);
            color: var(--text-color);
        }
        
        .game-info {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
        }
        
        .current-game {
            background: var(--bg-dark);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .no-game {
            color: var(--text-secondary);
            font-style: italic;
        }
        
        .game-table {
            background: var(--bg-dark);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .dealer-section {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .cards-display {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 15px 0;
            flex-wrap: wrap;
        }
        
        .card {
            width: 60px;
            height: 84px;
            background: white;
            border: 2px solid #333;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            color: #333;
            position: relative;
        }
        
        .card.red {
            color: #dc3545;
        }
        
        .card.hidden {
            background: #333;
            color: #333;
        }
        
        .card.hidden::after {
            content: '🂠';
            color: #666;
            font-size: 24px;
        }
        
        .hand-value {
            font-size: 1.2em;
            font-weight: bold;
            color: var(--accent-color);
            margin: 10px 0;
        }
        
        .player-hand {
            background: var(--card-bg);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .player-hand.active {
            border-color: var(--accent-color);
            box-shadow: 0 0 15px rgba(255, 193, 7, 0.3);
        }
        
        .player-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .player-name {
            font-weight: bold;
        }
        
        .hand-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .player-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .stats-section {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
        }
        
        .stats-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
        }
        
        .stats-title {
            font-size: 1.3em;
            font-weight: bold;
            margin-bottom: 20px;
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .stat-item:last-child {
            border-bottom: none;
        }
        
        .history-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .history-item:last-child {
            border-bottom: none;
        }
        
        .history-result {
            font-weight: bold;
        }
        
        .result-win {
            color: #28a745;
        }
        
        .result-lose {
            color: #dc3545;
        }
        
        .result-push {
            color: #ffc107;
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
        
        .notification.info {
            background: #17a2b8;
        }
        
        @media (max-width: 1200px) {
            .tables-section {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .stats-section {
                grid-template-columns: 1fr;
            }
            
            .table-info {
                grid-template-columns: 1fr;
            }
            
            .join-controls {
                flex-direction: column;
            }
            
            .player-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="blackjack-container">
        <!-- En-tête -->
        <div class="blackjack-header">
            <h1>♠️ Blackjack</h1>
            <p>Affrontez le croupier dans ce jeu de cartes classique !</p>
        </div>
        
        <!-- Section des tables -->
        <div class="tables-section">
            <!-- Liste des tables -->
            <div class="tables-list">
                <h3>🎲 Tables Disponibles</h3>
                
                <div id="tables-container">
                    <!-- Les tables seront chargées par JavaScript -->
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button class="btn btn-primary" onclick="loadTables()">
                        🔄 Actualiser les tables
                    </button>
                </div>
            </div>
            
            <!-- Informations de jeu -->
            <div class="game-info">
                <h3>🎯 Votre Partie</h3>
                
                <div class="current-game" id="current-game">
                    <div class="no-game">
                        Vous n'êtes dans aucune partie
                    </div>
                </div>
                
                <div>
                    <h4>💰 Vos Coins</h4>
                    <div style="font-size: 1.5em; font-weight: bold; color: var(--accent-color);">
                        <span id="user-coins"><?= number_format($userInfo['coins']) ?></span> coins
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <h4>📋 Règles du Blackjack</h4>
                    <ul style="font-size: 0.9em; color: var(--text-secondary);">
                        <li>Le but est d'atteindre 21 sans le dépasser</li>
                        <li>Les figures valent 10, l'As vaut 1 ou 11</li>
                        <li>Blackjack (21 avec 2 cartes) paie 3:2</li>
                        <li>Victoire normale paie 1:1</li>
                        <li>Égalité = remboursement</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Table de jeu (cachée par défaut) -->
        <div class="game-table" id="game-table" style="display: none;">
            <!-- Section croupier -->
            <div class="dealer-section">
                <h3>🎩 Croupier</h3>
                <div class="cards-display" id="dealer-cards">
                    <!-- Cartes du croupier -->
                </div>
                <div class="hand-value" id="dealer-value">
                    Valeur: ?
                </div>
            </div>
            
            <!-- Section joueurs -->
            <div id="players-section">
                <!-- Les mains des joueurs seront affichées ici -->
            </div>
        </div>
        
        <!-- Statistiques -->
        <div class="stats-section">
            <!-- Statistiques personnelles -->
            <div class="stats-card">
                <div class="stats-title">📈 Vos Statistiques</div>
                
                <div class="stat-item">
                    <span>Parties jouées:</span>
                    <span><?= number_format($userStats['games_played']) ?></span>
                </div>
                <div class="stat-item">
                    <span>Parties gagnées:</span>
                    <span><?= number_format($userStats['games_won']) ?></span>
                </div>
                <div class="stat-item">
                    <span>Total misé:</span>
                    <span><?= number_format($userStats['total_bet']) ?></span>
                </div>
                <div class="stat-item">
                    <span>Total gagné:</span>
                    <span><?= number_format($userStats['total_won']) ?></span>
                </div>
                <div class="stat-item">
                    <span>Profit net:</span>
                    <span class="<?= $userStats['net_profit'] >= 0 ? 'result-win' : 'result-lose' ?>">
                        <?= ($userStats['net_profit'] >= 0 ? '+' : '') . number_format($userStats['net_profit']) ?>
                    </span>
                </div>
                <div class="stat-item">
                    <span>Taux de réussite:</span>
                    <span><?= $userStats['games_played'] > 0 ? number_format(($userStats['games_won'] / $userStats['games_played']) * 100, 1) : '0' ?>%</span>
                </div>
            </div>
            
            <!-- Historique -->
            <div class="stats-card">
                <div class="stats-title">📋 Historique Récent</div>
                
                <?php if (empty($history)): ?>
                    <p style="text-align: center; color: var(--text-secondary); padding: 20px;">
                        Aucune partie jouée
                    </p>
                <?php else: ?>
                    <?php foreach ($history as $game): ?>
                        <div class="history-item">
                            <div>
                                <div><strong>Table #<?= $game['game_id'] ?></strong></div>
                                <small><?= date('d/m H:i', strtotime($game['created_at'])) ?></small>
                            </div>
                            <div>
                                <div>Misé: <?= number_format($game['bet_amount']) ?></div>
                                <div class="history-result result-<?= $game['result'] ?>">
                                    <?php
                                    switch ($game['result']) {
                                        case 'win':
                                            echo '+' . number_format($game['winnings']);
                                            break;
                                        case 'lose':
                                            echo '-' . number_format($game['bet_amount']);
                                            break;
                                        case 'push':
                                            echo 'Égalité';
                                            break;
                                        default:
                                            echo 'En cours';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Navigation -->
        <div style="text-align: center; margin-top: 30px;">
            <a href="dashboard.php" class="btn btn-secondary">← Retour au tableau de bord</a>
        </div>
    </div>
    
    <script>
        let currentGameId = null;
        let userCoins = <?= $userInfo['coins'] ?>;
        let gameUpdateInterval = null;
        
        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            loadTables();
            checkCurrentGame();
        });
        
        // Charger les tables disponibles
        function loadTables() {
            fetch('api/blackjack.php?action=get_games')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayTables(data.games);
                } else {
                    showNotification('Erreur lors du chargement des tables', 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur de connexion', 'error');
            });
        }
        
        // Afficher les tables
        function displayTables(tables) {
            const container = document.getElementById('tables-container');
            
            if (tables.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 20px;">Aucune table disponible</p>';
                return;
            }
            
            let html = '';
            
            tables.forEach(table => {
                const isFull = table.players_count >= 6;
                const canJoin = table.game_status === 'waiting' && !isFull;
                
                html += `
                    <div class="table-item ${isFull ? 'full' : ''}" ${canJoin ? `onclick="showJoinDialog(${table.id})"` : ''}>
                        <div class="table-header">
                            <div class="table-id">♠️ Table #${table.id}</div>
                            <div class="table-status status-${table.game_status}">
                                ${getStatusText(table.game_status)}
                            </div>
                        </div>
                        
                        <div class="table-info">
                            <div class="info-item">
                                <span class="info-label">Joueurs:</span>
                                <span class="info-value">${table.players_count}/6</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Mise min:</span>
                                <span class="info-value">10 coins</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Créée:</span>
                                <span class="info-value">${new Date(table.created_at).toLocaleTimeString()}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Croupier:</span>
                                <span class="info-value">${table.dealer_name || 'En attente'}</span>
                            </div>
                        </div>
                        
                        ${table.players && table.players.length > 0 ? `
                            <div class="players-preview">
                                ${table.players.map(player => `<div class="player-chip">${player.username}</div>`).join('')}
                            </div>
                        ` : ''}
                        
                        ${canJoin ? `
                            <div style="margin-top: 15px; text-align: center;">
                                <button class="btn btn-success" onclick="event.stopPropagation(); showJoinDialog(${table.id})">
                                    🎯 Rejoindre
                                </button>
                            </div>
                        ` : ''}
                        
                        ${isFull ? '<div style="text-align: center; color: #dc3545; margin-top: 10px;">Table complète</div>' : ''}
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Obtenir le texte du statut
        function getStatusText(status) {
            switch (status) {
                case 'waiting': return 'En attente';
                case 'playing': return 'En cours';
                case 'finished': return 'Terminée';
                default: return status;
            }
        }
        
        // Afficher la boîte de dialogue pour rejoindre
        function showJoinDialog(tableId) {
            const betAmount = prompt('Entrez votre mise (minimum 10 coins):', '50');
            
            if (betAmount === null) return;
            
            const bet = parseInt(betAmount);
            if (isNaN(bet) || bet < 10) {
                alert('Mise invalide. Minimum 10 coins.');
                return;
            }
            
            if (bet > userCoins) {
                alert('Vous n\'avez pas assez de coins.');
                return;
            }
            
            joinTable(tableId, bet);
        }
        
        // Rejoindre une table
        function joinTable(tableId, betAmount) {
            fetch('api/blackjack.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'join_game',
                    game_id: tableId,
                    bet_amount: betAmount
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentGameId = tableId;
                    userCoins = data.new_balance;
                    document.getElementById('user-coins').textContent = userCoins.toLocaleString();
                    
                    showNotification('Vous avez rejoint la table !', 'success');
                    loadTables();
                    checkCurrentGame();
                    startGameUpdates();
                } else {
                    showNotification('Erreur: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur de connexion', 'error');
            });
        }
        
        // Vérifier la partie actuelle
        function checkCurrentGame() {
            fetch('api/blackjack.php?action=get_user_current_game')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.game) {
                    currentGameId = data.game.id;
                    displayCurrentGame(data.game);
                    startGameUpdates();
                } else {
                    currentGameId = null;
                    hideGameTable();
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
        }
        
        // Afficher la partie actuelle
        function displayCurrentGame(game) {
            const currentGameEl = document.getElementById('current-game');
            
            currentGameEl.innerHTML = `
                <div style="text-align: left;">
                    <h4>♠️ Table #${game.id}</h4>
                    <div class="table-status status-${game.game_status}">
                        ${getStatusText(game.game_status)}
                    </div>
                    <div style="margin-top: 10px;">
                        <strong>Joueurs:</strong> ${game.players_count}/6<br>
                        <strong>Votre mise:</strong> ${game.user_bet ? game.user_bet.toLocaleString() + ' coins' : 'N/A'}
                    </div>
                    <div style="margin-top: 15px;">
                        <button class="btn btn-primary" onclick="loadGameDetails()">
                            🎯 Voir la table
                        </button>
                        <button class="btn btn-danger" onclick="leaveGame()">
                            🚪 Quitter
                        </button>
                    </div>
                </div>
            `;
        }
        
        // Charger les détails de la partie
        function loadGameDetails() {
            if (!currentGameId) return;
            
            fetch(`api/blackjack.php?action=get_game_details&game_id=${currentGameId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayGameTable(data.game, data.hands);
                } else {
                    showNotification('Erreur: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur de connexion', 'error');
            });
        }
        
        // Afficher la table de jeu
        function displayGameTable(game, hands) {
            const gameTable = document.getElementById('game-table');
            gameTable.style.display = 'block';
            
            // Afficher les cartes du croupier
            displayDealerCards(game);
            
            // Afficher les mains des joueurs
            displayPlayerHands(hands, game.game_status);
            
            // Faire défiler vers la table
            gameTable.scrollIntoView({ behavior: 'smooth' });
        }
        
        // Afficher les cartes du croupier
        function displayDealerCards(game) {
            const dealerCards = document.getElementById('dealer-cards');
            const dealerValue = document.getElementById('dealer-value');
            
            if (game.dealer_cards) {
                const cards = JSON.parse(game.dealer_cards);
                let html = '';
                
                cards.forEach((card, index) => {
                    const isHidden = game.game_status === 'playing' && index === 1;
                    const cardColor = ['hearts', 'diamonds'].includes(card.suit) ? 'red' : '';
                    
                    html += `
                        <div class="card ${cardColor} ${isHidden ? 'hidden' : ''}">
                            ${isHidden ? '' : getCardDisplay(card)}
                        </div>
                    `;
                });
                
                dealerCards.innerHTML = html;
                dealerValue.textContent = `Valeur: ${game.game_status === 'playing' ? '?' : game.dealer_value}`;
            } else {
                dealerCards.innerHTML = '';
                dealerValue.textContent = 'Valeur: ?';
            }
        }
        
        // Afficher les mains des joueurs
        function displayPlayerHands(hands, gameStatus) {
            const playersSection = document.getElementById('players-section');
            let html = '';
            
            hands.forEach(hand => {
                const isCurrentUser = hand.user_id == <?= $_SESSION['user_id'] ?>;
                const isActive = hand.hand_status === 'playing';
                
                html += `
                    <div class="player-hand ${isActive ? 'active' : ''}">
                        <div class="player-info">
                            <div class="player-name">
                                ${hand.player_name} ${isCurrentUser ? '(Vous)' : ''}
                            </div>
                            <div class="hand-status status-${hand.hand_status}">
                                ${getHandStatusText(hand.hand_status)}
                            </div>
                        </div>
                        
                        <div>
                            <strong>Mise:</strong> ${hand.bet_amount.toLocaleString()} coins
                            ${hand.doubled ? '<span style="color: var(--accent-color);">(Doublée)</span>' : ''}
                        </div>
                        
                        ${hand.cards ? `
                            <div class="cards-display">
                                ${JSON.parse(hand.cards).map(card => {
                                    const cardColor = ['hearts', 'diamonds'].includes(card.suit) ? 'red' : '';
                                    return `<div class="card ${cardColor}">${getCardDisplay(card)}</div>`;
                                }).join('')}
                            </div>
                            
                            <div class="hand-value">
                                Valeur: ${hand.hand_value}
                            </div>
                        ` : ''}
                        
                        ${hand.result ? `
                            <div style="margin-top: 10px;">
                                <strong>Résultat:</strong> 
                                <span class="result-${hand.result}">
                                    ${getResultText(hand.result)}
                                </span>
                                ${hand.winnings > 0 ? `<br><strong>Gains:</strong> ${hand.winnings.toLocaleString()} coins` : ''}
                            </div>
                        ` : ''}
                        
                        ${isCurrentUser && isActive && gameStatus === 'playing' ? `
                            <div class="player-actions">
                                <button class="btn btn-primary" onclick="playerAction('hit', ${hand.id})">
                                    🃏 Tirer
                                </button>
                                <button class="btn btn-warning" onclick="playerAction('stand', ${hand.id})">
                                    ✋ Rester
                                </button>
                                ${!hand.doubled && JSON.parse(hand.cards).length === 2 ? `
                                    <button class="btn btn-success" onclick="playerAction('double', ${hand.id})">
                                        ⬆️ Doubler
                                    </button>
                                ` : ''}
                            </div>
                        ` : ''}
                    </div>
                `;
            });
            
            playersSection.innerHTML = html;
        }
        
        // Obtenir l'affichage d'une carte
        function getCardDisplay(card) {
            const suits = {
                'hearts': '♥',
                'diamonds': '♦',
                'clubs': '♣',
                'spades': '♠'
            };
            
            return card.value + suits[card.suit];
        }
        
        // Obtenir le texte du statut de la main
        function getHandStatusText(status) {
            switch (status) {
                case 'playing': return 'En jeu';
                case 'stand': return 'Reste';
                case 'bust': return 'Bust';
                case 'blackjack': return 'Blackjack';
                case 'finished': return 'Terminé';
                default: return status;
            }
        }
        
        // Obtenir le texte du résultat
        function getResultText(result) {
            switch (result) {
                case 'win': return 'Victoire';
                case 'lose': return 'Défaite';
                case 'push': return 'Égalité';
                default: return result;
            }
        }
        
        // Action du joueur
        function playerAction(action, handId) {
            fetch('api/blackjack.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'player_action',
                    hand_id: handId,
                    action_type: action
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.new_balance !== undefined) {
                        userCoins = data.new_balance;
                        document.getElementById('user-coins').textContent = userCoins.toLocaleString();
                    }
                    
                    showNotification(getActionMessage(action), 'success');
                    
                    // Recharger les détails de la partie
                    setTimeout(loadGameDetails, 500);
                } else {
                    showNotification('Erreur: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur de connexion', 'error');
            });
        }
        
        // Obtenir le message d'action
        function getActionMessage(action) {
            switch (action) {
                case 'hit': return 'Carte tirée';
                case 'stand': return 'Vous restez';
                case 'double': return 'Mise doublée';
                default: return 'Action effectuée';
            }
        }
        
        // Quitter la partie
        function leaveGame() {
            if (!currentGameId) return;
            
            if (confirm('Êtes-vous sûr de vouloir quitter cette partie ?')) {
                // Logique pour quitter la partie
                currentGameId = null;
                hideGameTable();
                loadTables();
                showNotification('Vous avez quitté la partie', 'info');
            }
        }
        
        // Cacher la table de jeu
        function hideGameTable() {
            document.getElementById('game-table').style.display = 'none';
            document.getElementById('current-game').innerHTML = '<div class="no-game">Vous n\'êtes dans aucune partie</div>';
        }
        
        // Démarrer les mises à jour automatiques
        function startGameUpdates() {
            if (gameUpdateInterval) {
                clearInterval(gameUpdateInterval);
            }
            
            gameUpdateInterval = setInterval(() => {
                if (currentGameId) {
                    loadGameDetails();
                    checkCurrentGame();
                }
            }, 3000);
        }
        
        // Arrêter les mises à jour automatiques
        function stopGameUpdates() {
            if (gameUpdateInterval) {
                clearInterval(gameUpdateInterval);
                gameUpdateInterval = null;
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
        
        // Nettoyage lors de la fermeture de la page
        window.addEventListener('beforeunload', () => {
            stopGameUpdates();
        });
    </script>
</body>
</html>