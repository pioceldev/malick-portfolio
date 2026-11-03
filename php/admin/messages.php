<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/bootstrap.php';

$admin = require_auth();

$showArchived = get_param('archive') === '1';
$title = $showArchived ? 'Messages archivés' : 'Boîte de réception';
$activeKey = 'messages';

$messages = rows(
    'SELECT * FROM messages WHERE archived = ? ORDER BY is_read ASC, id DESC',
    [$showArchived ? 1 : 0]
);

$backUrl = $showArchived ? '/admin/messages.php?archive=1' : '/admin/messages.php';

include dirname(__DIR__) . '/inc/admin-start.php';
?>

<div class="toolbar">
  <div class="tabs">
    <a href="/admin/messages.php" class="<?= !$showArchived ? 'is-active' : '' ?>">Boîte de réception</a>
    <a href="/admin/messages.php?archive=1" class="<?= $showArchived ? 'is-active' : '' ?>">Archivés</a>
  </div>
  <p class="toolbar__count"><?= count($messages) ?> message(s)</p>
</div>

<section class="panel panel--flush">
  <?php if (!$messages): ?>
    <div class="empty">
      <p class="empty__title">Aucun message</p>
      <p class="muted">Les messages envoyés depuis le formulaire de contact apparaîtront ici.</p>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Expéditeur</th>
            <th>Sujet</th>
            <th>Type</th>
            <th>Reçu le</th>
            <th class="cell-actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($messages as $message): ?>
            <tr class="<?= (int) $message['is_read'] ? '' : 'is-unread' ?>">
              <td>
                <a class="link-strong" href="/admin/message.php?id=<?= (int) $message['id'] ?>"><?= e($message['name']) ?></a>
                <small class="muted block"><?= e($message['email']) ?></small>
              </td>
              <td>
                <a href="/admin/message.php?id=<?= (int) $message['id'] ?>"><?= e($message['subject'] ?: 'Sans sujet') ?></a>
                <?php if (!(int) $message['is_read']): ?><span class="pill pill--new">Nouveau</span><?php endif; ?>
              </td>
              <td><?= e($message['project_type'] ?: '—') ?></td>
              <td><?= e(substr((string) $message['created_at'], 0, 16)) ?></td>
              <td class="cell-actions">
                <div class="row-actions">
                  <a class="btn btn--tiny" href="/admin/message.php?id=<?= (int) $message['id'] ?>">Lire</a>
                  <form method="post" action="/admin/message-action.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $message['id'] ?>">
                    <input type="hidden" name="action" value="<?= (int) $message['archived'] ? 'restore' : 'archive' ?>">
                    <input type="hidden" name="back" value="<?= e($backUrl) ?>">
                    <button class="btn btn--tiny" type="submit"><?= (int) $message['archived'] ? 'Restaurer' : 'Archiver' ?></button>
                  </form>
                  <form method="post" action="/admin/message-action.php" data-confirm="Supprimer ce message ?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $message['id'] ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="back" value="<?= e($backUrl) ?>">
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

<?php include dirname(__DIR__) . '/inc/admin-end.php'; ?>
