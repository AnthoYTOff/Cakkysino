<?php
/**
 * Page d'erreur personnalisée pour CakkySino
 * Gère les erreurs HTTP de manière élégante
 */

// Récupérer le code d'erreur
$error_code = isset($_GET['code']) ? (int)$_GET['code'] : 404;

// Définir les messages d'erreur
$error_messages = [
    400 => [
        'title' => 'Requête Incorrecte',
        'message' => 'La requête que vous avez envoyée n\'est pas valide.',
        'icon' => '⚠️'
    ],
    401 => [
        'title' => 'Non Autorisé',
        'message' => 'Vous devez vous connecter pour accéder à cette page.',
        'icon' => '🔒'
    ],
    403 => [
        'title' => 'Accès Interdit',
        'message' => 'Vous n\'avez pas les permissions nécessaires pour accéder à cette ressource.',
        'icon' => '🚫'
    ],
    404 => [
        'title' => 'Page Non Trouvée',
        'message' => 'La page que vous recherchez n\'existe pas ou a été déplacée.',
        'icon' => '🔍'
    ],
    500 => [
        'title' => 'Erreur Serveur',
        'message' => 'Une erreur interne du serveur s\'est produite. Veuillez réessayer plus tard.',
        'icon' => '⚙️'
    ]
];

// Récupérer les informations de l'erreur
$error_info = isset($error_messages[$error_code]) ? $error_messages[$error_code] : $error_messages[404];

// Définir le code de statut HTTP
http_response_code($error_code);

// Log de l'erreur
error_log("Erreur {$error_code} - URL: {$_SERVER['REQUEST_URI']} - IP: {$_SERVER['REMOTE_ADDR']} - User Agent: {$_SERVER['HTTP_USER_AGENT']}");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur <?php echo $error_code; ?> - CakkySino</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        
        .error-container {
            background: white;
            border-radius: 20px;
            padding: 50px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 90%;
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .error-icon {
            font-size: 5rem;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }
        
        .error-code {
            font-size: 4rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .error-title {
            font-size: 2rem;
            color: #333;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .error-message {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 40px;
            line-height: 1.6;
        }
        
        .error-actions {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-block;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            border: 2px solid #dee2e6;
        }
        
        .btn-secondary:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }
        
        .error-details {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #eee;
            font-size: 0.9rem;
            color: #999;
        }
        
        .casino-logo {
            font-size: 2rem;
            margin-bottom: 20px;
            color: #667eea;
        }
        
        @media (max-width: 768px) {
            .error-container {
                padding: 30px 20px;
            }
            
            .error-code {
                font-size: 3rem;
            }
            
            .error-title {
                font-size: 1.5rem;
            }
            
            .error-message {
                font-size: 1rem;
            }
            
            .error-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 250px;
            }
        }
        
        .floating-chips {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }
        
        .chip {
            position: absolute;
            width: 30px;
            height: 30px;
            background: linear-gradient(45deg, #ff6b6b, #feca57);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
            opacity: 0.1;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(100vh) rotate(0deg);
            }
            50% {
                transform: translateY(-100px) rotate(180deg);
            }
        }
    </style>
</head>
<body>
    <div class="floating-chips">
        <?php for ($i = 0; $i < 10; $i++): ?>
            <div class="chip" style="left: <?php echo rand(0, 100); ?>%; animation-delay: <?php echo rand(0, 6); ?>s;"></div>
        <?php endfor; ?>
    </div>
    
    <div class="error-container">
        <div class="casino-logo">🎰 CakkySino</div>
        
        <div class="error-icon"><?php echo $error_info['icon']; ?></div>
        
        <div class="error-code"><?php echo $error_code; ?></div>
        
        <h1 class="error-title"><?php echo $error_info['title']; ?></h1>
        
        <p class="error-message"><?php echo $error_info['message']; ?></p>
        
        <div class="error-actions">
            <a href="/" class="btn btn-primary">🏠 Retour à l'Accueil</a>
            <button onclick="history.back()" class="btn btn-secondary">⬅️ Page Précédente</button>
            <?php if ($error_code == 401): ?>
                <a href="/index.php" class="btn btn-primary">🔑 Se Connecter</a>
            <?php endif; ?>
        </div>
        
        <?php if ($error_code == 404): ?>
            <div style="margin-top: 30px;">
                <h3 style="color: #667eea; margin-bottom: 15px;">🎮 Que souhaitez-vous faire ?</h3>
                <div class="error-actions">
                    <a href="/roulette.php" class="btn btn-secondary">🎲 Jouer à la Roulette</a>
                    <a href="/blackjack.php" class="btn btn-secondary">♠️ Jouer au Blackjack</a>
                    <a href="/passive_earnings.php" class="btn btn-secondary">💰 Gains Passifs</a>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="error-details">
            <p><strong>Code d'erreur :</strong> <?php echo $error_code; ?></p>
            <p><strong>Heure :</strong> <?php echo date('d/m/Y à H:i:s'); ?></p>
            <?php if (isset($_SERVER['HTTP_REFERER'])): ?>
                <p><strong>Page précédente :</strong> <?php echo htmlspecialchars($_SERVER['HTTP_REFERER']); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Ajouter des effets interactifs
        document.addEventListener('DOMContentLoaded', function() {
            // Animation des jetons flottants
            const chips = document.querySelectorAll('.chip');
            chips.forEach((chip, index) => {
                chip.style.animationDelay = (index * 0.5) + 's';
                chip.style.left = Math.random() * 100 + '%';
            });
            
            // Effet de parallaxe léger
            document.addEventListener('mousemove', function(e) {
                const container = document.querySelector('.error-container');
                const x = (e.clientX / window.innerWidth) * 10;
                const y = (e.clientY / window.innerHeight) * 10;
                
                container.style.transform = `translate(${x}px, ${y}px)`;
            });
            
            // Auto-refresh pour les erreurs serveur
            <?php if ($error_code == 500): ?>
                setTimeout(function() {
                    if (confirm('Voulez-vous réessayer de charger la page ?')) {
                        location.reload();
                    }
                }, 10000);
            <?php endif; ?>
        });
        
        // Raccourcis clavier
        document.addEventListener('keydown', function(e) {
            switch(e.key) {
                case 'h':
                case 'H':
                    window.location.href = '/';
                    break;
                case 'Escape':
                    history.back();
                    break;
            }
        });
    </script>
</body>
</html>