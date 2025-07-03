#!/bin/bash

# Script de démarrage pour CakkySino
# Ce script lance un serveur de développement PHP local

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Fonction pour afficher un titre
print_title() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${PURPLE}    🎰 CakkySino - Casino en Ligne${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo
}

# Fonction pour afficher un message de succès
print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

# Fonction pour afficher un message d'erreur
print_error() {
    echo -e "${RED}❌ ERREUR: $1${NC}"
}

# Fonction pour afficher un avertissement
print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# Fonction pour afficher une information
print_info() {
    echo -e "${CYAN}🔍 $1${NC}"
}

# Vérifier si PHP est installé
check_php() {
    if ! command -v php &> /dev/null; then
        print_error "PHP n'est pas installé ou n'est pas dans le PATH"
        echo
        echo "Veuillez installer PHP:"
        echo "  - Ubuntu/Debian: sudo apt-get install php php-mysql php-json"
        echo "  - CentOS/RHEL: sudo yum install php php-mysql php-json"
        echo "  - macOS: brew install php"
        echo
        exit 1
    fi
    
    print_success "PHP détecté:"
    php --version | head -n 1
    echo
}

# Vérifier si MySQL est accessible
check_mysql() {
    print_info "Vérification de MySQL..."
    if ! command -v mysql &> /dev/null; then
        print_warning "MySQL CLI non détecté (optionnel)"
    else
        print_success "MySQL CLI détecté"
    fi
    echo
}

# Vérifier les fichiers essentiels
check_files() {
    print_info "Vérification des fichiers..."
    
    if [ ! -f "index.php" ]; then
        print_error "index.php non trouvé"
        exit 1
    fi
    
    if [ ! -f "config/database.php" ]; then
        print_error "config/database.php non trouvé"
        exit 1
    fi
    
    print_success "Fichiers essentiels présents"
    echo
}

# Créer les dossiers nécessaires
create_directories() {
    print_info "Création des dossiers..."
    
    mkdir -p logs cache uploads
    chmod 755 logs cache uploads
    
    print_success "Dossiers créés"
    echo
}

# Afficher le menu
show_menu() {
    echo -e "${CYAN}🚀 Options de démarrage:${NC}"
    echo
    echo "[1] Lancer le serveur de développement (Port 8000)"
    echo "[2] Lancer le serveur de développement (Port personnalisé)"
    echo "[3] Tester l'installation"
    echo "[4] Ouvrir dans le navigateur seulement"
    echo "[5] Quitter"
    echo
    read -p "Votre choix (1-5): " choice
}

# Démarrer le serveur avec le port par défaut
start_default_server() {
    PORT=8000
    start_server
}

# Démarrer le serveur avec un port personnalisé
start_custom_server() {
    read -p "Entrez le port (ex: 8080): " PORT
    if [ -z "$PORT" ]; then
        PORT=8000
    fi
    start_server
}

# Démarrer le serveur
start_server() {
    echo
    print_info "Démarrage du serveur sur le port $PORT..."
    echo
    
    echo -e "${PURPLE}📍 URLs disponibles:${NC}"
    echo "    - Accueil:          http://localhost:$PORT/"
    echo "    - Test installation: http://localhost:$PORT/test_installation.php"
    echo "    - Administration:   http://localhost:$PORT/admin.php"
    echo "    - Roulette:         http://localhost:$PORT/roulette.php"
    echo "    - Blackjack:        http://localhost:$PORT/blackjack.php"
    echo "    - Gains Passifs:    http://localhost:$PORT/passive_earnings.php"
    echo
    
    echo -e "${YELLOW}🔑 Comptes par défaut:${NC}"
    echo "    - Admin:    admin / password"
    echo "    - Joueur 1: player1 / password"
    echo "    - Joueur 2: player2 / password"
    echo "    - Croupier: croupier / password"
    echo
    
    echo -e "${RED}⚠️  IMPORTANT:${NC}"
    echo "    - Assurez-vous que MySQL est démarré"
    echo "    - Importez database.sql dans votre base de données"
    echo "    - Configurez config/database.php si nécessaire"
    echo
    
    echo -e "${RED}🛑 Pour arrêter le serveur: Ctrl+C${NC}"
    echo
    echo "========================================"
    echo
    
    # Ouvrir automatiquement le navigateur (si disponible)
    if command -v xdg-open &> /dev/null; then
        xdg-open "http://localhost:$PORT/" &> /dev/null &
    elif command -v open &> /dev/null; then
        open "http://localhost:$PORT/" &> /dev/null &
    fi
    
    # Démarrer le serveur PHP
    php -S localhost:$PORT
}

# Tester l'installation
test_installation() {
    echo
    print_info "Test de l'installation..."
    echo
    
    # Vérifier la syntaxe PHP des fichiers principaux
    files_to_check=("index.php" "dashboard.php" "admin.php" "config/database.php")
    
    for file in "${files_to_check[@]}"; do
        if [ -f "$file" ]; then
            if php -l "$file" &> /dev/null; then
                print_success "Syntaxe OK: $file"
            else
                print_error "Erreur de syntaxe: $file"
                php -l "$file"
            fi
        else
            print_warning "Fichier manquant: $file"
        fi
    done
    
    echo
    read -p "Voulez-vous ouvrir le test dans le navigateur ? (o/n): " open_test
    if [[ $open_test =~ ^[Oo]$ ]]; then
        echo "Démarrage du serveur pour le test..."
        
        # Ouvrir le navigateur
        if command -v xdg-open &> /dev/null; then
            xdg-open "http://localhost:8000/test_installation.php" &> /dev/null &
        elif command -v open &> /dev/null; then
            open "http://localhost:8000/test_installation.php" &> /dev/null &
        fi
        
        php -S localhost:8000
    fi
}

# Ouvrir dans le navigateur
open_browser() {
    echo
    print_info "Ouverture dans le navigateur..."
    print_warning "Assurez-vous qu'un serveur web est déjà en cours d'exécution"
    echo
    
    if command -v xdg-open &> /dev/null; then
        xdg-open "http://localhost:8000/"
    elif command -v open &> /dev/null; then
        open "http://localhost:8000/"
    else
        echo "Ouvrez manuellement: http://localhost:8000/"
    fi
}

# Fonction principale
main() {
    # Aller dans le dossier du script
    cd "$(dirname "$0")"
    
    print_title
    
    check_php
    check_mysql
    check_files
    create_directories
    
    show_menu
    
    case $choice in
        1)
            start_default_server
            ;;
        2)
            start_custom_server
            ;;
        3)
            test_installation
            ;;
        4)
            open_browser
            ;;
        5)
            echo
            echo -e "${PURPLE}👋 Merci d'avoir utilisé CakkySino !${NC}"
            echo
            exit 0
            ;;
        *)
            print_warning "Choix invalide, démarrage par défaut..."
            start_default_server
            ;;
    esac
    
    echo
    echo -e "${PURPLE}👋 Merci d'avoir utilisé CakkySino !${NC}"
    echo
}

# Gestion des signaux pour un arrêt propre
trap 'echo -e "\n${YELLOW}🛑 Arrêt du serveur...${NC}"; exit 0' INT TERM

# Exécuter la fonction principale
main "$@"