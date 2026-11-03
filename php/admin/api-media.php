<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/bootstrap.php';

$admin = require_auth();

$media = rows('SELECT id, filename, alt, mime FROM media ORDER BY id DESC');

header('Content-Type: application/json; charset=utf-8');
echo json_encode($media, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
