// CakkySino - JavaScript Dashboard

// Variables globales
let earningInterval = null;
let earningActive = false;
let lastActivity = Date.now();
let userCoins = 0;

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Récupérer les coins de l'utilisateur
    const coinsElement = document.getElementById('user-coins');
    if (coinsElement) {
        userCoins = parseInt(coinsElement.textContent.replace(/,/g, ''));
    }
    
    // Détecter l'activité de l'utilisateur
    setupActivityDetection();
    
    // Mettre à jour les coins périodiquement
    setInterval(updateUserCoins, 5000);
});

// Gestion des sections
function showSection(sectionName) {
    // Cacher toutes les sections
    const sections = document.querySelectorAll('.content-section');
    sections.forEach(section => {
        section.classList.remove('active');
    });
    
    // Désactiver tous les éléments de menu
    const menuItems = document.querySelectorAll('.menu-item');
    menuItems.forEach(item => {
        item.classList.remove('active');
    });
    
    // Afficher la section demandée
    const targetSection = document.getElementById(sectionName + '-section');
    if (targetSection) {
        targetSection.classList.add('active');
    }
    
    // Activer l'élément de menu correspondant
    const menuItem = document.querySelector(`[onclick="showSection('${sectionName}')"]`);
    if (menuItem) {
        menuItem.classList.add('active');
    }
    
    // Actions spécifiques par section
    switch (sectionName) {
        case 'roulette':
            loadRouletteGame();
            break;
        case 'blackjack':
            loadBlackjackGames();
            break;
        case 'passive-earning':
            checkEarningStatus();
            break;
    }
}

// === GAINS PASSIFS ===

// Détecter l'activité de l'utilisateur
function setupActivityDetection() {
    const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
    
    events.forEach(event => {
        document.addEventListener(event, function() {
            lastActivity = Date.now();
            if (earningActive) {
                updateActivity();
            }
        }, true);
    });
}

// Démarrer les gains passifs
function startEarning() {
    fetch('api/passive_earnings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'start'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            earningActive = true;
            updateEarningDisplay();
            
            // Démarrer le timer
            earningInterval = setInterval(function() {
                calculateEarnings();
                checkActivity();
            }, 1000);
            
            showNotification('Gains passifs démarrés !', 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur lors du démarrage des gains', 'error');
    });
}

// Arrêter les gains passifs
function stopEarning() {
    fetch('api/passive_earnings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'stop'
        })
    })
    .then(response => response.json())
    .then(data => {
        earningActive = false;
        if (earningInterval) {
            clearInterval(earningInterval);
            earningInterval = null;
        }
        
        updateEarningDisplay();
        showNotification('Gains passifs arrêtés', 'info');
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}

// Mettre à jour l'activité
function updateActivity() {
    fetch('api/passive_earnings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update_activity'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success && data.afk) {
            // L'utilisateur était AFK trop longtemps
            stopEarning();
            showNotification('Session expirée due à l\'inactivité', 'warning');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}

// Calculer les gains
function calculateEarnings() {
    fetch('api/passive_earnings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'calculate'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.coins_earned > 0) {
                // Nouveaux coins gagnés
                userCoins = data.new_balance;
                updateUserCoinsDisplay();
                showNotification(`+${data.coins_earned} coins gagnés !`, 'success');
            }
            
            // Mettre à jour l'affichage
            updateEarningProgress(data);
        } else if (data.afk) {
            stopEarning();
            showNotification('Session expirée due à l\'inactivité', 'warning');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}

// Vérifier l'activité
function checkActivity() {
    const timeSinceActivity = Date.now() - lastActivity;
    if (timeSinceActivity > 60000) { // 60 secondes
        // Utilisateur inactif
        stopEarning();
        showNotification('Arrêt automatique - inactivité détectée', 'warning');
    }
}

// Vérifier le statut des gains
function checkEarningStatus() {
    fetch('api/passive_earnings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'status'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.active) {
            earningActive = true;
            updateEarningDisplay();
            
            // Redémarrer le timer
            if (!earningInterval) {
                earningInterval = setInterval(function() {
                    calculateEarnings();
                    checkActivity();
                }, 1000);
            }
        } else {
            earningActive = false;
            updateEarningDisplay();
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}

// Mettre à jour l'affichage des gains
function updateEarningDisplay() {
    const statusElement = document.getElementById('earning-status');
    if (!statusElement) return;
    
    if (earningActive) {
        statusElement.innerHTML = `
            <div class="status-active">
                <h3>🟢 Session active</h3>
                <p>Vous gagnez 1 coin toutes les 30 secondes</p>
                <div class="earning-timer" id="earning-timer">00:00</div>
                <div class="earning-progress">
                    <div class="earning-progress-bar" id="earning-progress-bar" style="width: 0%"></div>
                </div>
                <button class="btn btn-danger" onclick="stopEarning()">Arrêter</button>
                <p class="text-muted mt-20">Restez actif pour continuer à gagner des coins</p>
            </div>
        `;
    } else {
        statusElement.innerHTML = `
            <div class="status-inactive">
                <h3>⭕ Session inactive</h3>
                <p>Cliquez sur "Commencer" pour débuter vos gains passifs</p>
                <button class="btn btn-primary" onclick="startEarning()">Commencer à gagner</button>
            </div>
        `;
    }
}

// Mettre à jour la barre de progression
function updateEarningProgress(data) {
    const timerElement = document.getElementById('earning-timer');
    const progressBar = document.getElementById('earning-progress-bar');
    
    if (timerElement && data.time_elapsed) {
        const minutes = Math.floor(data.time_elapsed / 60);
        const seconds = data.time_elapsed % 60;
        timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }
    
    if (progressBar && data.next_earning_in) {
        const progress = ((30 - data.next_earning_in) / 30) * 100;
        progressBar.style.width = progress + '%';
    }
}

// === ROULETTE ===

// Charger le jeu de roulette
function loadRouletteGame() {
    const container = document.getElementById('roulette-game');
    if (!container) return;
    
    fetch('api/roulette.php?action=get_game')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayRouletteGame(data.game, data.bets, data.players);
        } else {
            container.innerHTML = '<p class="no-data">Erreur lors du chargement de la roulette</p>';
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        container.innerHTML = '<p class="no-data">Erreur de connexion</p>';
    });
}

// Afficher le jeu de roulette
function displayRouletteGame(game, bets, players) {
    const container = document.getElementById('roulette-game');
    
    let html = `
        <div class="game-container">
            <div class="game-header">
                <h3>Partie #${game.id}</h3>
                <div class="game-status status-${game.game_status}">
                    ${getGameStatusText(game.game_status)}
                </div>
            </div>
            
            <div class="roulette-wheel" id="roulette-wheel">
                <div class="roulette-pointer"></div>
            </div>
            
            <div class="game-info">
                <p><strong>Joueurs:</strong> ${players.length}/6</p>
                ${game.winning_number !== null ? `<p><strong>Numéro gagnant:</strong> ${game.winning_number} (${game.winning_color})</p>` : ''}
            </div>
    `;
    
    if (game.game_status === 'waiting' || game.game_status === 'betting') {
        html += `
            <div class="betting-section">
                <h4>Placer une mise</h4>
                <div class="betting-controls">
                    <input type="number" id="bet-amount" placeholder="Montant" min="1" max="${userCoins}" value="10">
                    <button class="btn btn-primary" onclick="placeBet()">Miser</button>
                </div>
                
                <div class="betting-area">
                    <div class="bet-option" data-type="red" data-value="red">Rouge (1:1)</div>
                    <div class="bet-option" data-type="black" data-value="black">Noir (1:1)</div>
                    <div class="bet-option" data-type="even" data-value="even">Pair (1:1)</div>
                    <div class="bet-option" data-type="odd" data-value="odd">Impair (1:1)</div>
                    <div class="bet-option" data-type="low" data-value="low">1-18 (1:1)</div>
                    <div class="bet-option" data-type="high" data-value="high">19-36 (1:1)</div>
                </div>
                
                <div class="number-betting">
                    <h5>Miser sur un numéro (35:1)</h5>
                    <div class="number-grid">
        `;
        
        for (let i = 0; i <= 36; i++) {
            const color = getNumberColor(i);
            html += `<div class="bet-option number-bet ${color}" data-type="number" data-value="${i}">${i}</div>`;
        }
        
        html += `
                    </div>
                </div>
            </div>
        `;
    }
    
    // Afficher les mises actuelles
    if (bets.length > 0) {
        html += `
            <div class="current-bets">
                <h4>Mises actuelles</h4>
                <div class="bets-list">
        `;
        
        bets.forEach(bet => {
            html += `
                <div class="bet-item">
                    <strong>${bet.username}</strong>: ${bet.bet_amount} coins sur ${bet.bet_type} ${bet.bet_value}
                    ${bet.won ? `<span class="win-indicator">+${bet.winnings} coins</span>` : ''}
                </div>
            `;
        });
        
        html += `
                </div>
            </div>
        `;
    }
    
    html += '</div>';
    
    container.innerHTML = html;
    
    // Ajouter les événements de clic pour les mises
    setupBettingEvents();
    
    // Si la roulette tourne, animer
    if (game.game_status === 'spinning') {
        animateRouletteWheel(game.winning_number);
    }
}

// Configuration des événements de mise
function setupBettingEvents() {
    const betOptions = document.querySelectorAll('.bet-option');
    betOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Désélectionner les autres options
            betOptions.forEach(opt => opt.classList.remove('selected'));
            // Sélectionner cette option
            this.classList.add('selected');
        });
    });
}

// Placer une mise à la roulette
function placeBet() {
    const selectedOption = document.querySelector('.bet-option.selected');
    const betAmount = document.getElementById('bet-amount').value;
    
    if (!selectedOption) {
        showNotification('Veuillez sélectionner une option de mise', 'warning');
        return;
    }
    
    if (!betAmount || betAmount < 1) {
        showNotification('Veuillez entrer un montant valide', 'warning');
        return;
    }
    
    if (parseInt(betAmount) > userCoins) {
        showNotification('Solde insuffisant', 'error');
        return;
    }
    
    const betType = selectedOption.dataset.type;
    const betValue = selectedOption.dataset.value;
    
    fetch('api/roulette.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'place_bet',
            bet_type: betType,
            bet_value: betValue,
            bet_amount: parseInt(betAmount)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            userCoins = data.new_balance;
            updateUserCoinsDisplay();
            showNotification('Mise placée avec succès !', 'success');
            
            // Recharger le jeu
            setTimeout(() => {
                loadRouletteGame();
            }, 1000);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur lors de la mise', 'error');
    });
}

// Animer la roulette
function animateRouletteWheel(winningNumber) {
    const wheel = document.getElementById('roulette-wheel');
    if (!wheel) return;
    
    // Calculer l'angle pour le numéro gagnant
    const angle = (winningNumber * 9.73) + (Math.random() * 360 * 3); // 3 tours + position
    
    wheel.style.transform = `rotate(${angle}deg)`;
    wheel.classList.add('spinning');
    
    // Après l'animation, afficher le résultat
    setTimeout(() => {
        wheel.classList.remove('spinning');
        loadRouletteGame(); // Recharger pour voir les résultats
    }, 3000);
}

// === BLACKJACK ===

// Charger les jeux de blackjack
function loadBlackjackGames() {
    const container = document.getElementById('blackjack-games');
    if (!container) return;
    
    fetch('api/blackjack.php?action=get_games')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayBlackjackGames(data.games);
        } else {
            container.innerHTML = '<p class="no-data">Erreur lors du chargement des tables</p>';
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        container.innerHTML = '<p class="no-data">Erreur de connexion</p>';
    });
}

// Afficher les jeux de blackjack
function displayBlackjackGames(games) {
    const container = document.getElementById('blackjack-games');
    
    let html = '<div class="blackjack-tables">';
    
    if (games.length === 0) {
        html += '<p class="no-data">Aucune table de blackjack active</p>';
    } else {
        games.forEach(game => {
            html += `
                <div class="game-container">
                    <div class="game-header">
                        <h3>Table #${game.id} - Croupier: ${game.dealer_name}</h3>
                        <div class="game-status status-${game.game_status}">
                            ${getGameStatusText(game.game_status)}
                        </div>
                    </div>
                    
                    <div class="game-info">
                        <p><strong>Joueurs:</strong> ${game.player_count}/4</p>
                    </div>
                    
                    ${game.game_status === 'waiting' ? `
                        <div class="join-game">
                            <input type="number" id="bet-amount-${game.id}" placeholder="Mise" min="1" max="${userCoins}" value="10">
                            <button class="btn btn-success" onclick="joinBlackjackGame(${game.id})">Rejoindre (${game.player_count}/4)</button>
                        </div>
                    ` : ''}
                </div>
            `;
        });
    }
    
    html += '</div>';
    container.innerHTML = html;
}

// Rejoindre une partie de blackjack
function joinBlackjackGame(gameId) {
    const betAmount = document.getElementById(`bet-amount-${gameId}`).value;
    
    if (!betAmount || betAmount < 1) {
        showNotification('Veuillez entrer un montant valide', 'warning');
        return;
    }
    
    if (parseInt(betAmount) > userCoins) {
        showNotification('Solde insuffisant', 'error');
        return;
    }
    
    fetch('api/blackjack.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'join_game',
            game_id: gameId,
            bet_amount: parseInt(betAmount)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            userCoins = data.new_balance;
            updateUserCoinsDisplay();
            showNotification('Vous avez rejoint la table !', 'success');
            
            // Recharger les jeux
            setTimeout(() => {
                loadBlackjackGames();
            }, 1000);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur lors de l\'inscription', 'error');
    });
}

// === UTILITAIRES ===

// Mettre à jour l'affichage des coins
function updateUserCoins() {
    fetch('api/user.php?action=get_coins')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            userCoins = data.coins;
            updateUserCoinsDisplay();
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}

// Mettre à jour l'affichage des coins dans l'interface
function updateUserCoinsDisplay() {
    const coinsElement = document.getElementById('user-coins');
    if (coinsElement) {
        coinsElement.textContent = userCoins.toLocaleString();
    }
}

// Obtenir le texte du statut de jeu
function getGameStatusText(status) {
    const statusTexts = {
        'waiting': 'En attente',
        'betting': 'Mises ouvertes',
        'dealing': 'Distribution',
        'playing': 'En cours',
        'spinning': 'Roulette en cours',
        'finished': 'Terminée'
    };
    return statusTexts[status] || status;
}

// Obtenir la couleur d'un numéro de roulette
function getNumberColor(number) {
    if (number === 0) return 'green';
    const redNumbers = [1, 3, 5, 7, 9, 12, 14, 16, 18, 19, 21, 23, 25, 27, 30, 32, 34, 36];
    return redNumbers.includes(number) ? 'red' : 'black';
}

// Afficher une notification
function showNotification(message, type = 'info') {
    // Créer l'élément de notification
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Styles inline pour la notification
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 1000;
        max-width: 300px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;
    
    // Couleurs selon le type
    const colors = {
        'success': '#28a745',
        'error': '#dc3545',
        'warning': '#ffc107',
        'info': '#17a2b8'
    };
    
    notification.style.backgroundColor = colors[type] || colors.info;
    
    // Ajouter au DOM
    document.body.appendChild(notification);
    
    // Animer l'entrée
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Supprimer après 3 secondes
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Formater les nombres
function formatNumber(number) {
    return number.toLocaleString();
}

// Formater le temps
function formatTime(seconds) {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
}