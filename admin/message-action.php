<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/bootstrap.php';

$admin = require_auth();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect('/admin/messages.php');
}
require_csrf();

$id = to_int(post('id'));
$action = post('action');
$back = post('back');

if ($id > 0) {
    match ($action) {
        'read' => sql_exec('UPDATE messages SET is_read = 1 WHERE id = ?', [$id]),
        'unread' => sql_exec('UPDATE messages SET is_read = 0 WHERE id = ?', [$id]),
        'archive' => sql_exec('UPDATE messages SET archived = 1 WHERE id = ?', [$id]),
        'restore' => sql_exec('UPDATE messages SET archived = 0 WHERE id = ?', [$id]),
        'delete' => sql_exec('DELETE FROM messages WHERE id = ?', [$id]),
        default => null,
    };
}

redirect(str_starts_with($back, '/admin') ? $back : '/admin/messages.php');
