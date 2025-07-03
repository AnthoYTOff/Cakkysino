<?php
// CakkySino - Page de Gains Passifs

require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'classes/PassiveEarnings.php';

session_start();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user = new User();
$userInfo = $user->getUserInfo($_SESSION['user_id']);
$passiveEarnings = new PassiveEarnings();

// Récupérer les statistiques
$stats = $passiveEarnings->getUserStats($_SESSION['user_id']);
$history = $passiveEarnings->getUserHistory($_SESSION['user_id'], 10);
$leaderboard = $passiveEarnings->getLeaderboard(10);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gains Passifs - CakkySino</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .passive-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .passive-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 30px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border-radius: 15px;
            color: white;
        }
        
        .passive-header h1 {
            margin: 0 0 10px 0;
            font-size: 2.5em;
        }
        
        .passive-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 1.1em;
        }
        
        .main-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .earning-zone {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            border: 3px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        .earning-zone.active {
            border-color: var(--accent-color);
            box-shadow: 0 0 30px rgba(255, 193, 7, 0.3);
            background: linear-gradient(135deg, var(--card-bg), rgba(255, 193, 7, 0.1));
        }
        
        .earning-display {
            font-size: 3em;
            font-weight: bold;
            color: var(--accent-color);
            margin: 20px 0;
            text-shadow: 0 0 10px rgba(255, 193, 7, 0.5);
        }
        
        .earning-rate {
            font-size: 1.2em;
            color: var(--text-secondary);
            margin-bottom: 20px;
        }
        
        .earning-controls {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .activity-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
            padding: 15px;
            background: var(--bg-dark);
            border-radius: 10px;
        }
        
        .activity-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #dc3545;
            transition: all 0.3s ease;
        }
        
        .activity-dot.active {
            background: #28a745;
            box-shadow: 0 0 10px #28a745;
        }
        
        .stats-panel {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .stat-item:last-child {
            border-bottom: none;
        }
        
        .stat-label {
            color: var(--text-secondary);
        }
        
        .stat-value {
            font-weight: bold;
            color: var(--accent-color);
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--bg-dark);
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            transition: width 0.3s ease;
        }
        
        .history-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .section-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
        }
        
        .section-title {
            font-size: 1.3em;
            font-weight: bold;
            margin-bottom: 20px;
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
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
        
        .history-time {
            color: var(--text-secondary);
            font-size: 0.9em;
        }
        
        .history-amount {
            font-weight: bold;
            color: var(--accent-color);
        }
        
        .leaderboard-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .leaderboard-item:last-child {
            border-bottom: none;
        }
        
        .leaderboard-rank {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
        }
        
        .leaderboard-rank.gold {
            background: #ffd700;
            color: #000;
        }
        
        .leaderboard-rank.silver {
            background: #c0c0c0;
            color: #000;
        }
        
        .leaderboard-rank.bronze {
            background: #cd7f32;
            color: white;
        }
        
        .leaderboard-info {
            flex: 1;
        }
        
        .leaderboard-name {
            font-weight: bold;
        }
        
        .leaderboard-amount {
            color: var(--accent-color);
            font-weight: bold;
        }
        
        .tips-section {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
            margin-top: 30px;
            border-left: 5px solid var(--accent-color);
        }
        
        .tips-title {
            color: var(--accent-color);
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .tip-item {
            margin-bottom: 10px;
            padding-left: 20px;
            position: relative;
        }
        
        .tip-item::before {
            content: '💡';
            position: absolute;
            left: 0;
        }
        
        @media (max-width: 768px) {
            .main-grid,
            .history-section {
                grid-template-columns: 1fr;
            }
            
            .earning-display {
                font-size: 2em;
            }
            
            .earning-controls {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="passive-container">
        <!-- En-tête -->
        <div class="passive-header">
            <h1>💰 Gains Passifs</h1>
            <p>Gagnez des coins en restant actif sur cette page !</p>
        </div>
        
        <!-- Zone principale -->
        <div class="main-grid">
            <!-- Zone de gains -->
            <div class="earning-zone" id="earning-zone">
                <h2>🎯 Zone de Gains</h2>
                
                <div class="earning-display" id="coins-display">
                    <?= number_format($userInfo['coins']) ?>
                </div>
                
                <div class="earning-rate">
                    +1 coin toutes les 10 secondes
                </div>
                
                <div class="activity-indicator">
                    <div class="activity-dot" id="activity-dot"></div>
                    <span id="activity-status">Inactif</span>
                </div>
                
                <div class="earning-controls">
                    <button class="btn btn-primary" id="start-earning" onclick="startEarning()">
                        🚀 Commencer à gagner
                    </button>
                    <button class="btn btn-danger" id="stop-earning" onclick="stopEarning()" style="display: none;">
                        ⏹️ Arrêter
                    </button>
                </div>
                
                <!-- Barre de progression -->
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill" style="width: 0%;"></div>
                </div>
                <div style="text-align: center; margin-top: 10px; color: var(--text-secondary);">
                    <span id="progress-text">Prochain gain dans: --</span>
                </div>
            </div>
            
            <!-- Panneau de statistiques -->
            <div class="stats-panel">
                <h3>📊 Vos Statistiques</h3>
                
                <div class="stat-item">
                    <span class="stat-label">Coins actuels:</span>
                    <span class="stat-value" id="current-coins"><?= number_format($userInfo['coins']) ?></span>
                </div>
                
                <div class="stat-item">
                    <span class="stat-label">Total gagné:</span>
                    <span class="stat-value"><?= number_format($stats['total_earned']) ?></span>
                </div>
                
                <div class="stat-item">
                    <span class="stat-label">Sessions:</span>
                    <span class="stat-value"><?= number_format($stats['total_sessions']) ?></span>
                </div>
                
                <div class="stat-item">
                    <span class="stat-label">Temps total:</span>
                    <span class="stat-value"><?= gmdate('H:i:s', $stats['total_time']) ?></span>
                </div>
                
                <div class="stat-item">
                    <span class="stat-label">Moyenne/heure:</span>
                    <span class="stat-value">
                        <?= $stats['total_time'] > 0 ? number_format($stats['total_earned'] / ($stats['total_time'] / 3600), 1) : '0' ?>
                    </span>
                </div>
                
                <div class="stat-item">
                    <span class="stat-label">Dernière session:</span>
                    <span class="stat-value">
                        <?= $stats['last_session'] ? date('d/m H:i', strtotime($stats['last_session'])) : 'Jamais' ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Historique et classement -->
        <div class="history-section">
            <!-- Historique -->
            <div class="section-card">
                <div class="section-title">📈 Historique Récent</div>
                
                <?php if (empty($history)): ?>
                    <p style="text-align: center; color: var(--text-secondary); padding: 20px;">
                        Aucun gain enregistré
                    </p>
                <?php else: ?>
                    <?php foreach ($history as $entry): ?>
                        <div class="history-item">
                            <div>
                                <div class="history-time">
                                    <?= date('d/m/Y H:i', strtotime($entry['earned_at'])) ?>
                                </div>
                                <div style="font-size: 0.9em; color: var(--text-secondary);">
                                    Session: <?= gmdate('i:s', $entry['session_duration']) ?>
                                </div>
                            </div>
                            <div class="history-amount">
                                +<?= number_format($entry['amount']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Classement -->
            <div class="section-card">
                <div class="section-title">🏆 Top Gagnants</div>
                
                <?php foreach ($leaderboard as $index => $player): ?>
                    <div class="leaderboard-item">
                        <div class="leaderboard-rank <?= $index === 0 ? 'gold' : ($index === 1 ? 'silver' : ($index === 2 ? 'bronze' : '')) ?>">
                            <?= $index + 1 ?>
                        </div>
                        <div class="leaderboard-info">
                            <div class="leaderboard-name">
                                <?= htmlspecialchars($player['username']) ?>
                                <?php if ($player['user_id'] == $_SESSION['user_id']): ?>
                                    <span style="color: var(--accent-color);">(Vous)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="leaderboard-amount">
                            <?= number_format($player['total_earned']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Conseils -->
        <div class="tips-section">
            <div class="tips-title">💡 Conseils pour maximiser vos gains</div>
            <div class="tip-item">Restez actif en bougeant votre souris ou en appuyant sur des touches</div>
            <div class="tip-item">Ne changez pas d'onglet pendant que vous gagnez des coins</div>
            <div class="tip-item">Plus vous restez longtemps, plus vous accumulez de coins</div>
            <div class="tip-item">Utilisez vos coins pour jouer à la roulette ou au blackjack</div>
        </div>
        
        <!-- Navigation -->
        <div style="text-align: center; margin-top: 30px;">
            <a href="dashboard.php" class="btn btn-secondary">← Retour au tableau de bord</a>
        </div>
    </div>
    
    <script>
        let isEarning = false;
        let earningInterval = null;
        let progressInterval = null;
        let activityTimeout = null;
        let lastActivity = Date.now();
        let nextEarnTime = 0;
        let currentCoins = <?= $userInfo['coins'] ?>;
        
        const EARNING_INTERVAL = 10000; // 10 secondes
        const ACTIVITY_TIMEOUT = 30000; // 30 secondes d'inactivité max
        
        // Éléments DOM
        const earningZone = document.getElementById('earning-zone');
        const activityDot = document.getElementById('activity-dot');
        const activityStatus = document.getElementById('activity-status');
        const coinsDisplay = document.getElementById('coins-display');
        const currentCoinsSpan = document.getElementById('current-coins');
        const progressFill = document.getElementById('progress-fill');
        const progressText = document.getElementById('progress-text');
        const startBtn = document.getElementById('start-earning');
        const stopBtn = document.getElementById('stop-earning');
        
        // Démarrer les gains
        function startEarning() {
            if (isEarning) return;
            
            fetch('api/passive_earnings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'start_session'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    isEarning = true;
                    earningZone.classList.add('active');
                    startBtn.style.display = 'none';
                    stopBtn.style.display = 'inline-block';
                    
                    nextEarnTime = Date.now() + EARNING_INTERVAL;
                    startProgressTimer();
                    startEarningTimer();
                    
                    updateActivityStatus(true);
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur de connexion');
            });
        }
        
        // Arrêter les gains
        function stopEarning() {
            if (!isEarning) return;
            
            fetch('api/passive_earnings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'stop_session'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    stopEarningSession();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                stopEarningSession();
            });
        }
        
        // Arrêter la session localement
        function stopEarningSession() {
            isEarning = false;
            earningZone.classList.remove('active');
            startBtn.style.display = 'inline-block';
            stopBtn.style.display = 'none';
            
            if (earningInterval) {
                clearInterval(earningInterval);
                earningInterval = null;
            }
            
            if (progressInterval) {
                clearInterval(progressInterval);
                progressInterval = null;
            }
            
            progressFill.style.width = '0%';
            progressText.textContent = 'Prochain gain dans: --';
            updateActivityStatus(false);
        }
        
        // Timer de gains
        function startEarningTimer() {
            earningInterval = setInterval(() => {
                if (isEarning && isActive()) {
                    earnCoins();
                    nextEarnTime = Date.now() + EARNING_INTERVAL;
                }
            }, EARNING_INTERVAL);
        }
        
        // Timer de progression
        function startProgressTimer() {
            progressInterval = setInterval(() => {
                if (isEarning) {
                    const now = Date.now();
                    const timeLeft = Math.max(0, nextEarnTime - now);
                    const progress = Math.max(0, 100 - (timeLeft / EARNING_INTERVAL * 100));
                    
                    progressFill.style.width = progress + '%';
                    
                    if (timeLeft > 0) {
                        const seconds = Math.ceil(timeLeft / 1000);
                        progressText.textContent = `Prochain gain dans: ${seconds}s`;
                    } else {
                        progressText.textContent = 'Gain en cours...';
                    }
                }
            }, 100);
        }
        
        // Gagner des coins
        function earnCoins() {
            fetch('api/passive_earnings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'earn_coins'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentCoins = data.new_balance;
                    updateCoinsDisplay();
                    
                    // Animation de gain
                    coinsDisplay.style.transform = 'scale(1.1)';
                    setTimeout(() => {
                        coinsDisplay.style.transform = 'scale(1)';
                    }, 200);
                } else if (data.message === 'Session inactive') {
                    stopEarningSession();
                    alert('Session arrêtée pour inactivité');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
        }
        
        // Mettre à jour l'affichage des coins
        function updateCoinsDisplay() {
            coinsDisplay.textContent = currentCoins.toLocaleString();
            currentCoinsSpan.textContent = currentCoins.toLocaleString();
        }
        
        // Vérifier l'activité
        function isActive() {
            return (Date.now() - lastActivity) < ACTIVITY_TIMEOUT;
        }
        
        // Mettre à jour le statut d'activité
        function updateActivityStatus(active) {
            if (active) {
                activityDot.classList.add('active');
                activityStatus.textContent = 'Actif';
            } else {
                activityDot.classList.remove('active');
                activityStatus.textContent = 'Inactif';
            }
        }
        
        // Mettre à jour l'activité
        function updateActivity() {
            lastActivity = Date.now();
            
            if (isEarning) {
                updateActivityStatus(true);
                
                // Envoyer l'activité au serveur
                fetch('api/passive_earnings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'update_activity'
                    })
                })
                .catch(error => {
                    console.error('Erreur:', error);
                });
                
                // Vérifier l'inactivité
                if (activityTimeout) {
                    clearTimeout(activityTimeout);
                }
                
                activityTimeout = setTimeout(() => {
                    if (isEarning) {
                        updateActivityStatus(false);
                    }
                }, ACTIVITY_TIMEOUT);
            }
        }
        
        // Événements d'activité
        document.addEventListener('mousemove', updateActivity);
        document.addEventListener('keypress', updateActivity);
        document.addEventListener('click', updateActivity);
        document.addEventListener('scroll', updateActivity);
        
        // Gestion de la visibilité de la page
        document.addEventListener('visibilitychange', () => {
            if (document.hidden && isEarning) {
                // Page cachée, arrêter les gains
                stopEarning();
                alert('Gains arrêtés car vous avez changé d\'onglet');
            }
        });
        
        // Initialisation
        updateActivity();
        
        // Vérifier le statut au chargement
        fetch('api/passive_earnings.php?action=get_status')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.is_active) {
                // Session déjà active, la reprendre
                isEarning = true;
                earningZone.classList.add('active');
                startBtn.style.display = 'none';
                stopBtn.style.display = 'inline-block';
                
                nextEarnTime = Date.now() + EARNING_INTERVAL;
                startProgressTimer();
                startEarningTimer();
                updateActivityStatus(true);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
        });
    </script>
</body>
</html>