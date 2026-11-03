# Déploiement — hébergement mutualisé (InfinityFree)

L'application est à la racine du dépôt : ce sont **ces fichiers** qui vont dans `htdocs`.
Rien à compiler, aucune dépendance à installer (pas de Composer, pas de npm).

## 1. Créer la base MySQL

1. Client area InfinityFree → **Control Panel**.
2. *Databases* → **MySQL Databases** → nom : `portfolio` → **Create Database**.
3. Relever dans *MySQL Details* : hôte (`sqlXXX.infinityfree.com`), nom complet de la base, utilisateur, mot de passe.

> L'hôte n'est jamais `localhost`. La base est créée par l'hébergeur : ne pas créer les tables à la main.

## 2. Configurer l'application

Éditer `inc/config.php`, bloc `$CONFIG` :

```php
'db' => [
    'host'     => 'sqlXXX.infinityfree.com',
    'port'     => 3306,
    'name'     => 'if0_XXXXXXXX_portfolio',
    'user'     => 'if0_XXXXXXXX',
    'password' => 'mot-de-passe-de-la-base',
],
'site_url' => 'https://ton-sous-domaine.infinityfreeapp.com',
```

Le bloc `mail` peut rester vide : les messages sont alors seulement enregistrés dans l'admin.

> `inc/config.local.php` (non versionné) écrase `inc/config.php` s'il est présent.
> C'est le fichier des identifiants locaux : **ne pas le téléverser** sur le serveur.

## 3. Envoyer les fichiers

Par FTP (FileZilla) :

- Hôte `ftpupload.net`, port `21`, authentification **Normal**
- Identifiant `if0_XXXXXXXX`, mot de passe du compte d'hébergement
- Destination : **`htdocs`**
- Activer *Serveur → Forcer l'affichage des fichiers cachés* (sinon `.htaccess` n'est pas envoyé)

Fichiers à déposer dans `htdocs` : tout le contenu du dépôt **sauf** `data/`, `export-contenus.sql`, `.git/` et `inc/config.local.php`.

Vérifier après l'envoi que `htdocs/index.php` et `htdocs/inc/.htaccess` existent (fichier caché).

## 4. Importer les contenus

phpMyAdmin (bouton *Admin* à côté de la base) → sélectionner la base → onglet **Importer** →
fichier `export-contenus.sql`, jeu de caractères `utf-8` → **Exécuter**.

Le fichier contient la structure (`CREATE TABLE IF NOT EXISTS`) **et** les données :
12 tables, 67 réglages, projets, services, processus, navigation, médias, messages.

Importer dans une base **vide** : les `INSERT` échouent sur les clés uniques en cas de double import.

Sans export à importer : ouvrir `/install.php` (crée les tables et les contenus d'exemple), puis le supprimer.

## 5. Premiers pas en ligne

1. `https://ton-sous-domaine/` → le portfolio s'affiche.
2. `/admin/setup.php` → créer le compte administrateur (10 caractères minimum).
3. *Médiathèque* → vérifier que les images de `uploads/` sont visibles.
4. *Réglages → SEO* → renseigner l'URL canonique (sitemap, robots.txt, Open Graph).
5. Supprimer `install.php` du serveur.

## 6. HTTPS

Client area → **Free SSL Certificates** → ajouter le domaine → créer les enregistrements CNAME demandés →
demander puis installer le certificat. Le cookie de session passe automatiquement en `secure` en HTTPS.

## 7. Dépannage

| Symptôme | Cause | Correctif |
|---|---|---|
| Liste de fichiers au lieu du site | fichiers dans `htdocs/<dossier>/` | renvoyer le contenu à la racine de `htdocs` |
| Erreur 500 | `Options -Indexes` refusé, ou PHP < 8.0 | commenter `Options -Indexes` dans `.htaccess` ; vérifier la version PHP (8.2/8.3 conseillées) |
| « Connexion impossible » | `inc/config.php` non modifié, ou `config.local.php` envoyé | corriger les identifiants, supprimer `config.local.php` du serveur |
| `/projets/<slug>` en 404 | `.htaccess` absent | renvoyer le fichier caché ; `projet.php?slug=...` reste utilisable |
| Images cassées | `uploads/` manquant ou non inscriptible | envoyer les images, permissions 755 |
| « Fichier trop volumineux » | limite PHP de l'hébergeur (< 12 Mo) | compresser l'image avant l'envoi |
| Aucun e-mail reçu | Brevo non configuré ou requêtes sortantes filtrées | les messages restent dans *Admin → Messages* |

## 8. Mises à jour et sauvegarde

- **Mise à jour** : renvoyer uniquement les fichiers modifiés par FTP (jamais `inc/config.php`, qui contient les identifiants du serveur).
- **Sauvegarde** : phpMyAdmin → *Exporter* pour la base, téléchargement de `uploads/` pour les images.
