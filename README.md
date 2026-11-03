# Portfolio — Malick

Portfolio professionnel full-stack avec **back-office complet** : le contenu, les images, les couleurs et les textes se modifient depuis l'administration, sans toucher au code.

- **Site public** : `/`
- **Administration** : `/admin`
- **Première configuration** : `/admin/setup` (le jour où aucun compte n'existe encore)

---

## 1. Démarrage

Prérequis : **Node.js ≥ 20** et **MySQL / MariaDB** (WAMP, XAMPP, MAMP ou un serveur MySQL). La base et ses tables sont créées automatiquement au premier démarrage.

```bash
npm install
cp .env.example .env      # facultatif en local
npm start
```

Renseignez la connexion MySQL dans `.env` (`DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`). Avec WAMP, l'utilisateur est `root` sans mot de passe. Les migrations de `src/migrations/` sont appliquées au démarrage ; `npm run migrate` importe en plus une ancienne base SQLite (`data/portfolio.db`) si elle existe.

Le site démarre sur <http://localhost:3000>. Au premier lancement :

1. Ouvrez <http://localhost:3000/admin/setup>
2. Créez votre compte (e-mail + mot de passe de 10 caractères minimum)
3. Vous êtes redirigé vers le tableau de bord

Cette page de création disparaît automatiquement dès qu'un administrateur existe. **Aucun mot de passe par défaut n'est fourni.**

En développement, `npm run dev` redémarre automatiquement à chaque modification.

---

## 2. Ce qui est administrable

| Module | Contenu modifiable |
| --- | --- |
| **Réglages → Identité** | Nom du site, nom affiché, nom professionnel, nom complet, titre, slogan, disponibilité, localisation, e-mail, téléphone, WhatsApp, photo de profil |
| **Réglages → À propos** | Biographie, vision, objectifs, centres d'intérêt, 3 statistiques |
| **Réglages → Titres des sections** | Tous les sur-titres et titres, l'intro de contact, le bloc CV et le fichier CV (PDF) |
| **Réglages → Apparence** | Couleur d'accent, fond, surfaces, texte, texte secondaire, polices, animations, grain |
| **Réglages → Réseaux** | GitHub, LinkedIn, X, Dribbble, Behance, Instagram, Facebook, YouTube |
| **Réglages → SEO** | Titre, meta description, mots-clés, image de partage, favicon, URL canonique |
| **Réglages → Footer** | Note et copyright (`{year}` est remplacé par l'année courante) |
| **Projets** | Titre, slug, catégorie, année, résumé, description longue, technologies, couverture, galerie, liens démo/dépôt, statut, mise à la une, publication |
| **Compétences** | Nom, catégorie, niveau (jauge), icône, description, publication |
| **Parcours** | Type (expérience / formation / certification / étape), intitulé, organisation, dates, description, lien, document |
| **Services** | Titre, description, numéro, publication |
| **Processus** | Étapes et descriptions |
| **Témoignages** | Nom, fonction, entreprise, message, note, photo |
| **Navigation** | Libellés, cibles, ordre et visibilité des liens du menu |
| **Messages** | Boîte de réception, lu/non lu, archivage, suppression, réponse par e-mail |
| **Médiathèque** | Import (images + PDF), texte alternatif, suppression, sélection depuis les formulaires |
| **Mon compte** | Nom, e-mail, mot de passe |

Chaque liste permet **modifier / publier / dépublier / supprimer**, et l'ordre se règle avec le champ « Ordre d'affichage ».

### Bon à savoir
- Un projet dépublié disparaît du site public **et** sa page détaillée renvoie une 404. Un bouton « Prévisualiser » permet de le voir en privé.
- Une section sans contenu est **masquée automatiquement** sur le site public (pas de bloc vide).
- Les modifications sont visibles immédiatement, sans redéploiement.

---

## 3. Sécurité

- Mots de passe hachés avec **scrypt** + sel aléatoire (jamais en clair).
- Sessions stockées en base, cookie `httpOnly` + `sameSite=lax`, expiration 12 h. Le cookie passe en `secure` quand `NODE_ENV=production`.
- **CSRF** : jeton vérifié sur toutes les écritures de l'administration.
- **Anti-bruteforce** : 6 tentatives de connexion maximum toutes les 15 minutes par IP.
- **Anti-spam** sur le formulaire public : honeypot + délai minimal + limite de 5 messages/heure par IP.
- Routes `/admin` protégées ; aucune donnée sensible exposée dans le HTML public.
- Téléversements limités à 12 Mo, types contrôlés (images, PDF), noms de fichiers aléatoires.

En production, servez le site en **HTTPS** : le cookie de session passe alors automatiquement en `secure`.

---

## 4. Structure du projet

```
src/
  app.js        routes publiques + administration + API médias
  server.js     point d'entrée (écoute du port)
  env.js        lecture du fichier .env
  mailer.js     envoi des notifications via l'API Brevo
  db.js         pool MySQL, migrations, seed initial
  migrations/   fichiers SQL appliqués au démarrage (suivi dans schema_migrations)
  config.js     réglages par défaut + définition des champs du back-office
  entities.js   description des contenus administrables (CRUD générique)
  auth.js       scrypt, sessions, CSRF, limitation de débit
  util.js       helpers (slug, paragraphes, images, formats)
views/
  public/       accueil, page projet, 404
  admin/        dashboard, listes, formulaires, réglages, médias, messages
  partials/     head, navigation, footer
public/
  css/          site.css + admin.css
  js/           site.js + admin.js
  fonts/        polices auto-hébergées (aucun appel à Google)
  uploads/      médias importés
data/           ancienne base SQLite (lue uniquement par `npm run migrate`)
```

Pour ajouter un champ éditable : déclarez-le dans `src/config.js` (réglage global) ou `src/entities.js` (contenu). Le formulaire est généré automatiquement.

---

## 5. Déploiement

L'application est un serveur Node classique (pas de build). Elle fonctionne sur un VPS, Render, Railway, Fly.io, Clever Cloud, etc.

```bash
NODE_ENV=production
PORT=3000
SITE_URL=https://votre-domaine.com
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USER=...
DB_PASSWORD=...
DB_NAME=malick_portfolio
BREVO_API_KEY=xkeysib-...
```

> Aucun secret de session n'est nécessaire : chaque session porte un jeton aléatoire de 32 octets stocké en base.

Points d'attention :
- Un serveur **MySQL / MariaDB** joignable depuis l'hébergeur (variables `DB_*`).
- **Disque persistant obligatoire** pour `public/uploads/` (les médias ne sont pas en base ; les plateformes serverless à système de fichiers éphémère ne conviennent pas).
- Après le déploiement, visitez `/admin/setup` pour créer le compte de production, puis renseignez l'URL canonique dans *Réglages → SEO* (utilisée par `sitemap.xml`, `robots.txt` et les balises Open Graph).

Sauvegarde : `mysqldump malick_portfolio > backup.sql` et copie du dossier `public/uploads/`.

---

## 6. E-mail — Brevo

Chaque message du formulaire est **toujours enregistré en base** (visible dans *Admin → Messages*). En plus, une notification est envoyée par e-mail via l'API Brevo.

### Configuration (une seule fois)

1. Créer un compte sur <https://app.brevo.com>
2. **Senders & IP → Add a sender** : valider l'adresse expéditrice (obligatoire, sinon Brevo refuse l'envoi)
3. **SMTP & API → API Keys → Generate a new API key** (droits « Transactional email »)
4. Copier `.env.example` en `.env` et remplir **uniquement** ces quatre lignes :

```ini
BREVO_API_KEY=xkeysib-...
MAIL_FROM_EMAIL=contact@votre-domaine.com   # adresse validée dans Brevo
MAIL_FROM_NAME=Portfolio Malick
MAIL_TO_EMAIL=vous@votre-domaine.com        # où vous recevez les messages
```

5. Tester sans passer par le site :

```bash
npm run mail:test
```

Si la configuration est bonne, le script affiche `✓ E-mail envoyé` et un identifiant Brevo. En cas d'erreur, il indique la cause (`401` = clé invalide, `400` = expéditeur non validé).

### Comportement

- Aucune clé configurée → le site fonctionne normalement, une ligne `E-mail → Brevo non configuré` s'affiche au démarrage et les messages restent consultables dans l'admin.
- Brevo indisponible → le message est sauvegardé, l'utilisateur voit quand même la confirmation, l'erreur est écrite dans les logs du serveur.
- La réponse à l'e-mail se fait directement sur l'adresse du visiteur (`replyTo`), pas sur celle du portfolio.
- Le code est dans `src/mailer.js` : rien d'autre à modifier que les clés.

---

## 7. Choix assumés et limites

- **Le contenu n'est pas inventé.** L'identité (nom affiché « Malick », titre, localisation) et les textes de présentation sont préremplis ; projets, compétences, parcours et témoignages sont **volontairement vides** et se remplissent depuis l'administration. Le dashboard affiche une liste de contrôle pour suivre ce qu'il reste à compléter.
- **E-mail optionnel** : sans clé Brevo, les messages sont uniquement stockés en base. Avec les clés, la notification part automatiquement (voir section 6).
- **MySQL** : le contenu et les sessions vivent en base, donc plusieurs instances Node peuvent tourner derrière un load balancer sans état partagé.
- **Détails d'animation** : GSAP n'est pas utilisé — les animations sont en CSS + IntersectionObserver, donc sans dépendance ni coût de chargement. `prefers-reduced-motion` et le réglage *Apparence → Animations* sont respectés.
