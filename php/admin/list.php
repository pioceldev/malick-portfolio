<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/bootstrap.php';

$admin = require_auth();

$entityKey = get_param('entity');
$entity = get_entity($entityKey);
if ($entity === null) {
    redirect('/admin/');
}

$title = $entity['label'];
$activeKey = 'entity:' . $entityKey;

$order = $entity['table'] === 'nav_items' ? 'sort ASC, id ASC' : 'sort ASC, id DESC';
$rows = rows('SELECT * FROM ' . $entity['table'] . ' ORDER BY ' . $order);

$saved = get_param('saved') === '1';
$deleted = get_param('deleted') === '1';

include dirname(__DIR__) . '/inc/admin-start.php';
?>

<div class="toolbar">
  <p class="toolbar__count"><?= count($rows) ?> élément(s)</p>
  <div class="toolbar__actions">
    <a class="btn btn--primary" href="<?= e(entity_new_url($entityKey)) ?>">+ Nouveau</a>
  </div>
</div>

<?php if ($saved): ?><div class="alert alert--ok">Enregistré.</div><?php endif; ?>
<?php if ($deleted): ?><div class="alert alert--ok">Élément supprimé.</div><?php endif; ?>

<section class="panel panel--flush">
  <?php if (!$rows): ?>
    <div class="empty">
      <p class="empty__title">Rien ici pour le moment</p>
      <p class="muted">Créez un premier élément pour l’afficher sur le site.</p>
      <a class="btn btn--primary" href="<?= e(entity_new_url($entityKey)) ?>">+ Nouveau</a>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th class="cell-id">#</th>
            <?php foreach ($entity['listColumns'] as $column): ?><th><?= e($column['label']) ?></th><?php endforeach; ?>
            <th class="cell-actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $item): ?>
            <tr>
              <td class="cell-id"><?= (int) $item['id'] ?></td>
              <?php foreach ($entity['listColumns'] as $column): ?>
                <td>
                  <?php if (($column['type'] ?? '') === 'bool'): ?>
                    <span class="pill <?= (int) $item[$column['name']] ? 'pill--ok' : 'pill--muted' ?>"><?= (int) $item[$column['name']] ? 'Oui' : 'Non' ?></span>
                  <?php else: ?>
                    <?= e((string) $item[$column['name']] === '' ? '—' : mb_substr((string) $item[$column['name']], 0, 70)) ?>
                  <?php endif; ?>
                </td>
              <?php endforeach; ?>
              <td class="cell-actions">
                <div class="row-actions">
                  <a class="btn btn--tiny" href="<?= e(entity_edit_url($entityKey, (int) $item['id'])) ?>">Modifier</a>
                  <?php if (!empty($entity['preview'])): ?>
                    <a class="btn btn--tiny" href="/admin/preview.php?id=<?= (int) $item['id'] ?>" target="_blank" rel="noopener">Aperçu</a>
                  <?php endif; ?>
                  <?php if (in_array('published', array_column($entity['fields'], 'name'), true)): ?>
                    <form method="post" action="/admin/toggle.php">
                      <?= csrf_field() ?>
                      <input type="hidden" name="entity" value="<?= e($entityKey) ?>">
                      <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                      <button class="btn btn--tiny" type="submit"><?= (int) $item['published'] ? 'Dépublier' : 'Publier' ?></button>
                    </form>
                  <?php endif; ?>
                  <form method="post" action="/admin/delete.php" data-confirm="Supprimer définitivement « <?= e($item['title'] ?? $item['name'] ?? $item['label'] ?? $item['id']) ?> » ?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="entity" value="<?= e($entityKey) ?>">
                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                    <button class="btn btn--tiny btn--danger" type="submit">Supprimer</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<div class="hint-box">
  <strong>Astuce :</strong> l’ordre d’affichage se règle avec le champ « Ordre d’affichage » (0 = en premier).
</div>

<?php include dirname(__DIR__) . '/inc/admin-end.php'; ?>
