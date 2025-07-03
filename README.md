# 🎰 CakkySino - Casino en Ligne

Un système de casino en ligne complet développé en PHP avec MySQL, offrant des jeux de hasard, des gains passifs et une interface d'administration complète.

## 🎮 Fonctionnalités

### Jeux Disponibles
- **🎲 Roulette** : Jeu de roulette automatique multi-joueurs avec différents types de paris
- **♠️ Blackjack** : Jeu de blackjack avec croupier humain (administrateur)
- **💰 Gains Passifs** : Système de gains automatiques avec détection anti-triche

### Système de Gestion
- **👥 Gestion des Utilisateurs** : Inscription, connexion, profils utilisateur
- **🏦 Banque du Casino** : Gestion automatique des fonds et statistiques
- **📊 Statistiques Complètes** : Suivi détaillé des performances et activités
- **🔧 Interface d'Administration** : Contrôle total du casino pour les administrateurs

### Sécurité
- **🔐 Authentification Sécurisée** : Hachage des mots de passe avec bcrypt
- **🛡️ Protection Anti-Triche** : Détection d'inactivité pour les gains passifs
- **📝 Logs d'Audit** : Enregistrement de toutes les actions administratives
- **⚡ Validation des Données** : Vérification côté serveur et client

## 🚀 Installation

### Prérequis
- **PHP 7.4+** avec extensions :
  - PDO MySQL
  - JSON
  - Session
- **MySQL 5.7+** ou **MariaDB 10.2+**
- **Serveur Web** (Apache, Nginx, ou serveur de développement PHP)

### Étapes d'Installation

1. **Cloner ou télécharger le projet**
   ```bash
   git clone <repository-url> cakkysino
   cd cakkysino
   ```

2. **Configurer la base de données**
   - Créer une base de données MySQL nommée `cakkysino`
   - Importer le fichier `database.sql` :
   ```bash
   mysql -u username -p cakkysino < database.sql
   ```

3. **Configurer la connexion à la base de données**
   - Modifier le fichier `config/database.php`
   - Ajuster les paramètres de connexion :
   ```php
   private $host = 'localhost';
   private $db_name = 'cakkysino';
   private $username = 'your_username';
   private $password = 'your_password';
   ```

4. **Configurer le serveur web**
   - Pointer le document root vers le dossier du projet
   - S'assurer que les permissions sont correctes
   - Activer la réécriture d'URL si nécessaire

5. **Accéder à l'application**
   - Ouvrir votre navigateur et aller à `http://localhost/cakkysino`
   - Utiliser les comptes par défaut :
     - **Admin** : `admin` / `password`
     - **Joueur 1** : `player1` / `password`
     - **Joueur 2** : `player2` / `password`
     - **Croupier** : `croupier` / `password`

## 📁 Structure du Projet

```
cakkysino/
├── 📄 index.php              # Page d'accueil et authentification
├── 📄 dashboard.php          # Tableau de bord principal
├── 📄 admin.php              # Interface d'administration
├── 📄 roulette.php           # Page du jeu de roulette
├── 📄 blackjack.php          # Page du jeu de blackjack
├── 📄 passive_earnings.php   # Page des gains passifs
├── 📄 admin_blackjack.php    # Gestion des parties de blackjack
├── 📄 database.sql           # Structure de la base de données
├── 📄 README.md              # Documentation du projet
│
├── 📁 config/
│   └── 📄 database.php       # Configuration de la base de données
│
├── 📁 classes/
│   ├── 📄 User.php           # Gestion des utilisateurs
│   ├── 📄 CasinoBank.php     # Gestion de la banque du casino
│   ├── 📄 PassiveEarnings.php # Système de gains passifs
│   ├── 📄 Roulette.php       # Logique du jeu de roulette
│   └── 📄 Blackjack.php      # Logique du jeu de blackjack
│
├── 📁 api/
│   ├── 📄 user.php           # API utilisateur
│   ├── 📄 passive_earnings.php # API gains passifs
│   ├── 📄 roulette.php       # API roulette
│   ├── 📄 blackjack.php      # API blackjack
│   └── 📄 admin_logs.php     # API logs d'administration
│
└── 📁 assets/
    ├── 📁 css/
    │   └── 📄 style.css       # Styles CSS principaux
    └── 📁 js/
        └── 📄 dashboard.js    # Scripts JavaScript
```

## 🎯 Guide d'Utilisation

### Pour les Joueurs

1. **Inscription/Connexion**
   - Créer un compte ou se connecter avec un compte existant
   - Chaque nouveau compte reçoit 1000 coins de départ

2. **Gains Passifs**
   - Aller dans la section "Gains Passifs"
   - Cliquer sur "Démarrer" pour commencer à gagner des coins
   - Rester actif pour maximiser les gains (détection anti-triche)

3. **Jeu de Roulette**
   - Rejoindre une partie en cours ou attendre qu'une nouvelle commence
   - Placer des mises sur les numéros ou les couleurs
   - Les parties se lancent automatiquement après 30 secondes

4. **Jeu de Blackjack**
   - Rejoindre une table créée par un administrateur
   - Placer sa mise et attendre que la partie commence
   - Utiliser les boutons "Tirer", "Rester" ou "Doubler"

### Pour les Administrateurs

1. **Gestion de la Banque**
   - Ajuster le solde de la banque du casino
   - Consulter les statistiques financières
   - Voir l'historique des transactions

2. **Gestion des Utilisateurs**
   - Voir la liste de tous les utilisateurs
   - Consulter leur statut en ligne et leurs statistiques
   - Gérer les comptes si nécessaire

3. **Gestion des Jeux**
   - **Roulette** : Forcer le lancement d'une partie
   - **Blackjack** : Créer des tables, gérer les parties en tant que croupier

4. **Logs et Statistiques**
   - Consulter les logs d'activité
   - Voir les statistiques détaillées du casino
   - Analyser les performances des jeux

## 🔧 Configuration Avancée

### Paramètres des Gains Passifs
- **Taux de base** : 1 coin par minute
- **Bonus d'activité** : Jusqu'à +50% pour les utilisateurs très actifs
- **Pénalité d'inactivité** : -30% pour les utilisateurs inactifs
- **Vérification d'activité** : Toutes les 30 secondes

### Paramètres de la Roulette
- **Lancement automatique** : 30 secondes après la première mise
- **Types de paris** : Numéros (35:1), Couleurs (1:1), Pairs/Impairs (1:1), etc.
- **Limite de joueurs** : Illimitée par partie

### Paramètres du Blackjack
- **Limite de joueurs** : 6 par table
- **Mise minimum** : 10 coins
- **Paiement Blackjack** : 3:2
- **Paiement victoire normale** : 1:1

## 🛠️ Développement

### Architecture
- **Frontend** : HTML5, CSS3, JavaScript (Vanilla)
- **Backend** : PHP 7.4+ avec architecture orientée objet
- **Base de données** : MySQL avec procédures stockées et triggers
- **API** : REST API en JSON pour les interactions AJAX

### Fonctionnalités Techniques
- **Transactions atomiques** pour la cohérence des données
- **Système de cache** pour les statistiques
- **Validation côté client et serveur**
- **Gestion d'erreurs robuste**
- **Logs détaillés** pour le débogage

### Sécurité Implémentée
- **Protection CSRF** via tokens de session
- **Validation et échappement** de toutes les entrées
- **Hachage sécurisé** des mots de passe
- **Limitation des tentatives** de connexion
- **Audit trail** complet

## 📊 Base de Données

### Tables Principales
- **users** : Informations des utilisateurs
- **casino_bank** : Solde et statistiques de la banque
- **coin_history** : Historique de toutes les transactions
- **roulette_games** / **roulette_bets** : Données de la roulette
- **blackjack_games** / **blackjack_hands** : Données du blackjack
- **passive_earnings** : Système de gains passifs
- **admin_logs** : Logs d'administration

### Optimisations
- **Index composites** pour les requêtes fréquentes
- **Vues matérialisées** pour les statistiques
- **Procédures stockées** pour les opérations complexes
- **Triggers** pour la cohérence des données
- **Événements planifiés** pour la maintenance

## 🐛 Dépannage

### Problèmes Courants

1. **Erreur de connexion à la base de données**
   - Vérifier les paramètres dans `config/database.php`
   - S'assurer que MySQL est démarré
   - Vérifier les permissions de l'utilisateur MySQL

2. **Les gains passifs ne fonctionnent pas**
   - Vérifier que JavaScript est activé
   - Ouvrir la console du navigateur pour voir les erreurs
   - S'assurer que l'utilisateur reste actif sur la page

3. **Les jeux ne se lancent pas**
   - Vérifier les logs d'erreur PHP
   - S'assurer que les tables de la base de données existent
   - Vérifier les permissions des fichiers

4. **Interface d'administration inaccessible**
   - Vérifier que l'utilisateur a les droits administrateur
   - Se connecter avec le compte `admin` par défaut
   - Vérifier les logs de session

### Logs et Débogage
- **Logs PHP** : Vérifier les logs d'erreur du serveur web
- **Logs MySQL** : Consulter les logs de requêtes lentes
- **Console navigateur** : Vérifier les erreurs JavaScript
- **Logs d'administration** : Consulter via l'interface admin

## 📝 Licence

Ce projet est développé à des fins éducatives et de démonstration. Veuillez respecter les lois locales concernant les jeux d'argent en ligne.

## 🤝 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à :
- Signaler des bugs
- Proposer de nouvelles fonctionnalités
- Améliorer la documentation
- Optimiser le code

## 📞 Support

Pour toute question ou problème :
1. Consulter cette documentation
2. Vérifier les logs d'erreur
3. Rechercher dans les issues existantes
4. Créer une nouvelle issue si nécessaire

---

**CakkySino** - Un casino en ligne moderne et sécurisé ! 🎰✨