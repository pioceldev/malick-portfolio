<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/bootstrap.php';
require dirname(__DIR__) . '/inc/uploads.php';

$admin = require_auth();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect('/admin/media.php');
}
require_csrf();

$id = to_int(post('id'));
$item = $id > 0 ? row('SELECT * FROM media WHERE id = ?', [$id]) : null;

if ($item !== null) {
    delete_upload((string) $item['filename']);
    sql_exec('DELETE FROM media WHERE id = ?', [$id]);
}

redirect('/admin/media.php');
