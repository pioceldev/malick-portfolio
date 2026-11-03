<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/bootstrap.php';
require dirname(__DIR__) . '/inc/uploads.php';

$admin = require_auth();
$title = 'Réglages du site';
$activeKey = 'settings';

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require_csrf();

    $values = [];
    foreach (SETTINGS_GROUPS as $group) {
        foreach ($group['fields'] as $field) {
            if (in_array($field['type'], ['image', 'file'], true)) {
                continue;
            }
            if (!array_key_exists($field['key'], $_POST)) {
                continue;
            }
            $values[$field['key']] = mb_substr(post($field['key']), 0, 20000);
        }
    }

    // Fichiers envoyés (photo de profil, favicon, CV…)
    foreach (SETTINGS_GROUPS as $group) {
        foreach ($group['fields'] as $field) {
            if (!in_array($field['type'], ['image', 'file'], true)) {
                continue;
            }
            $key = $field['key'];
            $file = uploaded_file('upload__' . $key);
            try {
                if ($file !== null) {
                    $values[$key] = save_upload($file);
                } elseif (array_key_exists($key, $_POST)) {
                    $values[$key] = mb_substr(post($key), 0, 255);
                }
            } catch (RuntimeException $exception) {
                $error = $exception->getMessage();
            }
        }
    }

    if ($error === '') {
        settings_save($values);
        redirect('/admin/settings.php?saved=1');
    }
}

$values = settings();
$saved = get_param('saved') === '1' && $error === '';
$groups = SETTINGS_GROUPS;

include dirname(__DIR__) . '/inc/admin-start.php';
?>

<?php if ($saved): ?><div class="alert alert--ok">Réglages enregistrés. Les changements sont déjà visibles sur le site.</div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert--error" role="alert"><?= e($error) ?></div><?php endif; ?>

<div class="settings">
  <nav class="settings__tabs" aria-label="Sections des réglages">
    <?php foreach ($groups as $index => $group): ?>
      <a href="#<?= e($group['key']) ?>" class="<?= $index === 0 ? 'is-active' : '' ?>"><span><?= e($group['icon']) ?></span><?= e($group['label']) ?></a>
    <?php endforeach; ?>
    <a class="settings__site" href="<?= e(u('/')) ?>" target="_blank" rel="noopener">Voir le site ↗</a>
  </nav>

  <form class="admin-form" method="post" action="<?= e(u('/admin/settings.php')) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <?php foreach ($groups as $group): ?>
      <section class="panel" id="<?= e($group['key']) ?>">
        <header class="panel__head">
          <h2 class="panel__title"><?= e($group['icon']) ?> <?= e($group['label']) ?></h2>
        </header>

        <div class="field-grid">
          <?php foreach ($group['fields'] as $field): ?>
            <?php $key = $field['key']; $value = (string) ($values[$key] ?? ''); ?>

            <?php if ($field['type'] === 'color'): ?>
              <label class="field field--color">
                <span><?= e($field['label']) ?></span>
                <span class="color-input">
                  <input type="color" name="<?= e($key) ?>" value="<?= e($value) ?>">
                  <code><?= e($value) ?></code>
                </span>
              </label>

            <?php elseif ($field['type'] === 'select'): ?>
              <label class="field">
                <span><?= e($field['label']) ?></span>
                <select name="<?= e($key) ?>">
                  <?php foreach ($field['options'] as $option): ?>
                    <option value="<?= e($option) ?>" <?= $value === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                  <?php endforeach; ?>
                </select>
                <?php if (!empty($field['hint'])): ?><small class="hint"><?= e($field['hint']) ?></small><?php endif; ?>
              </label>

            <?php elseif ($field['type'] === 'textarea'): ?>
              <label class="field field--wide">
                <span><?= e($field['label']) ?></span>
                <textarea name="<?= e($key) ?>" rows="<?= (int) ($field['rows'] ?? 4) ?>"><?= e($value) ?></textarea>
                <?php if (!empty($field['hint'])): ?><small class="hint"><?= e($field['hint']) ?></small><?php endif; ?>
              </label>

            <?php elseif (in_array($field['type'], ['image', 'file'], true)): ?>
              <div class="field field--wide">
                <span><?= e($field['label']) ?></span>
                <div class="media-input">
                  <div class="media-input__preview" data-preview-for="<?= e($key) ?>">
                    <?php if ($value !== ''): ?>
                      <?php if ($field['type'] === 'image'): ?>
                        <img src="<?= e(image_url($value)) ?>" alt="">
                      <?php else: ?>
                        <span class="file-chip"><?= e($value) ?></span>
                      <?php endif; ?>
                    <?php else: ?>
                      <span class="muted">Aucun fichier</span>
                    <?php endif; ?>
                  </div>
                  <input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>" data-media-value="<?= e($key) ?>">
                  <div class="media-input__actions">
                    <?php if ($field['type'] === 'image'): ?>
                      <button class="btn btn--tiny" type="button" data-open-media="<?= e($key) ?>">Médiathèque</button>
                    <?php endif; ?>
                    <label class="btn btn--tiny file-label">
                      Téléverser
                      <input type="file" name="upload__<?= e($key) ?>" accept="<?= $field['type'] === 'image' ? 'image/*' : 'image/*,application/pdf' ?>">
                    </label>
                    <button class="btn btn--tiny btn--danger" type="button" data-clear-media="<?= e($key) ?>">Retirer</button>
                  </div>
                </div>
                <?php if (!empty($field['hint'])): ?><small class="hint"><?= e($field['hint']) ?></small><?php endif; ?>
              </div>

            <?php else: ?>
              <label class="field">
                <span><?= e($field['label']) ?></span>
                <input type="<?= $field['type'] === 'url' ? 'text' : e($field['type']) ?>" name="<?= e($key) ?>" value="<?= e($value) ?>">
                <?php if (!empty($field['hint'])): ?><small class="hint"><?= e($field['hint']) ?></small><?php endif; ?>
              </label>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endforeach; ?>

    <div class="sticky-save">
      <p class="muted">Les modifications sont appliquées immédiatement sur le site public.</p>
      <button class="btn btn--primary" type="submit">Enregistrer les réglages</button>
    </div>
  </form>
</div>

<?php include dirname(__DIR__) . '/inc/admin-end.php'; ?>
