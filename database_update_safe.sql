-- Script de mise à jour SÉCURISÉ pour la base de données CakkySino
-- Version pour hébergements avec privilèges limités
-- Exécutez les commandes une par une si nécessaire

USE rsneay_cakkysin_db;

-- Étape 1: Ajouter la colonne last_login
-- Copiez et exécutez cette commande séparément si nécessaire
-- Si vous obtenez une erreur "Duplicate column name", c'est que la colonne existe déjà
ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL AFTER last_activity;

-- Étape 2: Ajouter l'index pour last_login
-- Copiez et exécutez cette commande séparément si nécessaire
-- Si vous obtenez une erreur "Duplicate key name", c'est que l'index existe déjà
ALTER TABLE users ADD INDEX idx_last_login (last_login);

-- Étape 3: Vérification de la structure
-- Cette commande devrait toujours fonctionner
DESCRIBE users;

-- Étape 4: Test de la colonne
-- Vérifier que la colonne last_login est bien présente
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'rsneay_cakkysin_db' 
AND TABLE_NAME = 'users' 
AND COLUMN_NAME = 'last_login';

-- Si la requête ci-dessus ne fonctionne pas à cause des privilèges,
-- utilisez cette alternative simple:
-- SELECT id, username, last_login FROM users LIMIT 1;

/*
INSTRUCTIONS D'UTILISATION:

1. Si vous avez accès à phpMyAdmin ou un outil similaire:
   - Copiez tout le contenu de ce fichier
   - Collez-le dans l'onglet SQL
   - Exécutez le script

2. Si vous avez des erreurs de privilèges:
   - Exécutez les commandes ALTER TABLE une par une
   - Ignorez les erreurs "Duplicate column" ou "Duplicate key"

3. Pour vérifier que tout fonctionne:
   - Exécutez: DESCRIBE users;
   - Vous devriez voir la colonne 'last_login' dans la liste

4. Test final:
   - Essayez de vous connecter à CakkySino
   - L'erreur "Unknown column 'last_login'" ne devrait plus apparaître
*/