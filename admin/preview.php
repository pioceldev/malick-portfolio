<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/bootstrap.php';

$admin = require_auth();

$id = to_int(get_param('id'));
if ($id <= 0) {
    redirect('/admin/');
}

redirect('/projet.php?preview=1&id=' . $id);
