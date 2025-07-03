<?php
/**
 * Script de test d'installation pour CakkySino
 * 
 * Ce script vérifie que tous les composants nécessaires sont installés
 * et configurés correctement.
 */

// Démarrer la session pour les tests
session_start();

// Inclure la configuration de la base de données
require_once 'config/database.php';

// Fonction pour afficher les résultats des tests
function displayTestResult($test_name, $result, $message = '') {
    $status = $result ? '✅ PASS' : '❌ FAIL';
    echo "<div style='margin: 10px 0; padding: 10px; border-left: 4px solid " . ($result ? '#4CAF50' : '#f44336') . "; background: " . ($result ? '#f1f8e9' : '#ffebee') . ";'>";
    echo "<strong>{$status}</strong> - {$test_name}";
    if ($message) {
        echo "<br><small>{$message}</small>";
    }
    echo "</div>";
}

// Fonction pour tester la connexion à la base de données
function testDatabaseConnection() {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        if ($conn) {
            // Test d'une requête simple
            $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
            $result = $stmt->fetch();
            return ['success' => true, 'message' => "Connexion réussie. {$result['count']} utilisateurs trouvés."];
        } else {
            return ['success' => false, 'message' => 'Impossible d\'établir la connexion.'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
    }
}

// Fonction pour vérifier l'existence des fichiers essentiels
function checkEssentialFiles() {
    $files = [
        'index.php' => 'Page d\'accueil',
        'dashboard.php' => 'Tableau de bord',
        'admin.php' => 'Interface d\'administration',
        'roulette.php' => 'Jeu de roulette',
        'blackjack.php' => 'Jeu de blackjack',
        'passive_earnings.php' => 'Gains passifs',
        'config/database.php' => 'Configuration BDD',
        'assets/css/style.css' => 'Styles CSS',
        'assets/js/dashboard.js' => 'Scripts JavaScript',
        'database.sql' => 'Structure BDD'
    ];
    
    $results = [];
    foreach ($files as $file => $description) {
        $exists = file_exists($file);
        $results[] = [
            'file' => $file,
            'description' => $description,
            'exists' => $exists
        ];
    }
    
    return $results;
}

// Fonction pour vérifier les extensions PHP
function checkPHPExtensions() {
    $extensions = [
        'pdo' => 'PDO (base de données)',
        'pdo_mysql' => 'PDO MySQL',
        'json' => 'JSON',
        'session' => 'Sessions',
        'mbstring' => 'Multibyte String'
    ];
    
    $results = [];
    foreach ($extensions as $ext => $description) {
        $loaded = extension_loaded($ext);
        $results[] = [
            'extension' => $ext,
            'description' => $description,
            'loaded' => $loaded
        ];
    }
    
    return $results;
}

// Fonction pour tester les classes principales
function testClasses() {
    $classes = [
        'classes/User.php' => 'User',
        'classes/CasinoBank.php' => 'CasinoBank',
        'classes/PassiveEarnings.php' => 'PassiveEarnings',
        'classes/Roulette.php' => 'Roulette',
        'classes/Blackjack.php' => 'Blackjack'
    ];
    
    $results = [];
    foreach ($classes as $file => $className) {
        $exists = file_exists($file);
        $classExists = false;
        
        if ($exists) {
            try {
                require_once $file;
                $classExists = class_exists($className);
            } catch (Exception $e) {
                // Erreur lors du chargement de la classe
            }
        }
        
        $results[] = [
            'file' => $file,
            'class' => $className,
            'file_exists' => $exists,
            'class_exists' => $classExists
        ];
    }
    
    return $results;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test d'Installation - CakkySino</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5em;
        }
        h2 {
            color: #555;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
            margin-top: 30px;
        }
        .test-section {
            margin: 20px 0;
        }
        .summary {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin: 10px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #5a6fd8;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .status-ok { color: #4CAF50; font-weight: bold; }
        .status-error { color: #f44336; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎰 Test d'Installation CakkySino</h1>
        
        <?php
        $allTestsPassed = true;
        
        // Test de la version PHP
        echo "<div class='test-section'>";
        echo "<h2>🔧 Configuration PHP</h2>";
        $phpVersion = phpversion();
        $phpOk = version_compare($phpVersion, '7.4.0', '>=');
        displayTestResult("Version PHP", $phpOk, "Version actuelle: {$phpVersion} (minimum requis: 7.4.0)");
        if (!$phpOk) $allTestsPassed = false;
        echo "</div>";
        
        // Test des extensions PHP
        echo "<div class='test-section'>";
        echo "<h2>📦 Extensions PHP</h2>";
        $extensions = checkPHPExtensions();
        foreach ($extensions as $ext) {
            displayTestResult(
                "Extension {$ext['extension']}",
                $ext['loaded'],
                $ext['description']
            );
            if (!$ext['loaded']) $allTestsPassed = false;
        }
        echo "</div>";
        
        // Test de la connexion à la base de données
        echo "<div class='test-section'>";
        echo "<h2>🗄️ Base de Données</h2>";
        $dbTest = testDatabaseConnection();
        displayTestResult("Connexion à la base de données", $dbTest['success'], $dbTest['message']);
        if (!$dbTest['success']) $allTestsPassed = false;
        echo "</div>";
        
        // Test des fichiers essentiels
        echo "<div class='test-section'>";
        echo "<h2>📁 Fichiers Essentiels</h2>";
        $files = checkEssentialFiles();
        echo "<table>";
        echo "<tr><th>Fichier</th><th>Description</th><th>Statut</th></tr>";
        foreach ($files as $file) {
            $status = $file['exists'] ? "<span class='status-ok'>✅ Présent</span>" : "<span class='status-error'>❌ Manquant</span>";
            echo "<tr><td>{$file['file']}</td><td>{$file['description']}</td><td>{$status}</td></tr>";
            if (!$file['exists']) $allTestsPassed = false;
        }
        echo "</table>";
        echo "</div>";
        
        // Test des classes PHP
        echo "<div class='test-section'>";
        echo "<h2>🏗️ Classes PHP</h2>";
        $classes = testClasses();
        echo "<table>";
        echo "<tr><th>Fichier</th><th>Classe</th><th>Fichier</th><th>Classe</th></tr>";
        foreach ($classes as $class) {
            $fileStatus = $class['file_exists'] ? "<span class='status-ok'>✅</span>" : "<span class='status-error'>❌</span>";
            $classStatus = $class['class_exists'] ? "<span class='status-ok'>✅</span>" : "<span class='status-error'>❌</span>";
            echo "<tr><td>{$class['file']}</td><td>{$class['class']}</td><td>{$fileStatus}</td><td>{$classStatus}</td></tr>";
            if (!$class['file_exists'] || !$class['class_exists']) $allTestsPassed = false;
        }
        echo "</table>";
        echo "</div>";
        
        // Résumé final
        echo "<div class='summary'>";
        if ($allTestsPassed) {
            echo "<h2 style='color: #4CAF50;'>🎉 Installation Réussie !</h2>";
            echo "<p>Tous les tests sont passés avec succès. Votre installation de CakkySino est prête à être utilisée.</p>";
            echo "<a href='index.php' class='btn'>🚀 Accéder au Casino</a>";
            echo "<a href='admin.php' class='btn'>⚙️ Administration</a>";
        } else {
            echo "<h2 style='color: #f44336;'>⚠️ Problèmes Détectés</h2>";
            echo "<p>Certains tests ont échoué. Veuillez corriger les problèmes avant d'utiliser CakkySino.</p>";
            echo "<a href='README.md' class='btn'>📖 Consulter la Documentation</a>";
        }
        echo "</div>";
        
        // Informations système
        echo "<div class='test-section'>";
        echo "<h2>ℹ️ Informations Système</h2>";
        echo "<table>";
        echo "<tr><th>Paramètre</th><th>Valeur</th></tr>";
        echo "<tr><td>Version PHP</td><td>" . phpversion() . "</td></tr>";
        echo "<tr><td>Système d'exploitation</td><td>" . php_uname('s') . " " . php_uname('r') . "</td></tr>";
        echo "<tr><td>Serveur Web</td><td>" . ($_SERVER['SERVER_SOFTWARE'] ?? 'Inconnu') . "</td></tr>";
        echo "<tr><td>Limite mémoire</td><td>" . ini_get('memory_limit') . "</td></tr>";
        echo "<tr><td>Temps d'exécution max</td><td>" . ini_get('max_execution_time') . "s</td></tr>";
        echo "<tr><td>Taille max upload</td><td>" . ini_get('upload_max_filesize') . "</td></tr>";
        echo "</table>";
        echo "</div>";
        ?>
        
        <div style="text-align: center; margin-top: 30px; color: #666;">
            <p>CakkySino - Casino en Ligne | Test d'Installation</p>
            <p><small>Généré le <?php echo date('d/m/Y à H:i:s'); ?></small></p>
        </div>
    </div>
</body>
</html>