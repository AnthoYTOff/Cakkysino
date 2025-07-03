<?php
// CakkySino - Page de Roulette

require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'classes/Roulette.php';

session_start();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user = new User();
$userInfo = $user->getUserInfo($_SESSION['user_id']);
$roulette = new Roulette();

// Récupérer les statistiques
$userStats = $roulette->getUserStats($_SESSION['user_id']);
$generalStats = $roulette->getGeneralStats();
$history = $roulette->getUserHistory($_SESSION['user_id'], 10);
$leaderboard = $roulette->getLeaderboard(10);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roulette Française - CakkySino</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .roulette-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .roulette-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 30px;
            background: linear-gradient(135deg, #dc3545, #28a745);
            border-radius: 15px;
            color: white;
        }
        
        .roulette-header h1 {
            margin: 0 0 10px 0;
            font-size: 2.5em;
        }
        
        .game-area {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .roulette-wheel-section {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
        }
        
        .wheel-container {
            position: relative;
            width: 300px;
            height: 300px;
            margin: 20px auto;
        }
        
        .roulette-wheel {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 8px solid #333;
            background: conic-gradient(
                from 0deg,
                #dc3545 0deg 9.73deg,
                #000 9.73deg 19.46deg,
                #dc3545 19.46deg 29.19deg,
                #000 29.19deg 38.92deg,
                #dc3545 38.92deg 48.65deg,
                #000 48.65deg 58.38deg,
                #dc3545 58.38deg 68.11deg,
                #000 68.11deg 77.84deg,
                #dc3545 77.84deg 87.57deg,
                #000 87.57deg 97.3deg,
                #dc3545 97.3deg 107.03deg,
                #000 107.03deg 116.76deg,
                #dc3545 116.76deg 126.49deg,
                #000 126.49deg 136.22deg,
                #dc3545 136.22deg 145.95deg,
                #000 145.95deg 155.68deg,
                #dc3545 155.68deg 165.41deg,
                #000 165.41deg 175.14deg,
                #dc3545 175.14deg 184.87deg,
                #000 184.87deg 194.6deg,
                #dc3545 194.6deg 204.33deg,
                #000 204.33deg 214.06deg,
                #dc3545 214.06deg 223.79deg,
                #000 223.79deg 233.52deg,
                #dc3545 233.52deg 243.25deg,
                #000 243.25deg 252.98deg,
                #dc3545 252.98deg 262.71deg,
                #000 262.71deg 272.44deg,
                #dc3545 272.44deg 282.17deg,
                #000 282.17deg 291.9deg,
                #dc3545 291.9deg 301.63deg,
                #000 301.63deg 311.36deg,
                #dc3545 311.36deg 321.09deg,
                #000 321.09deg 330.82deg,
                #dc3545 330.82deg 340.55deg,
                #000 340.55deg 350.28deg,
                #28a745 350.28deg 360deg
            );
            transition: transform 3s cubic-bezier(0.25, 0.1, 0.25, 1);
            position: relative;
        }
        
        .wheel-pointer {
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 15px solid transparent;
            border-right: 15px solid transparent;
            border-top: 30px solid #ffd700;
            z-index: 10;
        }
        
        .wheel-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60px;
            height: 60px;
            background: #ffd700;
            border-radius: 50%;
            border: 4px solid #333;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            z-index: 5;
        }
        
        .winning-number {
            font-size: 2em;
            font-weight: bold;
            margin: 20px 0;
            padding: 15px;
            border-radius: 10px;
            background: var(--bg-dark);
        }
        
        .number-red {
            color: #dc3545;
        }
        
        .number-black {
            color: #333;
        }
        
        .number-green {
            color: #28a745;
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
        }
        
        .game-status {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .status-waiting {
            background: #ffc107;
            color: #000;
        }
        
        .status-betting {
            background: #28a745;
            color: #fff;
        }
        
        .status-spinning {
            background: #17a2b8;
            color: #fff;
        }
        
        .players-list {
            margin-top: 15px;
        }
        
        .player-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .betting-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .betting-table {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
        }
        
        .numbers-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 5px;
            margin-bottom: 20px;
        }
        
        .number-btn {
            aspect-ratio: 1;
            border: 2px solid #333;
            border-radius: 8px;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .number-btn.red {
            background: #dc3545;
            color: white;
        }
        
        .number-btn.black {
            background: #333;
            color: white;
        }
        
        .number-btn.green {
            background: #28a745;
            color: white;
        }
        
        .number-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
        }
        
        .number-btn.selected {
            border-color: #ffd700;
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.5);
        }
        
        .bet-chip {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ffd700;
            color: #000;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .outside-bets {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 20px;
        }
        
        .outside-bet {
            padding: 15px;
            background: var(--bg-dark);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .outside-bet:hover {
            border-color: var(--accent-color);
            background: rgba(255, 193, 7, 0.1);
        }
        
        .outside-bet.selected {
            border-color: #ffd700;
            background: rgba(255, 215, 0, 0.2);
        }
        
        .bet-controls {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
        }
        
        .bet-amount-section {
            margin-bottom: 20px;
        }
        
        .bet-amount-input {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            background: var(--bg-dark);
            color: var(--text-color);
            font-size: 16px;
            text-align: center;
        }
        
        .quick-amounts {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        
        .quick-amount {
            padding: 8px;
            background: var(--bg-dark);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .quick-amount:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .current-bets {
            background: var(--bg-dark);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .bet-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .bet-item:last-child {
            border-bottom: none;
        }
        
        .stats-section {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
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
        
        .history-number {
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 15px;
            color: white;
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
        
        @media (max-width: 1200px) {
            .game-area {
                grid-template-columns: 1fr;
            }
            
            .betting-section {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .stats-section {
                grid-template-columns: 1fr;
            }
            
            .numbers-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .outside-bets {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="roulette-container">
        <!-- En-tête -->
        <div class="roulette-header">
            <h1>🎰 Roulette Française</h1>
            <p>Tentez votre chance sur la roulette automatique !</p>
        </div>
        
        <!-- Zone de jeu -->
        <div class="game-area">
            <!-- Roue de roulette -->
            <div class="roulette-wheel-section">
                <h3>🎯 Roue de la Fortune</h3>
                
                <div class="wheel-container">
                    <div class="wheel-pointer"></div>
                    <div class="roulette-wheel" id="roulette-wheel">
                        <div class="wheel-center" id="wheel-center">🎰</div>
                    </div>
                </div>
                
                <div class="winning-number" id="winning-number" style="display: none;">
                    Numéro gagnant: <span id="winning-number-value"></span>
                </div>
                
                <div style="margin-top: 20px;">
                    <button class="btn btn-primary" id="spin-btn" onclick="spinWheel()" disabled>
                        🎲 Lancer la roulette
                    </button>
                </div>
            </div>
            
            <!-- Informations de la partie -->
            <div class="game-info">
                <h3>📊 Partie Actuelle</h3>
                
                <div class="current-game" id="current-game">
                    <div class="game-status status-waiting" id="game-status">
                        En attente de joueurs
                    </div>
                    
                    <div>
                        <strong>Partie #:</strong> <span id="game-id">--</span><br>
                        <strong>Joueurs:</strong> <span id="players-count">0</span>/6<br>
                        <strong>Mises totales:</strong> <span id="total-bets">0</span> coins
                    </div>
                    
                    <div class="players-list" id="players-list">
                        <!-- Liste des joueurs sera remplie par JS -->
                    </div>
                </div>
                
                <div>
                    <h4>💰 Vos Coins</h4>
                    <div style="font-size: 1.5em; font-weight: bold; color: var(--accent-color);">
                        <span id="user-coins"><?= number_format($userInfo['coins']) ?></span> coins
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Section de paris -->
        <div class="betting-section">
            <!-- Table de paris -->
            <div class="betting-table">
                <h3>🎯 Table de Paris</h3>
                
                <!-- Numéros individuels -->
                <div class="numbers-grid" id="numbers-grid">
                    <!-- Les numéros seront générés par JS -->
                </div>
                
                <!-- Paris extérieurs -->
                <div class="outside-bets">
                    <div class="outside-bet" data-bet="rouge" onclick="selectOutsideBet('rouge')">
                        <strong>Rouge</strong><br>
                        <small>Paiement 1:1</small>
                    </div>
                    <div class="outside-bet" data-bet="noir" onclick="selectOutsideBet('noir')">
                        <strong>Noir</strong><br>
                        <small>Paiement 1:1</small>
                    </div>
                    <div class="outside-bet" data-bet="pair" onclick="selectOutsideBet('pair')">
                        <strong>Pair</strong><br>
                        <small>Paiement 1:1</small>
                    </div>
                    <div class="outside-bet" data-bet="impair" onclick="selectOutsideBet('impair')">
                        <strong>Impair</strong><br>
                        <small>Paiement 1:1</small>
                    </div>
                    <div class="outside-bet" data-bet="manque" onclick="selectOutsideBet('manque')">
                        <strong>1-18</strong><br>
                        <small>Paiement 1:1</small>
                    </div>
                    <div class="outside-bet" data-bet="passe" onclick="selectOutsideBet('passe')">
                        <strong>19-36</strong><br>
                        <small>Paiement 1:1</small>
                    </div>
                </div>
            </div>
            
            <!-- Contrôles de paris -->
            <div class="bet-controls">
                <h3>💸 Contrôles de Paris</h3>
                
                <div class="bet-amount-section">
                    <label for="bet-amount">Montant du pari:</label>
                    <input type="number" id="bet-amount" class="bet-amount-input" min="1" max="<?= $userInfo['coins'] ?>" value="10">
                    
                    <div class="quick-amounts">
                        <div class="quick-amount" onclick="setBetAmount(10)">10</div>
                        <div class="quick-amount" onclick="setBetAmount(50)">50</div>
                        <div class="quick-amount" onclick="setBetAmount(100)">100</div>
                        <div class="quick-amount" onclick="setBetAmount(500)">500</div>
                        <div class="quick-amount" onclick="setBetAmount(1000)">1000</div>
                        <div class="quick-amount" onclick="setBetAmount(<?= $userInfo['coins'] ?>)">Max</div>
                    </div>
                </div>
                
                <div>
                    <h4>📋 Vos Paris</h4>
                    <div class="current-bets" id="current-bets">
                        <p style="text-align: center; color: var(--text-secondary);">Aucun pari placé</p>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button class="btn btn-danger" onclick="clearBets()">🗑️ Effacer</button>
                        <button class="btn btn-success" onclick="confirmBets()" id="confirm-bets-btn" disabled>
                            ✅ Confirmer
                        </button>
                    </div>
                </div>
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
                                <div class="history-number <?= $game['winning_number'] == 0 ? 'green' : (in_array($game['winning_number'], [1,3,5,7,9,12,14,16,18,19,21,23,25,27,30,32,34,36]) ? 'red' : 'black') ?>" style="background: <?= $game['winning_number'] == 0 ? '#28a745' : (in_array($game['winning_number'], [1,3,5,7,9,12,14,16,18,19,21,23,25,27,30,32,34,36]) ? '#dc3545' : '#333') ?>">
                                    <?= $game['winning_number'] ?>
                                </div>
                                <small><?= date('d/m H:i', strtotime($game['created_at'])) ?></small>
                            </div>
                            <div>
                                <div>Misé: <?= number_format($game['bet_amount']) ?></div>
                                <div class="history-result <?= $game['winnings'] > 0 ? 'result-win' : 'result-lose' ?>">
                                    <?= $game['winnings'] > 0 ? '+' . number_format($game['winnings']) : '-' . number_format($game['bet_amount']) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Classement -->
            <div class="stats-card">
                <div class="stats-title">🏆 Top Joueurs</div>
                
                <?php foreach ($leaderboard as $index => $player): ?>
                    <div class="stat-item">
                        <span>
                            <?= $index + 1 ?>. <?= htmlspecialchars($player['username']) ?>
                            <?php if ($player['user_id'] == $_SESSION['user_id']): ?>
                                <span style="color: var(--accent-color);">(Vous)</span>
                            <?php endif; ?>
                        </span>
                        <span class="<?= $player['net_profit'] >= 0 ? 'result-win' : 'result-lose' ?>">
                            <?= ($player['net_profit'] >= 0 ? '+' : '') . number_format($player['net_profit']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Navigation -->
        <div style="text-align: center; margin-top: 30px;">
            <a href="dashboard.php" class="btn btn-secondary">← Retour au tableau de bord</a>
        </div>
    </div>
    
    <script>
        // Variables globales
        let currentGame = null;
        let userBets = {};
        let selectedBet = null;
        let userCoins = <?= $userInfo['coins'] ?>;
        let isSpinning = false;
        
        // Configuration de la roulette
        const rouletteNumbers = [
            {number: 0, color: 'green'},
            {number: 32, color: 'red'}, {number: 15, color: 'black'}, {number: 19, color: 'red'},
            {number: 4, color: 'black'}, {number: 21, color: 'red'}, {number: 2, color: 'black'},
            {number: 25, color: 'red'}, {number: 17, color: 'black'}, {number: 34, color: 'red'},
            {number: 6, color: 'black'}, {number: 27, color: 'red'}, {number: 13, color: 'black'},
            {number: 36, color: 'red'}, {number: 11, color: 'black'}, {number: 30, color: 'red'},
            {number: 8, color: 'black'}, {number: 23, color: 'red'}, {number: 10, color: 'black'},
            {number: 5, color: 'red'}, {number: 24, color: 'black'}, {number: 16, color: 'red'},
            {number: 33, color: 'black'}, {number: 1, color: 'red'}, {number: 20, color: 'black'},
            {number: 14, color: 'red'}, {number: 31, color: 'black'}, {number: 9, color: 'red'},
            {number: 22, color: 'black'}, {number: 18, color: 'red'}, {number: 29, color: 'black'},
            {number: 7, color: 'red'}, {number: 28, color: 'black'}, {number: 12, color: 'red'},
            {number: 35, color: 'black'}, {number: 3, color: 'red'}, {number: 26, color: 'black'}
        ];
        
        const redNumbers = [1,3,5,7,9,12,14,16,18,19,21,23,25,27,30,32,34,36];
        
        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            generateNumbersGrid();
            loadCurrentGame();
            
            // Actualisation automatique
            setInterval(loadCurrentGame, 5000);
        });
        
        // Générer la grille de numéros
        function generateNumbersGrid() {
            const grid = document.getElementById('numbers-grid');
            
            // Ajouter le 0 en premier
            const zeroBtn = document.createElement('div');
            zeroBtn.className = 'number-btn green';
            zeroBtn.textContent = '0';
            zeroBtn.onclick = () => selectNumber(0);
            grid.appendChild(zeroBtn);
            
            // Ajouter les numéros 1-36
            for (let i = 1; i <= 36; i++) {
                const btn = document.createElement('div');
                btn.className = `number-btn ${redNumbers.includes(i) ? 'red' : 'black'}`;
                btn.textContent = i;
                btn.onclick = () => selectNumber(i);
                grid.appendChild(btn);
            }
        }
        
        // Charger la partie actuelle
        function loadCurrentGame() {
            fetch('api/roulette.php?action=get_current_game')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentGame = data.game;
                    updateGameDisplay();
                } else {
                    // Créer une nouvelle partie
                    createNewGame();
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
        }
        
        // Créer une nouvelle partie
        function createNewGame() {
            fetch('api/roulette.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'create_game'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentGame = data.game;
                    updateGameDisplay();
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
        }
        
        // Mettre à jour l'affichage de la partie
        function updateGameDisplay() {
            if (!currentGame) return;
            
            document.getElementById('game-id').textContent = currentGame.id;
            document.getElementById('players-count').textContent = currentGame.players_count || 0;
            document.getElementById('total-bets').textContent = (currentGame.total_bets || 0).toLocaleString();
            
            const statusElement = document.getElementById('game-status');
            statusElement.className = `game-status status-${currentGame.game_status}`;
            
            switch (currentGame.game_status) {
                case 'waiting':
                    statusElement.textContent = 'En attente de joueurs';
                    break;
                case 'betting':
                    statusElement.textContent = 'Paris ouverts';
                    break;
                case 'spinning':
                    statusElement.textContent = 'Roulette en cours';
                    break;
                case 'finished':
                    statusElement.textContent = 'Partie terminée';
                    break;
            }
            
            // Mettre à jour le bouton de lancement
            const spinBtn = document.getElementById('spin-btn');
            spinBtn.disabled = currentGame.game_status !== 'betting' || Object.keys(userBets).length === 0;
            
            // Afficher le numéro gagnant si la partie est terminée
            if (currentGame.game_status === 'finished' && currentGame.winning_number !== null) {
                showWinningNumber(currentGame.winning_number);
            }
        }
        
        // Sélectionner un numéro
        function selectNumber(number) {
            if (currentGame && currentGame.game_status !== 'betting') {
                alert('Les paris sont fermés pour cette partie');
                return;
            }
            
            const betAmount = parseInt(document.getElementById('bet-amount').value);
            if (!betAmount || betAmount <= 0) {
                alert('Veuillez entrer un montant de pari valide');
                return;
            }
            
            if (betAmount > userCoins) {
                alert('Vous n\'avez pas assez de coins');
                return;
            }
            
            // Ajouter le pari
            const betKey = `number_${number}`;
            if (userBets[betKey]) {
                userBets[betKey] += betAmount;
            } else {
                userBets[betKey] = betAmount;
            }
            
            updateBetsDisplay();
            updateNumberDisplay(number);
        }
        
        // Sélectionner un pari extérieur
        function selectOutsideBet(betType) {
            if (currentGame && currentGame.game_status !== 'betting') {
                alert('Les paris sont fermés pour cette partie');
                return;
            }
            
            const betAmount = parseInt(document.getElementById('bet-amount').value);
            if (!betAmount || betAmount <= 0) {
                alert('Veuillez entrer un montant de pari valide');
                return;
            }
            
            if (betAmount > userCoins) {
                alert('Vous n\'avez pas assez de coins');
                return;
            }
            
            // Ajouter le pari
            if (userBets[betType]) {
                userBets[betType] += betAmount;
            } else {
                userBets[betType] = betAmount;
            }
            
            updateBetsDisplay();
            updateOutsideBetDisplay(betType);
        }
        
        // Mettre à jour l'affichage des paris
        function updateBetsDisplay() {
            const betsContainer = document.getElementById('current-bets');
            
            if (Object.keys(userBets).length === 0) {
                betsContainer.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">Aucun pari placé</p>';
                document.getElementById('confirm-bets-btn').disabled = true;
                return;
            }
            
            let html = '';
            let totalBet = 0;
            
            for (const [betType, amount] of Object.entries(userBets)) {
                totalBet += amount;
                const betName = betType.startsWith('number_') ? 
                    `Numéro ${betType.split('_')[1]}` : 
                    betType.charAt(0).toUpperCase() + betType.slice(1);
                
                html += `
                    <div class="bet-item">
                        <span>${betName}</span>
                        <span>${amount.toLocaleString()} coins</span>
                    </div>
                `;
            }
            
            html += `
                <div class="bet-item" style="border-top: 2px solid var(--primary-color); margin-top: 10px; padding-top: 10px;">
                    <strong>Total:</strong>
                    <strong>${totalBet.toLocaleString()} coins</strong>
                </div>
            `;
            
            betsContainer.innerHTML = html;
            document.getElementById('confirm-bets-btn').disabled = false;
        }
        
        // Mettre à jour l'affichage d'un numéro
        function updateNumberDisplay(number) {
            const numberBtns = document.querySelectorAll('.number-btn');
            numberBtns.forEach(btn => {
                if (parseInt(btn.textContent) === number) {
                    btn.classList.add('selected');
                    
                    // Ajouter ou mettre à jour le chip
                    let chip = btn.querySelector('.bet-chip');
                    if (!chip) {
                        chip = document.createElement('div');
                        chip.className = 'bet-chip';
                        btn.appendChild(chip);
                    }
                    chip.textContent = userBets[`number_${number}`] || '';
                }
            });
        }
        
        // Mettre à jour l'affichage d'un pari extérieur
        function updateOutsideBetDisplay(betType) {
            const betElement = document.querySelector(`[data-bet="${betType}"]`);
            if (betElement) {
                betElement.classList.add('selected');
                
                // Ajouter ou mettre à jour le chip
                let chip = betElement.querySelector('.bet-chip');
                if (!chip) {
                    chip = document.createElement('div');
                    chip.className = 'bet-chip';
                    betElement.appendChild(chip);
                }
                chip.textContent = userBets[betType] || '';
            }
        }
        
        // Définir le montant du pari
        function setBetAmount(amount) {
            document.getElementById('bet-amount').value = Math.min(amount, userCoins);
        }
        
        // Effacer tous les paris
        function clearBets() {
            userBets = {};
            updateBetsDisplay();
            
            // Retirer les sélections visuelles
            document.querySelectorAll('.selected').forEach(el => {
                el.classList.remove('selected');
                const chip = el.querySelector('.bet-chip');
                if (chip) chip.remove();
            });
        }
        
        // Confirmer les paris
        function confirmBets() {
            if (Object.keys(userBets).length === 0) {
                alert('Aucun pari à confirmer');
                return;
            }
            
            if (!currentGame) {
                alert('Aucune partie active');
                return;
            }
            
            fetch('api/roulette.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'place_bet',
                    game_id: currentGame.id,
                    bets: userBets
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    userCoins = data.new_balance;
                    document.getElementById('user-coins').textContent = userCoins.toLocaleString();
                    
                    alert('Paris confirmés !');
                    clearBets();
                    loadCurrentGame();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur de connexion');
            });
        }
        
        // Lancer la roulette
        function spinWheel() {
            if (isSpinning) return;
            
            fetch('api/roulette.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'spin_roulette',
                    game_id: currentGame.id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    animateWheel(data.winning_number);
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur de connexion');
            });
        }
        
        // Animer la roue
        function animateWheel(winningNumber) {
            isSpinning = true;
            const wheel = document.getElementById('roulette-wheel');
            
            // Calculer l'angle pour le numéro gagnant
            const numberIndex = rouletteNumbers.findIndex(n => n.number === winningNumber);
            const anglePerNumber = 360 / rouletteNumbers.length;
            const targetAngle = (numberIndex * anglePerNumber) + (Math.random() * anglePerNumber);
            const totalRotation = 1800 + targetAngle; // 5 tours + position finale
            
            wheel.style.transform = `rotate(${totalRotation}deg)`;
            
            setTimeout(() => {
                showWinningNumber(winningNumber);
                isSpinning = false;
                loadCurrentGame();
                
                // Recharger les coins de l'utilisateur
                fetch('api/user.php?action=get_balance')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        userCoins = data.balance;
                        document.getElementById('user-coins').textContent = userCoins.toLocaleString();
                    }
                });
            }, 3000);
        }
        
        // Afficher le numéro gagnant
        function showWinningNumber(number) {
            const winningNumberEl = document.getElementById('winning-number');
            const winningNumberValue = document.getElementById('winning-number-value');
            
            winningNumberValue.textContent = number;
            winningNumberValue.className = number === 0 ? 'number-green' : 
                (redNumbers.includes(number) ? 'number-red' : 'number-black');
            
            winningNumberEl.style.display = 'block';
            
            // Cacher après 10 secondes
            setTimeout(() => {
                winningNumberEl.style.display = 'none';
            }, 10000);
        }
    </script>
</body>
</html>