<?php
declare(strict_types=1);

/**
 * Installation : crée les tables (schema.sql) et les contenus de départ.
 * Idempotent — peut être relancé sans risque. Supprimez ce fichier après usage.
 */

require __DIR__ . '/inc/bootstrap.php';

$steps = [];
$failed = null;

try {
    db();
    $steps[] = 'Connexion à la base MySQL établie (' . e((string) cfg('db.name')) . ').';
    sql_exec('SELECT 1');
} catch (Throwable $error) {
    $failed = 'Connexion impossible : ' . $error->getMessage();
}

if ($failed === null) {
    try {
        $schema = (string) file_get_contents(__DIR__ . '/schema.sql');
        $schema = (string) preg_replace('/^\s*--.*$/m', '', $schema);
        $statements = preg_split('/;\s*\n/', $schema) ?: [];

        $created = 0;
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement === '') {
                continue;
            }
            db()->exec($statement);
            $created++;
        }
        $steps[] = $created . ' instruction(s) SQL exécutée(s) — tables à jour.';
    } catch (Throwable $error) {
        $failed = 'Création des tables impossible : ' . $error->getMessage();
    }
}

if ($failed === null) {
    try {
        // Réglages par défaut
        if ((int) val('SELECT COUNT(*) FROM settings', [], 0) === 0) {
            foreach (DEFAULT_SETTINGS as $key => $value) {
                setting_save((string) $key, (string) $value);
            }
            $steps[] = count(DEFAULT_SETTINGS) . ' réglages par défaut insérés.';
        } else {
            $steps[] = 'Réglages déjà présents — rien à insérer.';
        }

        // Navigation
        if ((int) val('SELECT COUNT(*) FROM nav_items', [], 0) === 0) {
            $items = [
                ['Projets', '#projets'],
                ['À propos', '#apropos'],
                ['Parcours', '#parcours'],
                ['Services', '#services'],
                ['Contact', '#contact'],
            ];
            foreach ($items as $index => [$label, $href]) {
                sql_exec('INSERT INTO nav_items (label, href, sort) VALUES (?, ?, ?)', [$label, $href, $index]);
            }
            $steps[] = count($items) . ' liens de navigation créés.';
        }

        // Services
        if ((int) val('SELECT COUNT(*) FROM services', [], 0) === 0) {
            $items = [
                ['Sites web sur mesure', 'Sites vitrines, portfolios et plateformes éditoriales, rapides et bien référencés.', '01'],
                ['Applications web', 'Interfaces applicatives complètes : authentification, tableaux de bord, API.', '02'],
                ['Conception UI/UX', 'Recherche utilisateur, wireframes, maquettes Figma et design systems.', '03'],
                ['Intégration de maquettes', 'Découpage pixel-perfect de vos designs, animations et responsive.', '04'],
                ['Solutions personnalisées', 'Automatisations, intégrations d’API et outils métiers internes.', '05'],
            ];
            foreach ($items as $index => [$title, $description, $icon]) {
                sql_exec(
                    'INSERT INTO services (title, description, icon, sort) VALUES (?, ?, ?, ?)',
                    [$title, $description, $icon, $index]
                );
            }
            $steps[] = count($items) . ' services créés.';
        }

        // Processus
        if ((int) val('SELECT COUNT(*) FROM process_steps', [], 0) === 0) {
            $items = [
                ['Découverte du besoin', 'On clarifie l’objectif, le public et les contraintes. Questions, pas de suppositions.'],
                ['Analyse & planification', 'Périmètre, priorités, technologies et calendrier. Un plan réaliste, écrit.'],
                ['Conception UI/UX', 'Structure, parcours et maquettes. On valide avant d’écrire la première ligne de code.'],
                ['Développement', 'Code propre, composants réutilisables, points d’étape réguliers.'],
                ['Tests & optimisation', 'Responsive, accessibilité, performance, sécurité.'],
                ['Livraison', 'Mise en ligne, documentation et accompagnement après lancement.'],
            ];
            foreach ($items as $index => [$title, $description]) {
                sql_exec(
                    'INSERT INTO process_steps (title, description, sort) VALUES (?, ?, ?)',
                    [$title, $description, $index]
                );
            }
            $steps[] = count($items) . ' étapes de processus créées.';
        }

        $steps[] = 'Comptes administrateurs existants : ' . count_users() . '.';
    } catch (Throwable $error) {
        $failed = 'Insertion des contenus de départ impossible : ' . $error->getMessage();
    }
}

if ($failed === null) {
    try {
        uploads_dir_check();
        $steps[] = 'Dossier des médias prêt (droits en écriture).';
    } catch (Throwable $error) {
        $failed = $error->getMessage();
    }
}

function uploads_dir_check(): void
{
    $dir = (string) cfg('uploads_dir');
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
        throw new RuntimeException('Impossible de créer le dossier ' . $dir);
    }
    if (!is_writable($dir)) {
        throw new RuntimeException('Le dossier ' . $dir . ' n’est pas accessible en écriture.');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Installation — Portfolio</title>
  <link rel="stylesheet" href="/css/admin.css">
</head>
<body class="auth-body">
  <div class="auth" style="grid-template-columns:1fr">
    <section class="auth__form" style="max-width:720px;margin:0 auto">
      <p class="auth__kicker">Installation</p>
      <h1 style="font-size:26px;margin:8px 0 18px">Mise en place de la base</h1>

      <?php if ($failed !== null): ?>
        <div class="alert alert--error" role="alert"><strong>Échec :</strong> <?= e($failed) ?></div>
        <p class="muted">Vérifiez les identifiants MySQL dans <code>inc/config.php</code> (hôte, base, utilisateur, mot de passe fournis par l’hébergeur).</p>
      <?php else: ?>
        <div class="alert alert--ok" role="status">Installation terminée.</div>
        <ul class="help-list">
          <?php foreach ($steps as $step): ?><li><?= $step ?></li><?php endforeach; ?>
        </ul>
        <p style="margin-top:20px">
          <a class="btn btn--primary" href="/admin/setup.php">Créer mon compte administrateur</a>
        </p>
        <p class="muted" style="margin-top:14px">Pensez à supprimer <code>install.php</code> une fois l’installation faite.</p>
      <?php endif; ?>
    </section>
  </div>
</body>
</html>
