<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/bootstrap.php';

$admin = require_auth();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect('/admin/');
}
require_csrf();

$entityKey = post('entity');
$entity = get_entity($entityKey);
$id = to_int(post('id'));

if ($entity !== null && $id > 0) {
    sql_exec('DELETE FROM ' . $entity['table'] . ' WHERE id = ?', [$id]);
}

redirect(entity_list_url($entityKey) . '&deleted=1');
