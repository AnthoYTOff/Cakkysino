@echo off
REM Script de démarrage pour CakkySino
REM Ce script lance un serveur de développement PHP local

echo ========================================
echo    🎰 CakkySino - Casino en Ligne
echo ========================================
echo.

REM Vérifier si PHP est installé
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ ERREUR: PHP n'est pas installé ou n'est pas dans le PATH
    echo.
    echo Veuillez installer PHP et l'ajouter au PATH système.
    echo Téléchargement: https://www.php.net/downloads
    echo.
    pause
    exit /b 1
)

echo ✅ PHP détecté:
php --version | findstr "PHP"
echo.

REM Vérifier si MySQL est accessible (optionnel)
echo 🔍 Vérification de MySQL...
mysql --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ⚠️  MySQL CLI non détecté (optionnel)
) else (
    echo ✅ MySQL CLI détecté
)
echo.

REM Aller dans le dossier du projet
cd /d "%~dp0"

REM Vérifier les fichiers essentiels
echo 🔍 Vérification des fichiers...
if not exist "index.php" (
    echo ❌ ERREUR: index.php non trouvé
    pause
    exit /b 1
)
if not exist "config\database.php" (
    echo ❌ ERREUR: config\database.php non trouvé
    pause
    exit /b 1
)
echo ✅ Fichiers essentiels présents
echo.

REM Créer les dossiers nécessaires s'ils n'existent pas
echo 📁 Création des dossiers...
if not exist "logs" mkdir logs
if not exist "cache" mkdir cache
if not exist "uploads" mkdir uploads
echo ✅ Dossiers créés
echo.

REM Afficher les options
echo 🚀 Options de démarrage:
echo.
echo [1] Lancer le serveur de développement (Port 8000)
echo [2] Lancer le serveur de développement (Port personnalisé)
echo [3] Tester l'installation
echo [4] Ouvrir dans le navigateur seulement
echo [5] Quitter
echo.
set /p choice="Votre choix (1-5): "

if "%choice%"=="1" goto start_default
if "%choice%"=="2" goto start_custom
if "%choice%"=="3" goto test_install
if "%choice%"=="4" goto open_browser
if "%choice%"=="5" goto end

echo Choix invalide, démarrage par défaut...
goto start_default

:start_default
set PORT=8000
goto start_server

:start_custom
set /p PORT="Entrez le port (ex: 8080): "
if "%PORT%"=="" set PORT=8000
goto start_server

:start_server
echo.
echo 🌐 Démarrage du serveur sur le port %PORT%...
echo.
echo 📍 URLs disponibles:
echo    - Accueil:          http://localhost:%PORT%/
echo    - Test installation: http://localhost:%PORT%/test_installation.php
echo    - Administration:   http://localhost:%PORT%/admin.php
echo    - Roulette:         http://localhost:%PORT%/roulette.php
echo    - Blackjack:        http://localhost:%PORT%/blackjack.php
echo    - Gains Passifs:    http://localhost:%PORT%/passive_earnings.php
echo.
echo 🔑 Comptes par défaut:
echo    - Admin:    admin / password
echo    - Joueur 1: player1 / password
echo    - Joueur 2: player2 / password
echo    - Croupier: croupier / password
echo.
echo ⚠️  IMPORTANT:
echo    - Assurez-vous que MySQL est démarré
echo    - Importez database.sql dans votre base de données
echo    - Configurez config\database.php si nécessaire
echo.
echo 🛑 Pour arrêter le serveur: Ctrl+C
echo.
echo ========================================
echo.

REM Ouvrir automatiquement le navigateur après 3 secondes
start "" "http://localhost:%PORT%/"

REM Démarrer le serveur PHP
php -S localhost:%PORT%
goto end

:test_install
echo.
echo 🧪 Test de l'installation...
echo.
php -f test_installation.php
if %errorlevel% neq 0 (
    echo ❌ Erreur lors du test
) else (
    echo ✅ Test terminé
)
echo.
echo Voulez-vous ouvrir le test dans le navigateur ? (o/n)
set /p open_test="Votre choix: "
if /i "%open_test%"=="o" (
    echo Démarrage du serveur pour le test...
    start "" "http://localhost:8000/test_installation.php"
    php -S localhost:8000
)
goto end

:open_browser
echo.
echo 🌐 Ouverture dans le navigateur...
echo ⚠️  Assurez-vous qu'un serveur web est déjà en cours d'exécution
echo.
start "" "http://localhost:8000/"
goto end

:end
echo.
echo 👋 Merci d'avoir utilisé CakkySino !
echo.
pause