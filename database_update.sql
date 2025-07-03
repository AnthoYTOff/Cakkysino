-- Script de mise à jour de la base de données CakkySino
-- À exécuter si vous avez déjà créé la base de données sans la colonne last_login
-- Version simplifiée sans accès à information_schema

USE rsneay_cakkysin_db;

-- Tentative d'ajout de la colonne last_login
-- Si la colonne existe déjà, l'erreur sera ignorée
ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL AFTER last_activity;

-- Ajouter l'index pour la colonne last_login
-- Si l'index existe déjà, l'erreur sera ignorée
ALTER TABLE users ADD INDEX idx_last_login (last_login);

-- Message de confirmation
SELECT 'Mise à jour terminée. Si des erreurs "Duplicate column" apparaissent, c\'est normal - la colonne existe déjà.' as message;

-- Vérifier la structure finale de la table users
DESCRIBE users;