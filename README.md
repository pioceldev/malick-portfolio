# Portfolio — Malick

Portfolio professionnel avec **back-office complet** : contenu, images, couleurs et textes se modifient depuis l'administration, sans toucher au code.

**Stack : PHP 8 + MySQL + JavaScript vanilla, sans framework ni dépendance** — conçu pour un hébergement mutualisé (InfinityFree, cPanel, WAMP en local).

- **Site public** : `/`
- **Administration** : `/admin/`
- **Première configuration** : `/admin/setup.php` (tant qu'aucun compte n'existe)
- **Installation de la base** : `/install.php` (une seule fois, puis à supprimer)
- **Mise en ligne** : voir **[`DEPLOIEMENT.md`](DEPLOIEMENT.md)**

---

## 1. Structure

Tout le code est à la racine du dépôt : ce sont ces fichiers qui vont dans `htdocs` chez l'hébergeur.

```
index.php            accueil
projet.php           page projet (/projets/<slug>)
contact.php          traitement du formulaire de contact
404.php              page introuvable
robots.php           robots.txt
sitemap.php          sitemap.xml
install.php          création des tables + contenus de départ
schema.sql           schéma MySQL (import phpMyAdmin possible)
.htaccess            réécritures d'URL et protections Apache
inc/                 configuration, PDO, auth, e-mail, gabarits, .htaccess de protection
admin/               back-office (dashboard, CRUD, réglages, médias, messages, compte)
css/ js/ fonts/      assets (aucun appel externe)
uploads/             médias importés
data/                archive de l'ancienne base SQLite (non utilisée par l'application)
```

## 2. Démarrage local (WAMP / XAMPP)

1. Démarrer Apache et MySQL.
2. Créer la base : `malick_portfolio` (ou utiliser celle qui existe déjà).
3. Renseigner `inc/config.php` (bloc `$CONFIG`) ou créer `inc/config.local.php` — non versionné — qui retourne un tableau :

```php
<?php
return [
    'db' => ['host' => '127.0.0.1', 'port' => 3306, 'name' => 'malick_portfolio', 'user' => 'root', 'password' => ''],
    'site_url' => 'http://localhost/portfolio',
];
```

4. Ouvrir `http://localhost/portfolio/install.php`, puis `/admin/setup.php` pour créer le compte.

Sans Apache, le serveur intégré suffit : `php -S 127.0.0.1:8000 -t .`.

## 3. Mise en ligne

Procédure détaillée : **[`DEPLOIEMENT.md`](DEPLOIEMENT.md)**.

Résumé : créer la base MySQL chez l'hébergeur → envoyer les fichiers dans `htdocs` → renseigner `inc/config.php` → importer `export-contenus.sql` (ou lancer `install.php`) → créer le compte via `/admin/setup.php` → supprimer `install.php` → renseigner l'URL canonique dans *Réglages → SEO*.

## 4. Ce qui est administrable

| Module | Contenu |
| --- | --- |
| **Réglages → Identité** | Nom du site, nom affiché, nom professionnel, titre, slogan, disponibilité, localisation, e-mail, téléphone, WhatsApp, photo |
| **Réglages → À propos** | Biographie, vision, objectifs, centres d'intérêt, 3 statistiques |
| **Réglages → Titres des sections** | Sur-titres, titres, intro de contact, bloc CV et fichier CV (PDF) |
| **Réglages → Apparence** | Couleurs (accent, fond, surfaces, textes), polices, animations, grain |
| **Réglages → Réseaux / SEO / Footer** | Liens sociaux, métadonnées, image de partage, favicon, URL canonique, copyright |
| **Projets** | Titre, slug, catégorie, année, résumé, description, technologies, couverture, galerie, liens, statut, une, publication |
| **Compétences / Parcours / Services / Processus / Témoignages** | Contenus, niveaux, icônes, dates, notes, documents, ordre |
| **Navigation** | Libellés, cibles, ordre, visibilité |
| **Messages** | Boîte de réception, lu/non lu, archivage, suppression, réponse e-mail |
| **Médiathèque** | Import images + PDF, texte alternatif, suppression, sélection dans les formulaires |
| **Mon compte** | Nom, e-mail, mot de passe |

Une section sans contenu est masquée automatiquement ; un projet dépublié renvoie une 404 (aperçu privé disponible dans l'admin).

## 5. Sécurité

- Mots de passe hachés avec **bcrypt** (`password_hash`), jamais en clair.
- Sessions PHP natives, cookie `httpOnly` + `SameSite=Lax`, `secure` automatique en HTTPS, expiration 12 h.
- Jeton **CSRF** sur toutes les écritures de l'administration.
- Anti-bruteforce : 6 tentatives / 15 min par IP ; formulaire public : 5 messages / heure + honeypot + délai minimal.
- Téléversements limités à 12 Mo, types contrôlés par MIME (images, PDF), noms aléatoires.
- `inc/`, `schema.sql` et les fichiers de configuration sont bloqués par `.htaccess`.

## 6. E-mail (Brevo, optionnel)

Bloc `mail` de `inc/config.php` : `api_key`, `from_email` (expéditeur validé dans Brevo), `from_name`, `to_email`.

Sans clé, le site fonctionne : chaque message reste consultable dans *Admin → Messages*.

## 7. Sauvegarde

Export de la base via phpMyAdmin, et copie du dossier `uploads/`.

---

> L'ancienne implémentation Node.js/Express (dossier `src/`, vues EJS, `package.json`) a été supprimée. La version PHP reprend l'intégralité des fonctionnalités ; `data/portfolio.db` est conservée uniquement comme archive.
