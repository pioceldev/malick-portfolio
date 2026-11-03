<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/bootstrap.php';

$admin = require_auth();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect('/admin/media.php');
}
require_csrf();

$id = to_int(post('id'));
if ($id > 0) {
    sql_exec('UPDATE media SET alt = ? WHERE id = ?', [mb_substr(post('alt'), 0, 200), $id]);
}

redirect('/admin/media.php');
