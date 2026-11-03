<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/bootstrap.php';
require dirname(__DIR__) . '/inc/uploads.php';

$admin = require_auth();
$title = 'Médiathèque';
$activeKey = 'media';

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require_csrf();
    $files = uploaded_files('files');
    if (!$files) {
        $error = 'Aucun fichier sélectionné.';
    }
    foreach ($files as $file) {
        try {
            save_upload($file);
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        }
    }
    if ($error === '') {
        redirect('/admin/media.php');
    }
}

$media = rows('SELECT * FROM media ORDER BY id DESC');

include dirname(__DIR__) . '/inc/admin-start.php';
?>

<?php if ($error !== ''): ?><div class="alert alert--error" role="alert"><?= e($error) ?></div><?php endif; ?>

<section class="panel">
  <h2 class="panel__title">Importer des fichiers</h2>
  <p class="muted">Images (JPG, PNG, WebP, AVIF, SVG) et PDF — 12 Mo maximum par fichier.</p>
  <form method="post" action="<?= e(u('/admin/media.php')) ?>" enctype="multipart/form-data" class="upload-form">
    <?= csrf_field() ?>
    <label class="dropzone">
      <input type="file" name="files[]" accept="image/*,application/pdf" multiple required>
      <span class="dropzone__icon">⇪</span>
      <span class="dropzone__text">Cliquez pour choisir des fichiers</span>
      <span class="dropzone__hint" data-file-name>Aucun fichier sélectionné</span>
    </label>
    <button class="btn btn--primary" type="submit">Téléverser</button>
  </form>
</section>

<section class="panel">
  <div class="panel__head">
    <h2 class="panel__title">Bibliothèque (<?= count($media) ?>)</h2>
  </div>

  <?php if (!$media): ?>
    <div class="empty">
      <p class="empty__title">Aucun média</p>
      <p class="muted">Importez vos premières images pour les utiliser dans les projets et réglages.</p>
    </div>
  <?php else: ?>
    <div class="media-grid">
      <?php foreach ($media as $item): ?>
        <article class="media-card">
          <div class="media-card__preview">
            <?php if (str_starts_with((string) $item['mime'], 'image/')): ?>
              <img src="<?= e(image_url((string) $item['filename'])) ?>" alt="<?= e($item['alt']) ?>" loading="lazy">
            <?php else: ?>
              <span class="file-chip">PDF</span>
            <?php endif; ?>
          </div>
          <div class="media-card__body">
            <p class="media-card__name" title="<?= e($item['original_name']) ?>"><?= e($item['original_name']) ?></p>
            <p class="media-card__meta"><?= e(file_size_label((int) $item['size'])) ?> · <?= e(substr((string) $item['created_at'], 0, 10)) ?></p>
            <form method="post" action="<?= e(u('/admin/media-alt.php')) ?>" class="media-card__alt">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
              <input type="text" name="alt" value="<?= e($item['alt']) ?>" placeholder="Texte alternatif (accessibilité)" aria-label="Texte alternatif">
              <button class="btn btn--tiny" type="submit">OK</button>
            </form>
            <div class="media-card__actions">
              <code><?= e(u('/uploads/' . $item['filename'])) ?></code>
              <form method="post" action="<?= e(u('/admin/media-delete.php')) ?>" data-confirm="Supprimer ce fichier ? Les pages qui l’utilisent afficheront un visuel manquant.">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                <button class="btn btn--tiny btn--danger" type="submit">Supprimer</button>
              </form>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php include dirname(__DIR__) . '/inc/admin-end.php'; ?>
