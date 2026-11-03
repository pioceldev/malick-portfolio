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

if ($entity !== null && $id > 0 && in_array('published', array_column($entity['fields'], 'name'), true)) {
    sql_exec('UPDATE ' . $entity['table'] . ' SET published = 1 - published WHERE id = ?', [$id]);
}

redirect(entity_list_url($entityKey));
