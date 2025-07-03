<?php
/**
 * Constantes globales pour CakkySino
 * 
 * Ce fichier contient toutes les constantes de configuration
 * utilisées dans l'application.
 */

// Empêcher l'accès direct
if (!defined('CAKKYSINO_INIT')) {
    die('Accès direct interdit');
}

// ================================
// CONFIGURATION GÉNÉRALE
// ================================

// Nom du casino
define('CASINO_NAME', 'CakkySino');

// Version de l'application
define('CASINO_VERSION', '1.0.0');

// Mode debug (à désactiver en production)
define('DEBUG_MODE', true);

// Timezone par défaut
define('DEFAULT_TIMEZONE', 'Europe/Paris');

// Langue par défaut
define('DEFAULT_LANGUAGE', 'fr');

// ================================
// CONFIGURATION DES UTILISATEURS
// ================================

// Coins de départ pour les nouveaux utilisateurs
define('STARTING_COINS', 1000);

// Coins minimum requis pour jouer
define('MIN_COINS_TO_PLAY', 10);

// Limite de coins par utilisateur
define('MAX_USER_COINS', 1000000);

// Durée de session en secondes (24 heures)
define('SESSION_LIFETIME', 86400);

// Nombre maximum de tentatives de connexion
define('MAX_LOGIN_ATTEMPTS', 5);

// Durée de blocage après échec de connexion (en minutes)
define('LOGIN_BLOCK_DURATION', 15);

// ================================
// CONFIGURATION DES GAINS PASSIFS
// ================================

// Taux de base des gains passifs (coins par minute)
define('PASSIVE_BASE_RATE', 1.0);

// Bonus maximum d'activité (pourcentage)
define('PASSIVE_MAX_ACTIVITY_BONUS', 50);

// Pénalité d'inactivité (pourcentage)
define('PASSIVE_INACTIVITY_PENALTY', 30);

// Intervalle de vérification d'activité (secondes)
define('PASSIVE_ACTIVITY_CHECK_INTERVAL', 30);

// Temps d'inactivité avant pénalité (secondes)
define('PASSIVE_INACTIVITY_THRESHOLD', 60);

// Durée maximum d'une session de gains passifs (heures)
define('PASSIVE_MAX_SESSION_DURATION', 8);

// Gains maximum par session
define('PASSIVE_MAX_EARNINGS_PER_SESSION', 500);

// ================================
// CONFIGURATION DE LA ROULETTE
// ================================

// Délai avant lancement automatique (secondes)
define('ROULETTE_AUTO_LAUNCH_DELAY', 30);

// Mise minimum
define('ROULETTE_MIN_BET', 1);

// Mise maximum
define('ROULETTE_MAX_BET', 1000);

// Nombre maximum de joueurs par partie
define('ROULETTE_MAX_PLAYERS', 50);

// Durée de rotation de la roue (secondes)
define('ROULETTE_SPIN_DURATION', 5);

// Multiplicateurs de gains
define('ROULETTE_PAYOUT_STRAIGHT', 35);     // Numéro plein
define('ROULETTE_PAYOUT_SPLIT', 17);        // Cheval
define('ROULETTE_PAYOUT_STREET', 11);       // Transversale
define('ROULETTE_PAYOUT_CORNER', 8);        // Carré
define('ROULETTE_PAYOUT_LINE', 5);          // Sixain
define('ROULETTE_PAYOUT_COLUMN', 2);        // Colonne
define('ROULETTE_PAYOUT_DOZEN', 2);         // Douzaine
define('ROULETTE_PAYOUT_EVEN_ODD', 1);      // Pair/Impair
define('ROULETTE_PAYOUT_RED_BLACK', 1);     // Rouge/Noir
define('ROULETTE_PAYOUT_HIGH_LOW', 1);      // Manque/Passe

// ================================
// CONFIGURATION DU BLACKJACK
// ================================

// Nombre maximum de joueurs par table
define('BLACKJACK_MAX_PLAYERS', 6);

// Mise minimum
define('BLACKJACK_MIN_BET', 10);

// Mise maximum
define('BLACKJACK_MAX_BET', 500);

// Multiplicateur pour un blackjack naturel
define('BLACKJACK_NATURAL_PAYOUT', 1.5);

// Multiplicateur pour une victoire normale
define('BLACKJACK_WIN_PAYOUT', 1.0);

// Valeur à laquelle le croupier doit s'arrêter
define('BLACKJACK_DEALER_STAND', 17);

// Valeur maximum avant bust
define('BLACKJACK_BUST_VALUE', 21);

// Timeout pour les actions des joueurs (secondes)
define('BLACKJACK_PLAYER_TIMEOUT', 30);

// ================================
// CONFIGURATION DE LA BANQUE
// ================================

// Solde initial de la banque
define('BANK_INITIAL_BALANCE', 100000);

// Solde minimum de la banque
define('BANK_MIN_BALANCE', 10000);

// Solde maximum de la banque
define('BANK_MAX_BALANCE', 10000000);

// Pourcentage de commission sur les gains
define('BANK_COMMISSION_RATE', 5);

// Seuil d'alerte pour solde faible
define('BANK_LOW_BALANCE_ALERT', 50000);

// ================================
// CONFIGURATION DES LOGS
// ================================

// Durée de conservation des logs (jours)
define('LOG_RETENTION_DAYS', 30);

// Niveau de log minimum (DEBUG, INFO, WARNING, ERROR)
define('LOG_LEVEL', 'INFO');

// Taille maximum des fichiers de log (Mo)
define('LOG_MAX_FILE_SIZE', 10);

// Nombre maximum de fichiers de log à conserver
define('LOG_MAX_FILES', 5);

// ================================
// CONFIGURATION DE SÉCURITÉ
// ================================

// Clé secrète pour le hachage (à changer en production)
define('SECRET_KEY', 'CakkySino_Secret_Key_2024_Change_Me_In_Production');

// Algorithme de hachage pour les mots de passe
define('PASSWORD_HASH_ALGO', PASSWORD_DEFAULT);

// Coût pour le hachage bcrypt
define('PASSWORD_HASH_COST', 12);

// Durée de validité des tokens CSRF (secondes)
define('CSRF_TOKEN_LIFETIME', 3600);

// Longueur des tokens de session
define('SESSION_TOKEN_LENGTH', 32);

// ================================
// CONFIGURATION DES PERFORMANCES
// ================================

// Durée de cache pour les statistiques (secondes)
define('STATS_CACHE_DURATION', 300);

// Nombre maximum de requêtes par minute par IP
define('RATE_LIMIT_REQUESTS', 100);

// Durée de la fenêtre de rate limiting (secondes)
define('RATE_LIMIT_WINDOW', 60);

// Taille maximum des résultats de requête
define('MAX_QUERY_RESULTS', 1000);

// ================================
// CONFIGURATION DES NOTIFICATIONS
// ================================

// Activer les notifications en temps réel
define('ENABLE_REAL_TIME_NOTIFICATIONS', true);

// Intervalle de mise à jour des notifications (millisecondes)
define('NOTIFICATION_UPDATE_INTERVAL', 5000);

// Durée d'affichage des notifications (millisecondes)
define('NOTIFICATION_DISPLAY_DURATION', 3000);

// ================================
// CONFIGURATION DES EMAILS
// ================================

// Activer l'envoi d'emails
define('ENABLE_EMAIL', false);

// Adresse email de l'expéditeur
define('EMAIL_FROM', 'noreply@cakkysino.com');

// Nom de l'expéditeur
define('EMAIL_FROM_NAME', 'CakkySino');

// Serveur SMTP
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_ENCRYPTION', 'tls');

// ================================
// CONFIGURATION DES FICHIERS
// ================================

// Dossier de stockage des logs
define('LOG_DIR', __DIR__ . '/../logs/');

// Dossier de stockage des uploads
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Dossier de cache
define('CACHE_DIR', __DIR__ . '/../cache/');

// Extensions de fichiers autorisées pour l'upload
define('ALLOWED_FILE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt']);

// Taille maximum des fichiers uploadés (octets)
define('MAX_UPLOAD_SIZE', 5242880); // 5 Mo

// ================================
// CONFIGURATION DE L'API
// ================================

// Version de l'API
define('API_VERSION', 'v1');

// Préfixe des routes API
define('API_PREFIX', '/api/v1');

// Format de réponse par défaut
define('API_DEFAULT_FORMAT', 'json');

// Timeout pour les requêtes API (secondes)
define('API_TIMEOUT', 30);

// Activer la documentation automatique de l'API
define('API_ENABLE_DOCS', true);

// ================================
// MESSAGES D'ERREUR
// ================================

// Messages d'erreur génériques
define('ERROR_GENERIC', 'Une erreur inattendue s\'est produite.');
define('ERROR_INVALID_REQUEST', 'Requête invalide.');
define('ERROR_UNAUTHORIZED', 'Accès non autorisé.');
define('ERROR_FORBIDDEN', 'Accès interdit.');
define('ERROR_NOT_FOUND', 'Ressource non trouvée.');
define('ERROR_INTERNAL_SERVER', 'Erreur interne du serveur.');

// Messages d'erreur spécifiques
define('ERROR_INSUFFICIENT_FUNDS', 'Fonds insuffisants.');
define('ERROR_GAME_NOT_FOUND', 'Partie non trouvée.');
define('ERROR_GAME_ALREADY_STARTED', 'La partie a déjà commencé.');
define('ERROR_GAME_FULL', 'La partie est complète.');
define('ERROR_INVALID_BET', 'Mise invalide.');
define('ERROR_USER_NOT_FOUND', 'Utilisateur non trouvé.');
define('ERROR_INVALID_CREDENTIALS', 'Identifiants invalides.');

// ================================
// MESSAGES DE SUCCÈS
// ================================

define('SUCCESS_LOGIN', 'Connexion réussie.');
define('SUCCESS_LOGOUT', 'Déconnexion réussie.');
define('SUCCESS_REGISTER', 'Inscription réussie.');
define('SUCCESS_BET_PLACED', 'Mise placée avec succès.');
define('SUCCESS_GAME_CREATED', 'Partie créée avec succès.');
define('SUCCESS_PROFILE_UPDATED', 'Profil mis à jour avec succès.');

// ================================
// CONFIGURATION DES COULEURS DE LA ROULETTE
// ================================

// Numéros rouges
define('ROULETTE_RED_NUMBERS', [1, 3, 5, 7, 9, 12, 14, 16, 18, 19, 21, 23, 25, 27, 30, 32, 34, 36]);

// Numéros noirs
define('ROULETTE_BLACK_NUMBERS', [2, 4, 6, 8, 10, 11, 13, 15, 17, 20, 22, 24, 26, 28, 29, 31, 33, 35]);

// Le zéro est vert
define('ROULETTE_GREEN_NUMBERS', [0]);

// ================================
// CONFIGURATION DES CARTES (BLACKJACK)
// ================================

// Valeurs des cartes
define('CARD_VALUES', [
    'A' => [1, 11],  // As peut valoir 1 ou 11
    '2' => [2],
    '3' => [3],
    '4' => [4],
    '5' => [5],
    '6' => [6],
    '7' => [7],
    '8' => [8],
    '9' => [9],
    '10' => [10],
    'J' => [10],
    'Q' => [10],
    'K' => [10]
]);

// Couleurs des cartes
define('CARD_SUITS', ['♠', '♥', '♦', '♣']);

// ================================
// FONCTIONS UTILITAIRES
// ================================

/**
 * Vérifie si l'application est en mode debug
 * @return bool
 */
function isDebugMode() {
    return defined('DEBUG_MODE') && DEBUG_MODE === true;
}

/**
 * Retourne la version de l'application
 * @return string
 */
function getAppVersion() {
    return defined('CASINO_VERSION') ? CASINO_VERSION : '1.0.0';
}

/**
 * Retourne le nom du casino
 * @return string
 */
function getCasinoName() {
    return defined('CASINO_NAME') ? CASINO_NAME : 'CakkySino';
}

/**
 * Vérifie si un numéro de roulette est rouge
 * @param int $number
 * @return bool
 */
function isRedNumber($number) {
    return in_array($number, ROULETTE_RED_NUMBERS);
}

/**
 * Vérifie si un numéro de roulette est noir
 * @param int $number
 * @return bool
 */
function isBlackNumber($number) {
    return in_array($number, ROULETTE_BLACK_NUMBERS);
}

/**
 * Vérifie si un numéro de roulette est vert (zéro)
 * @param int $number
 * @return bool
 */
function isGreenNumber($number) {
    return in_array($number, ROULETTE_GREEN_NUMBERS);
}

/**
 * Formate un montant de coins
 * @param float $amount
 * @return string
 */
function formatCoins($amount) {
    return number_format($amount, 0, ',', ' ') . ' coins';
}

/**
 * Génère un token sécurisé
 * @param int $length
 * @return string
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Vérifie si une adresse IP est dans une liste blanche
 * @param string $ip
 * @param array $whitelist
 * @return bool
 */
function isIpWhitelisted($ip, $whitelist = []) {
    if (empty($whitelist)) {
        return true;
    }
    return in_array($ip, $whitelist);
}

// Marquer que les constantes ont été chargées
define('CONSTANTS_LOADED', true);

?>