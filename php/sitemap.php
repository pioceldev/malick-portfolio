<?php
declare(strict_types=1);

require __DIR__ . '/inc/bootstrap.php';

$base = site_base();
$projects = rows('SELECT slug, created_at FROM projects WHERE published = 1');

$urls = ['<url><loc>' . e($base) . '/</loc><changefreq>weekly</changefreq><priority>1.0</priority></url>'];
foreach ($projects as $project) {
    $urls[] = '<url><loc>' . e($base) . '/projets/' . e($project['slug']) . '</loc>'
        . '<lastmod>' . e(substr((string) $project['created_at'], 0, 10)) . '</lastmod>'
        . '<priority>0.8</priority></url>';
}

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
    . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"
    . implode("\n", $urls) . "\n"
    . '</urlset>';
