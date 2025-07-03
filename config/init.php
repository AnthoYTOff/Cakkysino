<?php
/**
 * Fichier d'initialisation principal pour CakkySino
 * 
 * Ce fichier configure l'environnement de l'application,
 * charge les dépendances et initialise les composants essentiels.
 */

// Empêcher l'accès direct
if (basename($_SERVER['PHP_SELF']) === 'init.php') {
    die('Accès direct interdit');
}

// Marquer que l'initialisation a commencé
define('CAKKYSINO_INIT', true);

// Démarrer la mesure du temps d'exécution
$start_time = microtime(true);

// ================================
// CONFIGURATION PHP
// ================================

// Définir le niveau de rapport d'erreurs
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Configuration de la mémoire et du temps d'exécution
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 30);

// Configuration de l'encodage
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

// ================================
// CHARGEMENT DES CONSTANTES
// ================================

// Charger les constantes si elles ne sont pas déjà chargées
if (!defined('CONSTANTS_LOADED')) {
    require_once __DIR__ . '/constants.php';
}

// ================================
// CONFIGURATION DU TIMEZONE
// ================================

date_default_timezone_set(DEFAULT_TIMEZONE);

// ================================
// CONFIGURATION DES SESSIONS
// ================================

// Configuration sécurisée des sessions
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Mettre à 1 si HTTPS
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.name', 'CAKKYSINO_SESSION');

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ================================
// CRÉATION DES DOSSIERS NÉCESSAIRES
// ================================

$required_dirs = [
    LOG_DIR,
    CACHE_DIR,
    UPLOAD_DIR
];

foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            error_log("Impossible de créer le dossier: {$dir}");
        }
    }
}

// ================================
// FONCTIONS UTILITAIRES GLOBALES
// ================================

/**
 * Fonction de logging personnalisée
 * @param string $message
 * @param string $level
 * @param string $file
 */
function logMessage($message, $level = 'INFO', $file = 'app.log') {
    $log_levels = ['DEBUG', 'INFO', 'WARNING', 'ERROR'];
    $current_level_index = array_search(LOG_LEVEL, $log_levels);
    $message_level_index = array_search($level, $log_levels);
    
    // Ne logger que si le niveau est suffisant
    if ($message_level_index >= $current_level_index) {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        $log_file = LOG_DIR . $file;
        
        // Rotation des logs si nécessaire
        if (file_exists($log_file) && filesize($log_file) > (LOG_MAX_FILE_SIZE * 1024 * 1024)) {
            rotateLogFile($log_file);
        }
        
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Rotation des fichiers de log
 * @param string $log_file
 */
function rotateLogFile($log_file) {
    $base_name = pathinfo($log_file, PATHINFO_FILENAME);
    $extension = pathinfo($log_file, PATHINFO_EXTENSION);
    $dir = dirname($log_file);
    
    // Déplacer les anciens fichiers
    for ($i = LOG_MAX_FILES - 1; $i > 0; $i--) {
        $old_file = $dir . '/' . $base_name . '.' . $i . '.' . $extension;
        $new_file = $dir . '/' . $base_name . '.' . ($i + 1) . '.' . $extension;
        
        if (file_exists($old_file)) {
            if ($i == LOG_MAX_FILES - 1) {
                unlink($old_file); // Supprimer le plus ancien
            } else {
                rename($old_file, $new_file);
            }
        }
    }
    
    // Renommer le fichier actuel
    $archived_file = $dir . '/' . $base_name . '.1.' . $extension;
    rename($log_file, $archived_file);
}

/**
 * Fonction de debug sécurisée
 * @param mixed $data
 * @param string $label
 */
function debugLog($data, $label = 'DEBUG') {
    if (isDebugMode()) {
        $message = $label . ': ' . (is_array($data) || is_object($data) ? json_encode($data, JSON_PRETTY_PRINT) : $data);
        logMessage($message, 'DEBUG', 'debug.log');
    }
}

/**
 * Fonction de validation et nettoyage des données
 * @param mixed $data
 * @param string $type
 * @return mixed
 */
function sanitizeInput($data, $type = 'string') {
    if (is_array($data)) {
        return array_map(function($item) use ($type) {
            return sanitizeInput($item, $type);
        }, $data);
    }
    
    switch ($type) {
        case 'int':
            return filter_var($data, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        
        case 'float':
            return filter_var($data, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
        
        case 'email':
            return filter_var($data, FILTER_VALIDATE_EMAIL);
        
        case 'url':
            return filter_var($data, FILTER_VALIDATE_URL);
        
        case 'html':
            return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        
        case 'sql':
            return addslashes($data);
        
        case 'string':
        default:
            return trim(strip_tags($data));
    }
}

/**
 * Génération de token CSRF
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token']) || 
        !isset($_SESSION['csrf_token_time']) || 
        (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_LIFETIME) {
        
        $_SESSION['csrf_token'] = generateSecureToken();
        $_SESSION['csrf_token_time'] = time();
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Vérification de token CSRF
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && 
           isset($_SESSION['csrf_token_time']) && 
           hash_equals($_SESSION['csrf_token'], $token) && 
           (time() - $_SESSION['csrf_token_time']) <= CSRF_TOKEN_LIFETIME;
}

/**
 * Fonction de réponse JSON standardisée
 * @param bool $success
 * @param mixed $data
 * @param string $message
 * @param int $code
 */
function jsonResponse($success, $data = null, $message = '', $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    
    $response = [
        'success' => $success,
        'timestamp' => time(),
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Fonction de redirection sécurisée
 * @param string $url
 * @param int $code
 */
function redirect($url, $code = 302) {
    // Valider l'URL pour éviter les redirections malveillantes
    if (!filter_var($url, FILTER_VALIDATE_URL) && !preg_match('/^\//', $url)) {
        $url = '/';
    }
    
    header("Location: {$url}", true, $code);
    exit;
}

/**
 * Vérification de l'authentification
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['username']) && 
           isset($_SESSION['session_token']);
}

/**
 * Vérification des droits administrateur
 * @return bool
 */
function isAdmin() {
    return isLoggedIn() && 
           isset($_SESSION['is_admin']) && 
           $_SESSION['is_admin'] === true;
}

/**
 * Obtenir l'ID de l'utilisateur connecté
 * @return int|null
 */
function getCurrentUserId() {
    return isLoggedIn() ? (int)$_SESSION['user_id'] : null;
}

/**
 * Obtenir le nom d'utilisateur connecté
 * @return string|null
 */
function getCurrentUsername() {
    return isLoggedIn() ? $_SESSION['username'] : null;
}

/**
 * Fonction de limitation de taux (rate limiting)
 * @param string $key
 * @param int $limit
 * @param int $window
 * @return bool
 */
function checkRateLimit($key, $limit = RATE_LIMIT_REQUESTS, $window = RATE_LIMIT_WINDOW) {
    $cache_file = CACHE_DIR . 'rate_limit_' . md5($key) . '.json';
    $current_time = time();
    
    $data = [];
    if (file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true) ?: [];
    }
    
    // Nettoyer les anciennes entrées
    $data = array_filter($data, function($timestamp) use ($current_time, $window) {
        return ($current_time - $timestamp) < $window;
    });
    
    // Vérifier la limite
    if (count($data) >= $limit) {
        return false;
    }
    
    // Ajouter la nouvelle requête
    $data[] = $current_time;
    file_put_contents($cache_file, json_encode($data), LOCK_EX);
    
    return true;
}

/**
 * Nettoyage du cache
 * @param int $max_age Âge maximum en secondes
 */
function cleanCache($max_age = 3600) {
    $cache_files = glob(CACHE_DIR . '*');
    $current_time = time();
    
    foreach ($cache_files as $file) {
        if (is_file($file) && ($current_time - filemtime($file)) > $max_age) {
            unlink($file);
        }
    }
}

/**
 * Obtenir une valeur du cache
 * @param string $key
 * @param int $max_age
 * @return mixed|null
 */
function getCache($key, $max_age = STATS_CACHE_DURATION) {
    $cache_file = CACHE_DIR . 'cache_' . md5($key) . '.json';
    
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $max_age) {
        $data = file_get_contents($cache_file);
        return json_decode($data, true);
    }
    
    return null;
}

/**
 * Mettre une valeur en cache
 * @param string $key
 * @param mixed $data
 * @return bool
 */
function setCache($key, $data) {
    $cache_file = CACHE_DIR . 'cache_' . md5($key) . '.json';
    return file_put_contents($cache_file, json_encode($data), LOCK_EX) !== false;
}

// ================================
// GESTION DES ERREURS
// ================================

/**
 * Gestionnaire d'erreurs personnalisé
 */
function customErrorHandler($severity, $message, $file, $line) {
    $error_types = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_PARSE => 'PARSE',
        E_NOTICE => 'NOTICE',
        E_CORE_ERROR => 'CORE_ERROR',
        E_CORE_WARNING => 'CORE_WARNING',
        E_COMPILE_ERROR => 'COMPILE_ERROR',
        E_COMPILE_WARNING => 'COMPILE_WARNING',
        E_USER_ERROR => 'USER_ERROR',
        E_USER_WARNING => 'USER_WARNING',
        E_USER_NOTICE => 'USER_NOTICE',
        E_STRICT => 'STRICT',
        E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
        E_DEPRECATED => 'DEPRECATED',
        E_USER_DEPRECATED => 'USER_DEPRECATED'
    ];
    
    $error_type = isset($error_types[$severity]) ? $error_types[$severity] : 'UNKNOWN';
    $log_message = "PHP {$error_type}: {$message} in {$file} on line {$line}";
    
    logMessage($log_message, 'ERROR', 'php_errors.log');
    
    // Ne pas arrêter l'exécution pour les erreurs non fatales
    return true;
}

/**
 * Gestionnaire d'exceptions non capturées
 */
function customExceptionHandler($exception) {
    $log_message = "Uncaught exception: " . $exception->getMessage() . 
                   " in " . $exception->getFile() . 
                   " on line " . $exception->getLine();
    
    logMessage($log_message, 'ERROR', 'exceptions.log');
    
    if (isDebugMode()) {
        echo "<pre>" . $exception->getTraceAsString() . "</pre>";
    } else {
        // Rediriger vers une page d'erreur en production
        if (!headers_sent()) {
            header('Location: /error.php?code=500');
            exit;
        }
    }
}

/**
 * Gestionnaire d'arrêt fatal
 */
function customShutdownHandler() {
    $error = error_get_last();
    
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        $log_message = "Fatal error: {$error['message']} in {$error['file']} on line {$error['line']}";
        logMessage($log_message, 'ERROR', 'fatal_errors.log');
    }
    
    // Calculer le temps d'exécution
    global $start_time;
    $execution_time = microtime(true) - $start_time;
    
    if ($execution_time > 1) { // Log si l'exécution prend plus d'1 seconde
        logMessage("Slow execution: {$execution_time}s for {$_SERVER['REQUEST_URI']}", 'WARNING', 'performance.log');
    }
}

// Enregistrer les gestionnaires d'erreurs
set_error_handler('customErrorHandler');
set_exception_handler('customExceptionHandler');
register_shutdown_function('customShutdownHandler');

// ================================
// NETTOYAGE AUTOMATIQUE
// ================================

// Nettoyer le cache et les logs anciens (1% de chance à chaque requête)
if (rand(1, 100) === 1) {
    cleanCache();
    
    // Nettoyer les anciens logs
    $log_files = glob(LOG_DIR . '*.log');
    $max_age = LOG_RETENTION_DAYS * 24 * 3600;
    
    foreach ($log_files as $file) {
        if ((time() - filemtime($file)) > $max_age) {
            unlink($file);
        }
    }
}

// ================================
// FINALISATION
// ================================

// Logger le démarrage de l'application
logMessage("Application initialized for {$_SERVER['REQUEST_URI']}", 'INFO');

// Marquer que l'initialisation est terminée
define('CAKKYSINO_READY', true);

?>