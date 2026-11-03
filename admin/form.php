<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/bootstrap.php';
require dirname(__DIR__) . '/inc/uploads.php';

$admin = require_auth();

$entityKey = get_param('entity');
$entity = get_entity($entityKey);
if ($entity === null) {
    redirect('/admin/');
}

$table = $entity['table'];
$id = to_int(get_param('id', post('id')));
$existing = $id > 0 ? row('SELECT * FROM ' . $table . ' WHERE id = ?', [$id]) : null;
$isNew = $existing === null;

if (!$isNew && (int) ($existing['id'] ?? 0) !== $id) {
    $isNew = true;
}

/** Convertit une valeur envoyée selon le type du champ. */
function coerce_field(array $field, mixed $raw): mixed
{
    $type = $field['type'];
    if ($type === 'boolean') {
        return to_bool($raw);
    }
    if ($type === 'number') {
        return to_int($raw, (int) ($field['default'] ?? 0));
    }
    $value = trim((string) ($raw ?? ''));
    if ($type === 'url' && $value !== '' && !preg_match('#^https?://#i', $value) && str_starts_with($value, 'www.')) {
        $value = 'https://' . $value;
    }
    return mb_substr($value, 0, 20000);
}

/** Slug unique dans la table. */
function unique_slug(string $table, string $slug, int $ignoreId = 0): string
{
    $base = $slug !== '' ? $slug : 'element';
    $candidate = $base;
    $index = 2;
    while (row('SELECT id FROM ' . $table . ' WHERE slug = ? AND id != ?', [$candidate, $ignoreId]) !== null) {
        $candidate = $base . '-' . $index;
        $index++;
    }
    return $candidate;
}

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require_csrf();

    $values = [];
    foreach ($entity['fields'] as $field) {
        $name = $field['name'];
        if ($field['type'] === 'gallery') {
            continue;
        }
        if (in_array($field['type'], ['image', 'file'], true)) {
            $values[$name] = (string) post($name);
            continue;
        }
        if (!array_key_exists($name, $_POST)) {
            // Case à cocher décochée
            $values[$name] = $field['type'] === 'boolean' ? 0 : coerce_field($field, '');
            continue;
        }
        $values[$name] = coerce_field($field, $_POST[$name]);
    }

    // Fichiers téléversés depuis le formulaire
    foreach ($entity['fields'] as $field) {
        if (!in_array($field['type'], ['image', 'file'], true)) {
            continue;
        }
        $file = uploaded_file('upload__' . $field['name']);
        if ($file === null) {
            continue;
        }
        try {
            $values[$field['name']] = save_upload($file);
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        }
    }

    // Galerie : sélection existante + nouveaux fichiers
    foreach ($entity['fields'] as $field) {
        if ($field['type'] !== 'gallery') {
            continue;
        }
        $gallery = json_decode((string) post('gallery_json', '[]'), true);
        $gallery = is_array($gallery) ? array_values(array_filter(array_map('strval', $gallery))) : [];

        foreach (uploaded_files('gallery_files') as $file) {
            try {
                $gallery[] = save_upload($file);
            } catch (RuntimeException $exception) {
                $error = $exception->getMessage();
            }
        }
        $values[$field['name']] = json_encode(array_values(array_unique($gallery)), JSON_UNESCAPED_UNICODE);
    }

    // Slug unique
    foreach ($entity['fields'] as $field) {
        if (empty($field['slugFrom'])) {
            continue;
        }
        $source = (string) ($values[$field['name']] ?? '');
        if ($source === '') {
            $source = (string) ($values[$field['slugFrom']] ?? '');
        }
        $values[$field['name']] = unique_slug($table, slugify($source), $existing ? (int) $existing['id'] : 0);
    }

    // Champs obligatoires
    if ($error === '') {
        foreach ($entity['fields'] as $field) {
            if (!empty($field['required']) && trim((string) ($values[$field['name']] ?? '')) === '') {
                $error = 'Le champ « ' . $field['label'] . ' » est obligatoire.';
                break;
            }
        }
    }

    if ($error === '') {
        try {
            if ($existing) {
                $assignments = implode(', ', array_map(static fn (string $column): string => $column . ' = ?', array_keys($values)));
                sql_exec('UPDATE ' . $table . ' SET ' . $assignments . ' WHERE id = ?', [...array_values($values), (int) $existing['id']]);
            } else {
                $columns = implode(', ', array_keys($values));
                $placeholders = implode(', ', array_fill(0, count($values), '?'));
                sql_exec('INSERT INTO ' . $table . ' (' . $columns . ') VALUES (' . $placeholders . ')', array_values($values));
            }
            redirect(entity_list_url($entityKey) . '&saved=1');
        } catch (PDOException $exception) {
            $error = 'Enregistrement impossible : ' . $exception->getMessage();
        }
    }
}

// Valeurs affichées par le formulaire
$values = [];
if ($existing) {
    $values = $existing;
} else {
    foreach ($entity['fields'] as $field) {
        $values[$field['name']] = $field['default'] ?? ($field['type'] === 'boolean' ? 1 : '');
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($entity['fields'] as $field) {
        if ($field['type'] === 'gallery') {
            $values[$field['name']] = post('gallery_json', '[]');
            continue;
        }
        if (array_key_exists($field['name'], $_POST) && !in_array($field['type'], ['image', 'file'], true)) {
            $values[$field['name']] = $_POST[$field['name']];
        }
    }
}

$galleryItems = json_decode((string) ($values['gallery'] ?? '[]'), true);
$galleryItems = is_array($galleryItems) ? $galleryItems : [];

$title = ($isNew ? 'Nouveau — ' : 'Modifier — ') . $entity['singular'];
$activeKey = 'entity:' . $entityKey;

include dirname(__DIR__) . '/inc/admin-start.php';
?>

<?php if ($error !== ''): ?><div class="alert alert--error" role="alert"><?= e($error) ?></div><?php endif; ?>

<form class="admin-form form-grid" method="post"
      action="<?= e(admin_url('form.php', $isNew ? ['entity' => $entityKey] : ['entity' => $entityKey, 'id' => (int) $existing['id']])) ?>"
      enctype="multipart/form-data">
  <?= csrf_field() ?>
  <?php if (!$isNew): ?><input type="hidden" name="id" value="<?= (int) $existing['id'] ?>"><?php endif; ?>

  <div class="form-grid__main">
    <?php foreach ($entity['fields'] as $field): ?>
      <?php
      $name = $field['name'];
      $value = $values[$name] ?? '';
      $required = !empty($field['required']);
      ?>

      <?php if ($field['type'] === 'boolean'): ?>
        <label class="switch">
          <input type="checkbox" name="<?= e($name) ?>" value="1" <?= !empty($value) ? 'checked' : '' ?>>
          <span class="switch__track"><span></span></span>
          <span class="switch__label"><?= e($field['label']) ?></span>
        </label>

      <?php elseif ($field['type'] === 'select'): ?>
        <label class="field">
          <span><?= e($field['label']) ?><?= $required ? ' *' : '' ?></span>
          <select name="<?= e($name) ?>">
            <?php foreach ($field['options'] as $option): ?>
              <option value="<?= e($option) ?>" <?= (string) $value === (string) $option ? 'selected' : '' ?>><?= e($option) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (!empty($field['hint'])): ?><small class="hint"><?= e($field['hint']) ?></small><?php endif; ?>
        </label>

      <?php elseif ($field['type'] === 'textarea'): ?>
        <label class="field">
          <span><?= e($field['label']) ?><?= $required ? ' *' : '' ?></span>
          <textarea name="<?= e($name) ?>" rows="<?= (int) ($field['rows'] ?? 5) ?>" <?= $required ? 'required' : '' ?>><?= e((string) $value) ?></textarea>
          <?php if (!empty($field['hint'])): ?><small class="hint"><?= e($field['hint']) ?></small><?php endif; ?>
        </label>

      <?php elseif (in_array($field['type'], ['image', 'file'], true)): ?>
        <div class="field">
          <span><?= e($field['label']) ?></span>
          <div class="media-input">
            <div class="media-input__preview" data-preview-for="<?= e($name) ?>">
              <?php if ((string) $value !== ''): ?>
                <?php if ($field['type'] === 'image'): ?>
                  <img src="<?= e(image_url((string) $value)) ?>" alt="">
                <?php else: ?>
                  <span class="file-chip"><?= e((string) $value) ?></span>
                <?php endif; ?>
              <?php else: ?>
                <span class="muted">Aucun fichier</span>
              <?php endif; ?>
            </div>
            <input type="hidden" name="<?= e($name) ?>" value="<?= e((string) $value) ?>" data-media-value="<?= e($name) ?>">
            <div class="media-input__actions">
              <button class="btn btn--tiny" type="button" data-open-media="<?= e($name) ?>">Médiathèque</button>
              <label class="btn btn--tiny file-label">
                Téléverser
                <input type="file" name="upload__<?= e($name) ?>" accept="<?= $field['type'] === 'image' ? 'image/*' : 'image/*,application/pdf' ?>">
              </label>
              <button class="btn btn--tiny btn--danger" type="button" data-clear-media="<?= e($name) ?>">Retirer</button>
            </div>
          </div>
          <?php if (!empty($field['hint'])): ?><small class="hint"><?= e($field['hint']) ?></small><?php endif; ?>
        </div>

      <?php elseif ($field['type'] === 'gallery'): ?>
        <div class="field" data-gallery-root>
          <span><?= e($field['label']) ?></span>
          <input type="hidden" name="gallery_json" value="<?= e(json_encode(array_values($galleryItems), JSON_UNESCAPED_UNICODE)) ?>" data-gallery-json>
          <ul class="gallery-input" data-gallery-list>
            <?php foreach ($galleryItems as $file): ?>
              <li data-file="<?= e((string) $file) ?>">
                <img src="<?= e(image_url((string) $file)) ?>" alt="">
                <button type="button" class="gallery-input__remove" data-gallery-remove aria-label="Retirer">✕</button>
              </li>
            <?php endforeach; ?>
          </ul>
          <div class="media-input__actions">
            <button class="btn btn--tiny" type="button" data-open-media-gallery>Ajouter depuis la médiathèque</button>
            <label class="btn btn--tiny file-label">
              Téléverser
              <input type="file" name="gallery_files[]" accept="image/*" multiple>
            </label>
          </div>
          <small class="hint">La première image sert de couverture secondaire dans la galerie du projet.</small>
        </div>

      <?php else: ?>
        <label class="field">
          <span><?= e($field['label']) ?><?= $required ? ' *' : '' ?></span>
          <input type="<?= $field['type'] === 'number' ? 'number' : ($field['type'] === 'url' ? 'url' : ($field['type'] === 'email' ? 'email' : ($field['type'] === 'tel' ? 'tel' : 'text'))) ?>"
                 name="<?= e($name) ?>"
                 value="<?= e((string) $value) ?>"
                 <?= $field['type'] === 'number' ? 'min="' . (int) ($field['min'] ?? 0) . '" max="' . (int) ($field['max'] ?? 9999) . '"' : '' ?>
                 <?= !empty($field['slugFrom']) ? 'data-slug-target="' . e($field['slugFrom']) . '"' : '' ?>
                 <?= $required ? 'required' : '' ?>>
          <?php if (!empty($field['hint'])): ?><small class="hint"><?= e($field['hint']) ?></small><?php endif; ?>
        </label>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <aside class="form-grid__side">
    <div class="panel">
      <h2 class="panel__title">Publication</h2>
      <p class="muted"><?= !empty($values['published']) ? 'Cet élément est visible sur le site.' : 'Brouillon : invisible pour les visiteurs.' ?></p>
      <button class="btn btn--primary btn--block" type="submit">Enregistrer</button>
      <a class="btn btn--ghost btn--block" href="<?= e(entity_list_url($entityKey)) ?>">Annuler</a>
      <?php if (!empty($entity['preview']) && !$isNew): ?>
        <a class="btn btn--ghost btn--block" href="<?= e(admin_url('preview.php', ['id' => (int) $existing['id']])) ?>" target="_blank" rel="noopener">Prévisualiser</a>
      <?php endif; ?>
    </div>

    <div class="panel panel--help">
      <h2 class="panel__title">Bon à savoir</h2>
      <ul class="help-list">
        <li>Les sections vides sont masquées automatiquement sur le site public.</li>
        <li>Une ligne vide dans un texte crée un nouveau paragraphe.</li>
        <li>Les images sont optimisées via la médiathèque.</li>
      </ul>
    </div>
  </aside>
</form>

<?php include dirname(__DIR__) . '/inc/admin-end.php'; ?>
