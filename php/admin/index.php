<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/bootstrap.php';
require dirname(__DIR__) . '/inc/uploads.php';

$admin = require_auth();
$title = 'Tableau de bord';
$activeKey = 'dashboard';

$counts = [
    'projects' => (int) val('SELECT COUNT(*) FROM projects', [], 0),
    'skills' => (int) val('SELECT COUNT(*) FROM skills', [], 0),
    'experiences' => (int) val('SELECT COUNT(*) FROM experiences', [], 0),
    'messages' => (int) val('SELECT COUNT(*) FROM messages WHERE archived = 0', [], 0),
    'unread' => unread_messages(),
    'testimonials' => (int) val('SELECT COUNT(*) FROM testimonials', [], 0),
    'media' => (int) val('SELECT COUNT(*) FROM media', [], 0),
];

$recentMessages = rows('SELECT * FROM messages ORDER BY id DESC LIMIT 5');
$recentProjects = rows('SELECT * FROM projects ORDER BY id DESC LIMIT 5');

include dirname(__DIR__) . '/inc/admin-start.php';
?>

<div class="stat-grid">
  <a class="stat" href="<?= e(entity_list_url('projects')) ?>">
    <span class="stat__label">Projets</span>
    <strong class="stat__value"><?= $counts['projects'] ?></strong>
    <span class="stat__link">Gérer →</span>
  </a>
  <a class="stat" href="<?= e(entity_list_url('skills')) ?>">
    <span class="stat__label">Compétences</span>
    <strong class="stat__value"><?= $counts['skills'] ?></strong>
    <span class="stat__link">Gérer →</span>
  </a>
  <a class="stat" href="<?= e(entity_list_url('experiences')) ?>">
    <span class="stat__label">Étapes du parcours</span>
    <strong class="stat__value"><?= $counts['experiences'] ?></strong>
    <span class="stat__link">Gérer →</span>
  </a>
  <a class="stat stat--accent" href="/admin/messages.php">
    <span class="stat__label">Messages non lus</span>
    <strong class="stat__value"><?= $counts['unread'] ?></strong>
    <span class="stat__link"><?= $counts['messages'] ?> message(s) actif(s) →</span>
  </a>
  <a class="stat" href="<?= e(entity_list_url('testimonials')) ?>">
    <span class="stat__label">Témoignages</span>
    <strong class="stat__value"><?= $counts['testimonials'] ?></strong>
    <span class="stat__link">Gérer →</span>
  </a>
  <a class="stat" href="/admin/media.php">
    <span class="stat__label">Médias</span>
    <strong class="stat__value"><?= $counts['media'] ?></strong>
    <span class="stat__link">Ouvrir →</span>
  </a>
</div>

<?php if ($counts['projects'] === 0 || $counts['skills'] === 0 || $counts['experiences'] === 0): ?>
<section class="panel">
  <h2 class="panel__title">Pour que le site soit complet</h2>
  <p class="muted">Les sections vides sont automatiquement masquées sur le site public. Voici ce qu’il reste à renseigner :</p>
  <ul class="checklist">
    <li class="<?= $counts['projects'] ? 'is-done' : '' ?>"><span></span>Ajouter au moins 3 projets (avec image de couverture)</li>
    <li class="<?= $counts['skills'] ? 'is-done' : '' ?>"><span></span>Lister vos compétences par catégorie</li>
    <li class="<?= $counts['experiences'] ? 'is-done' : '' ?>"><span></span>Compléter expériences, formations et certifications</li>
    <li class="<?= $siteSettings['portrait'] ? 'is-done' : '' ?>"><span></span>Ajouter une photo de profil dans les réglages</li>
    <li class="<?= $siteSettings['cv_file'] ? 'is-done' : '' ?>"><span></span>Joindre votre CV en PDF</li>
    <li class="<?= $siteSettings['canonical_url'] ? 'is-done' : '' ?>"><span></span>Renseigner l’URL du site (SEO)</li>
  </ul>
  <a class="btn btn--primary" href="/admin/settings.php">Compléter les réglages</a>
</section>
<?php endif; ?>

<div class="two-col">
  <section class="panel">
    <div class="panel__head">
      <h2 class="panel__title">Derniers messages</h2>
      <a href="/admin/messages.php" class="link">Tout voir →</a>
    </div>
    <?php if (!$recentMessages): ?>
      <p class="muted">Aucun message pour l’instant. Le formulaire de contact est actif.</p>
    <?php else: ?>
      <ul class="mini-list">
        <?php foreach ($recentMessages as $message): ?>
          <li>
            <a href="/admin/message.php?id=<?= (int) $message['id'] ?>">
              <span class="mini-list__main">
                <strong><?= e($message['name']) ?><?php if (!(int) $message['is_read']): ?><em class="dot-new"></em><?php endif; ?></strong>
                <small><?= e($message['subject'] ?: ($message['project_type'] ?: 'Sans sujet')) ?> — <?= e(substr((string) $message['created_at'], 0, 16)) ?></small>
              </span>
              <span class="mini-list__meta"><?= e($message['email']) ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <section class="panel">
    <div class="panel__head">
      <h2 class="panel__title">Derniers projets</h2>
      <a href="<?= e(entity_list_url('projects')) ?>" class="link">Gérer →</a>
    </div>
    <?php if (!$recentProjects): ?>
      <p class="muted">Aucun projet enregistré.</p>
      <a class="btn btn--primary" href="<?= e(entity_new_url('projects')) ?>">Créer le premier projet</a>
    <?php else: ?>
      <ul class="mini-list">
        <?php foreach ($recentProjects as $project): ?>
          <li>
            <a href="<?= e(entity_edit_url('projects', (int) $project['id'])) ?>">
              <span class="mini-list__main">
                <strong><?= e($project['title']) ?></strong>
                <small><?= e($project['category'] ?: 'Sans catégorie') ?> · <?= e($project['year'] ?: '—') ?></small>
              </span>
              <span class="pill <?= (int) $project['published'] ? 'pill--ok' : 'pill--muted' ?>"><?= (int) $project['published'] ? 'Publié' : 'Brouillon' ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>

<?php include dirname(__DIR__) . '/inc/admin-end.php'; ?>
