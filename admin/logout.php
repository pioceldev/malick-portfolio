<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect('/admin/');
}

require_csrf();
logout_user();
redirect('/admin/login.php');
