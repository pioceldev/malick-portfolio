<?php
declare(strict_types=1);

require __DIR__ . '/inc/bootstrap.php';

$slug = get_param('slug');
$project = $slug !== '' ? project_by_slug($slug) : null;
$preview = false;

// Aperçu privé d'un projet non publié (administrateur connecté uniquement)
if ($project === null && current_user() !== null && get_param('preview') === '1') {
    $previewId = to_int(get_param('id'));
    $candidate = $previewId > 0 ? row('SELECT * FROM projects WHERE id = ?', [$previewId]) : null;
    if ($candidate !== null) {
        $project = map_project($candidate);
        $preview = true;
    }
}

if ($project === null) {
    require __DIR__ . '/404.php';
    exit;
}

$data = public_data();

$settings = $data['settings'];
$nav = $data['nav'];
$socials = $data['socials'];
$cv = $data['cv'];

$related = [];
foreach ($data['projects'] as $item) {
    if ((int) $item['id'] !== (int) $project['id'] && $item['category'] === $project['category']) {
        $related[] = $item;
    }
}
$related = array_slice($related, 0, 2);

$pageTitle = $project['title'] . ' — ' . $settings['site_name'];
$metaDescription = $project['summary'] ?: $settings['meta_description'];
$ogImage = $project['coverUrl'];
$canonicalPath = '/projets/' . $project['slug'];

include __DIR__ . '/inc/head.php';
include __DIR__ . '/inc/nav.php';
?>

<main id="main">
  <?php if ($preview): ?>
    <div class="preview-bar">Aperçu privé — ce projet n’est pas visible publiquement. <a href="<?= e(entity_edit_url('projects', (int) $project['id'])) ?>">Modifier</a></div>
  <?php endif; ?>

  <article class="case">
    <header class="case__head">
      <a class="case__back" href="<?= e(u('/#projets')) ?>"><span aria-hidden="true">←</span> Tous les projets</a>
      <p class="case__kicker reveal"><?= e($project['category']) ?><?php if (!empty($project['year'])): ?> · <?= e($project['year']) ?><?php endif; ?></p>
      <h1 class="case__title reveal"><?= e($project['title']) ?></h1>
      <?php if (!empty($project['summary'])): ?><p class="case__summary reveal"><?= e($project['summary']) ?></p><?php endif; ?>
    </header>

    <dl class="case__meta reveal">
      <div><dt>Statut</dt><dd><?= e($project['status']) ?></dd></div>
      <?php if (!empty($project['category'])): ?><div><dt>Catégorie</dt><dd><?= e($project['category']) ?></dd></div><?php endif; ?>
      <?php if (!empty($project['year'])): ?><div><dt>Année</dt><dd><?= e($project['year']) ?></dd></div><?php endif; ?>
      <?php if ($project['techList']): ?><div><dt>Technologies</dt><dd><?= e(implode(', ', $project['techList'])) ?></dd></div><?php endif; ?>
    </dl>

    <div class="case__actions reveal">
      <?php if ($project['demo'] !== ''): ?><a class="btn btn--solid" href="<?= e($project['demo']) ?>" target="_blank" rel="noopener noreferrer" data-magnetic><span>Voir le site ↗</span></a><?php endif; ?>
      <?php if ($project['repo'] !== ''): ?><a class="btn btn--ghost" href="<?= e($project['repo']) ?>" target="_blank" rel="noopener noreferrer" data-magnetic><span>Code source ↗</span></a><?php endif; ?>
    </div>

    <?php if ($project['coverUrl'] !== ''): ?>
      <figure class="case__cover reveal">
        <img src="<?= e($project['coverUrl']) ?>" alt="Aperçu du projet <?= e($project['title']) ?>" width="1600" height="1000" loading="eager" decoding="async">
      </figure>
    <?php endif; ?>

    <div class="case__body">
      <div class="case__text reveal">
        <?= paragraphs($project['description'] ?: $project['summary']) ?>
      </div>

      <?php if ($project['techList']): ?>
        <aside class="case__side reveal">
          <p class="case__side-label">Technologies</p>
          <ul class="tags">
            <?php foreach ($project['techList'] as $tech): ?><li><?= e($tech) ?></li><?php endforeach; ?>
          </ul>
          <p class="case__side-label">Liens</p>
          <ul class="case__links">
            <?php if ($project['demo'] !== ''): ?><li><a href="<?= e($project['demo']) ?>" target="_blank" rel="noopener noreferrer">Démonstration ↗</a></li><?php endif; ?>
            <?php if ($project['repo'] !== ''): ?><li><a href="<?= e($project['repo']) ?>" target="_blank" rel="noopener noreferrer">Dépôt GitHub ↗</a></li><?php endif; ?>
            <?php if ($project['demo'] === '' && $project['repo'] === ''): ?><li class="muted">Aucun lien public</li><?php endif; ?>
          </ul>
        </aside>
      <?php endif; ?>
    </div>

    <?php if ($project['galleryList']): ?>
      <section class="case__gallery">
        <h2 class="case__gallery-title">Galerie</h2>
        <div class="gallery">
          <?php foreach ($project['galleryList'] as $index => $file): ?>
            <figure class="gallery__item reveal" data-delay="<?= (int) $index % 3 ?>">
              <img src="<?= e(image_url((string) $file)) ?>" alt="<?= e($project['title']) ?> — visuel <?= (int) $index + 1 ?>" loading="lazy" decoding="async">
            </figure>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>
  </article>

  <?php if ($related): ?>
    <section class="section section--related">
      <header class="section__head"><p class="section__kicker">À suivre</p><h2 class="section__title">Projets similaires</h2></header>
      <div class="related">
        <?php foreach ($related as $item): ?>
          <a class="related__item" href="<?= e(project_url((string) $item['slug'])) ?>">
            <?php if ($item['coverUrl'] !== ''): ?><img src="<?= e($item['coverUrl']) ?>" alt="" loading="lazy"> <?php endif; ?>
            <span><strong><?= e($item['title']) ?></strong><small><?= e($item['category']) ?> · <?= e($item['year']) ?></small></span>
            <span aria-hidden="true">↗</span>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</main>

<?php include __DIR__ . '/inc/footer.php'; ?>
