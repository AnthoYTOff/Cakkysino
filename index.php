<?php
session_start();
require_once 'classes/User.php';

$user_obj = new User();
$current_user = null;

// Vérifier si l'utilisateur est connecté
if (isset($_COOKIE['session_token'])) {
    $current_user = $user_obj->verifySession($_COOKIE['session_token']);
}

// Traitement des formulaires
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'login':
                $result = $user_obj->login($_POST['username'], $_POST['password']);
                if ($result['success']) {
                    setcookie('session_token', $result['session_token'], time() + 86400, '/');
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'register':
                $result = $user_obj->register($_POST['username'], $_POST['email'], $_POST['password']);
                if ($result['success']) {
                    $message = $result['message'] . ' Vous pouvez maintenant vous connecter.';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'logout':
                if (isset($_COOKIE['session_token'])) {
                    $user_obj->logout($_COOKIE['session_token']);
                    setcookie('session_token', '', time() - 3600, '/');
                }
                header('Location: index.php');
                exit;
                break;
        }
    }
}

// Si l'utilisateur est connecté, rediriger vers le dashboard
if ($current_user) {
    header('Location: dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CakkySino - Casino en Ligne</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="casino-header">
            <h1>🎰 CakkySino</h1>
            <p>Le casino en ligne de référence</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="auth-forms">
            <!-- Formulaire de connexion -->
            <div class="auth-form" id="login-form">
                <h2>Connexion</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group">
                        <label for="login-username">Nom d'utilisateur:</label>
                        <input type="text" id="login-username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="login-password">Mot de passe:</label>
                        <input type="password" id="login-password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Se connecter</button>
                </form>
                <p class="auth-switch">
                    Pas encore de compte ? 
                    <a href="#" onclick="showRegister()">S'inscrire</a>
                </p>
            </div>
            
            <!-- Formulaire d'inscription -->
            <div class="auth-form" id="register-form" style="display: none;">
                <h2>Inscription</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="register">
                    <div class="form-group">
                        <label for="register-username">Nom d'utilisateur:</label>
                        <input type="text" id="register-username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="register-email">Email:</label>
                        <input type="email" id="register-email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="register-password">Mot de passe:</label>
                        <input type="password" id="register-password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">S'inscrire</button>
                </form>
                <p class="auth-switch">
                    Déjà un compte ? 
                    <a href="#" onclick="showLogin()">Se connecter</a>
                </p>
                <div class="welcome-bonus">
                    <p>🎁 <strong>Bonus de bienvenue:</strong> 1000 coins offerts à l'inscription !</p>
                </div>
            </div>
        </div>
        
        <div class="features">
            <h3>Pourquoi choisir CakkySino ?</h3>
            <div class="features-grid">
                <div class="feature">
                    <div class="feature-icon">💰</div>
                    <h4>Gains Passifs</h4>
                    <p>Gagnez des coins simplement en restant connecté</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">🎰</div>
                    <h4>Roulette Française</h4>
                    <p>Jeu automatique jusqu'à 6 joueurs</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">♠️</div>
                    <h4>Blackjack Live</h4>
                    <p>Avec croupier humain, jusqu'à 4 joueurs</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">🔒</div>
                    <h4>Sécurisé</h4>
                    <p>Jeu équitable et transactions sécurisées</p>
                </div>
            </div>
        </div>
        
        <div class="demo-info">
            <h4>Compte de démonstration</h4>
            <p><strong>Admin:</strong> admin / password</p>
            <p><em>Créez votre propre compte pour commencer à jouer !</em></p>
        </div>
    </div>
    
    <script>
        function showLogin() {
            document.getElementById('login-form').style.display = 'block';
            document.getElementById('register-form').style.display = 'none';
        }
        
        function showRegister() {
            document.getElementById('login-form').style.display = 'none';
            document.getElementById('register-form').style.display = 'block';
        }
        
        // Afficher le formulaire d'inscription si il y a un message de succès
        <?php if ($message): ?>
            showLogin();
        <?php endif; ?>
    </script>
</body>
</html>