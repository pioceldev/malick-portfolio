<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/bootstrap.php';

$admin = require_auth();

$id = to_int(get_param('id'));
$message = $id > 0 ? row('SELECT * FROM messages WHERE id = ?', [$id]) : null;

if ($message === null) {
    redirect('/admin/messages.php');
}

sql_exec('UPDATE messages SET is_read = 1 WHERE id = ?', [$id]);

$title = 'Message de ' . $message['name'];
$activeKey = 'messages';

include dirname(__DIR__) . '/inc/admin-start.php';
?>

<a class="link" href="/admin/messages.php">← Retour à la boîte de réception</a>

<article class="panel message">
  <header class="message__head">
    <div>
      <h2 class="message__subject"><?= e($message['subject'] ?: 'Sans sujet') ?></h2>
      <p class="muted">
        De <strong><?= e($message['name']) ?></strong> ·
        <a href="mailto:<?= e($message['email']) ?>"><?= e($message['email']) ?></a>
        <?php if (!empty($message['project_type'])): ?> · <?= e($message['project_type']) ?><?php endif; ?>
        <?php if (!empty($message['budget'])): ?> · Budget : <?= e($message['budget']) ?><?php endif; ?>
      </p>
      <p class="muted">Reçu le <?= e(str_replace('T', ' ', substr((string) $message['created_at'], 0, 16))) ?></p>
    </div>
    <div class="row-actions">
      <a class="btn btn--primary btn--tiny" href="mailto:<?= e($message['email']) ?>?subject=<?= e(rawurlencode('Re: ' . ($message['subject'] ?: 'Votre message'))) ?>">Répondre</a>
      <form method="post" action="/admin/message-action.php">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $message['id'] ?>">
        <input type="hidden" name="action" value="<?= (int) $message['archived'] ? 'restore' : 'archive' ?>">
        <input type="hidden" name="back" value="/admin/message.php?id=<?= (int) $message['id'] ?>">
        <button class="btn btn--tiny" type="submit"><?= (int) $message['archived'] ? 'Restaurer' : 'Archiver' ?></button>
      </form>
      <form method="post" action="/admin/message-action.php" data-confirm="Supprimer ce message ?">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $message['id'] ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="back" value="/admin/messages.php">
        <button class="btn btn--tiny btn--danger" type="submit">Supprimer</button>
      </form>
    </div>
  </header>

  <div class="message__body">
    <?= paragraphs($message['body']) ?>
  </div>
</article>

<?php include dirname(__DIR__) . '/inc/admin-end.php'; ?>
