# Portfolio — version PHP / MySQL (hébergement mutualisé)

Version **PHP + MySQL + JavaScript vanilla**, sans framework : la même application que la version Node (site public + back-office complet), conçue pour tourner sur un hébergement mutualisé gratuit type **InfinityFree**.

```
php/
  index.php            accueil
  projet.php           page projet (/projets/<slug> via .htaccess)
  contact.php          traitement du formulaire de contact
  404.php              page introuvable
  robots.php           robots.txt
  sitemap.php          sitemap.xml
  install.php          création des tables + contenus de départ (à supprimer après usage)
  schema.sql           schéma MySQL (import phpMyAdmin possible)
  inc/                 configuration, base, auth, mailer, gabarits, protection Apache
  admin/               back-office (login, setup, dashboard, CRUD, médias, messages, compte)
  css/ js/ fonts/      assets (repris de la version Node)
  uploads/             médias importés
```

## 1. Mise en ligne sur InfinityFree

1. **Base MySQL** : panneau InfinityFree → *MySQL Databases* → créer une base.
   Notez l'hôte (`sqlXXX.infinityfree.com`), le nom de la base (`if0_XXXXXXX_portfolio`), l'utilisateur (`if0_XXXXXXX`) et le mot de passe.
2. **Fichiers** : envoyer le **contenu** du dossier `php/` à la racine de `htdocs` (FTP ou gestionnaire de fichiers).
   Le dossier `php/` lui-même ne doit pas être téléversé : ce sont ses fichiers qui vont à la racine.
3. **Configuration** : éditer `inc/config.php` → bloc `$CONFIG` : identifiants MySQL et `site_url`.
   (Alternative : créer `inc/config.local.php` qui retourne un tableau, non versionné.)
4. **Installation** : ouvrir `https://votre-site/install.php` → tables et contenus créés.
5. **Compte** : ouvrir `/admin/setup.php`, créer le premier administrateur (10 caractères minimum).
6. **Nettoyage** : supprimer `install.php`, puis renseigner dans *Réglages → SEO* l'URL canonique du site.

Le site est ensuite sur `/` et l'administration sur `/admin/`.

> Si `install.php` ne peut pas créer les tables, importez `schema.sql` via phpMyAdmin, puis relancez `install.php` (il ne fera qu'insérer les contenus de départ).

## 2. Droits des dossiers

- `uploads/` doit être accessible en écriture (InfinityFree le permet par défaut).
- Pas de `composer`, pas de `npm` : aucune dépendance à installer.

## 3. E-mail (Brevo, optionnel)

Dans `inc/config.php`, bloc `mail` :

```php
'mail' => [
    'api_key'    => 'xkeysib-...',   // Brevo → SMTP & API → API Keys
    'from_email' => 'contact@votre-domaine.com', // expéditeur validé dans Brevo
    'from_name'  => 'Portfolio Malick',
    'to_email'   => 'vous@votre-domaine.com',
],
```

Sans clé, les messages restent enregistrés dans *Admin → Messages* (aucune erreur pour le visiteur).

## 4. Sécurité

- Mots de passe hachés avec **bcrypt** (`password_hash`), jamais en clair.
- Sessions PHP natives, cookie `httponly` + `SameSite=Lax`, `secure` automatique en HTTPS, expiration 12 h.
- Jeton **CSRF** sur toutes les écritures de l'administration.
- Anti-bruteforce : 6 tentatives / 15 min par IP ; formulaire public : 5 messages / heure.
- Téléversements contrôlés par MIME (images + PDF), 12 Mo maximum, noms aléatoires.
- `inc/` et les fichiers `.sql` / `.md` sont bloqués par `.htaccess`.

## 5. Notes techniques (ex-version Node/Express, supprimée)

| | Node (Express) | PHP |
| --- | --- | --- |
| Sessions | table MySQL | sessions PHP |
| Hachage | scrypt | bcrypt |
| Médias | multer | `move_uploaded_file` + finfo |
| E-mail | fetch | cURL |
| Base | pool mysql2 | PDO |
| Démarrage | `npm start` | rien à lancer |

Les URL changent légèrement : `/admin/list.php?entity=projects`, `/admin/form.php?entity=projects&id=3`.
Les pages publiques gardent `/`, `/projets/<slug>`, `/robots.txt`, `/sitemap.xml`.

## 6. Sauvegarde

Via phpMyAdmin (*Exporter*) pour la base, et téléchargement du dossier `uploads/` pour les images.
