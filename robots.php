<?php
declare(strict_types=1);

require __DIR__ . '/inc/bootstrap.php';

$base = site_base();

header('Content-Type: text/plain; charset=utf-8');
echo "User-agent: *\nAllow: /\nDisallow: /admin\n";
if ($base !== '') {
    echo 'Sitemap: ' . $base . "/sitemap.xml\n";
}
