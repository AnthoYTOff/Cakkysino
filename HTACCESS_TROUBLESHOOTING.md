# Guide de dépannage .htaccess pour CakkySino

## Problèmes identifiés

Les erreurs Apache que vous rencontrez sont causées par l'utilisation de directives obsolètes dans le fichier `.htaccess`. Voici les principales corrections apportées :

### 1. Directives d'autorisation obsolètes

**Problème :** Utilisation de `Order Allow,Deny` et `Allow/Deny from all` (syntaxe Apache 2.2)

**Solution :** Remplacement par `Require all granted/denied` (syntaxe Apache 2.4+)

```apache
# Ancien (cause des erreurs)
<Files "database.sql">
    Order Allow,Deny
    Deny from all
</Files>

# Nouveau (compatible)
<Files "database.sql">
    Require all denied
</Files>
```

### 2. Directives de configuration serveur

**Problème :** `ServerTokens` et `ServerSignature` ne peuvent pas être utilisées dans `.htaccess`

**Solution :** Ces directives doivent être configurées dans `httpd.conf` ou `apache2.conf`

### 3. Configuration PHP

**Problème :** `mod_php.c` peut ne pas être disponible avec PHP-FPM

**Solution :** Utilisation de `mod_php7.c` ou configuration via `php.ini`

## Fichiers créés

1. **`.htaccess`** - Version corrigée avec syntaxe Apache 2.4+
2. **`.htaccess.backup`** - Version de sauvegarde simplifiée et compatible

## Solutions selon votre configuration serveur

### Option 1: Serveur partagé/mutualisé

Utilisez le fichier `.htaccess.backup` qui contient uniquement les directives essentielles :

```bash
# Remplacer le fichier actuel
cp .htaccess.backup .htaccess
```

### Option 2: Serveur dédié/VPS

Vous pouvez utiliser le fichier `.htaccess` corrigé, mais assurez-vous que :

1. Apache 2.4+ est installé
2. Les modules suivants sont activés :
   - `mod_rewrite`
   - `mod_headers`
   - `mod_deflate`
   - `mod_expires`
   - `mod_mime`

### Option 3: Configuration minimale

Si vous continuez à avoir des erreurs, utilisez cette configuration minimale :

```apache
# Configuration minimale .htaccess
RewriteEngine On
DirectoryIndex index.php

# Protection fichiers sensibles
<Files "database.sql">
    Require all denied
</Files>

<Files "*.log">
    Require all denied
</Files>

# Protection dossier config
<Directory "config">
    Require all denied
</Directory>

# Désactiver l'affichage des dossiers
Options -Indexes

# Pages d'erreur
ErrorDocument 404 /error.php?code=404
ErrorDocument 500 /error.php?code=500
```

## Vérification de la configuration

### 1. Tester la syntaxe Apache

```bash
# Sur votre serveur
apache2ctl configtest
# ou
httpd -t
```

### 2. Vérifier les logs d'erreur

```bash
# Logs Apache
tail -f /var/log/apache2/error.log
# ou
tail -f /var/log/httpd/error_log
```

### 3. Tester les modules Apache

```bash
# Lister les modules chargés
apache2ctl -M
# ou
httpd -M
```

## Configuration recommandée par environnement

### Développement local
- Utilisez `.htaccess` complet pour tester toutes les fonctionnalités
- Activez `display_errors` dans PHP

### Production
- Utilisez `.htaccess.backup` pour plus de stabilité
- Configurez les optimisations dans `httpd.conf` plutôt que `.htaccess`
- Désactivez `display_errors` dans PHP

### Serveur partagé
- Utilisez la configuration minimale
- Testez chaque directive une par une
- Contactez votre hébergeur pour les modules disponibles

## Dépannage avancé

### Si les erreurs persistent :

1. **Commentez toutes les directives** et ajoutez-les une par une
2. **Vérifiez la version d'Apache** : `apache2 -v`
3. **Testez sans .htaccess** temporairement
4. **Consultez la documentation de votre hébergeur**

### Modules requis pour CakkySino :

- `mod_rewrite` (obligatoire)
- `mod_headers` (recommandé)
- `mod_deflate` (optionnel)
- `mod_expires` (optionnel)
- `mod_mime` (optionnel)

## Contact support

Si vous continuez à rencontrer des problèmes :

1. Fournissez la version d'Apache : `apache2 -v`
2. Listez les modules chargés : `apache2ctl -M`
3. Partagez les logs d'erreur complets
4. Indiquez votre type d'hébergement (partagé/dédié/VPS)

---

**Note :** Les corrections apportées rendent le fichier `.htaccess` compatible avec Apache 2.4+ tout en maintenant la sécurité et les performances de CakkySino.