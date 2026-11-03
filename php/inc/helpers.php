<?php
declare(strict_types=1);

/** Échappe une valeur pour du HTML. */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** URL publique du site, sans slash final. */
function site_base(): string
{
    $url = trim((string) (settings()['canonical_url'] ?? ''));
    if ($url === '') {
        $url = trim((string) cfg('site_url', ''));
    }
    return rtrim($url, '/');
}

/** Transforme un nom de fichier média en URL publique. */
function image_url(?string $filename, string $fallback = ''): string
{
    $filename = trim((string) $filename);
    if ($filename === '') {
        return $fallback;
    }
    if (preg_match('#^https?://#i', $filename)) {
        return $filename;
    }
    return rtrim((string) cfg('uploads_url', '/uploads'), '/') . '/' . rawurlencode(basename($filename));
}

/** Texte brut → paragraphes HTML sûrs (une ligne vide = nouveau paragraphe). */
function paragraphs(?string $value): string
{
    $blocks = preg_split('/\n\s*\n/', trim((string) $value)) ?: [];
    $out = [];
    foreach ($blocks as $block) {
        $block = trim($block);
        if ($block === '') {
            continue;
        }
        $out[] = '<p>' . str_replace("\n", '<br>', e($block)) . '</p>';
    }
    return implode("\n", $out);
}

/** Slug URL à partir d'un titre. */
function slugify(string $value): string
{
    $value = trim($value);
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        if ($converted !== false) {
            $value = $converted;
        }
    }
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    $value = trim($value, '-');
    return substr($value, 0, 80);
}

/** « a, b et c » */
function listing(array $items): string
{
    $items = array_values(array_filter($items, static fn ($item) => $item !== null && $item !== ''));
    $count = count($items);
    if ($count === 0) {
        return '';
    }
    if ($count === 1) {
        return (string) $items[0];
    }
    $last = array_pop($items);
    return implode(', ', $items) . ' et ' . $last;
}

function truncate_text(?string $value, int $max = 160): string
{
    $text = trim((string) preg_replace('/\s+/', ' ', (string) $value));
    if (mb_strlen($text) <= $max) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $max - 1)) . '…';
}

function format_period(?string $start, ?string $end): string
{
    if (!$start && !$end) {
        return '';
    }
    if (!$end) {
        return (string) $start;
    }
    return $start . ' — ' . $end;
}

/** « React, Node.js » → ['React', 'Node.js'] */
function parse_list(?string $value): array
{
    $parts = array_map('trim', explode(',', (string) $value));
    return array_values(array_filter($parts, static fn ($item) => $item !== ''));
}

function to_bool(mixed $value): int
{
    return in_array($value, ['1', 'on', 'true', true, 1], true) ? 1 : 0;
}

function to_int(mixed $value, int $fallback = 0): int
{
    return is_numeric($value) ? (int) $value : $fallback;
}

function file_size_label(?int $bytes): string
{
    $bytes = (int) $bytes;
    if ($bytes <= 0) {
        return '—';
    }
    if ($bytes < 1024) {
        return $bytes . ' o';
    }
    if ($bytes < 1024 * 1024) {
        return (string) round($bytes / 1024) . ' Ko';
    }
    return number_format($bytes / 1024 / 1024, 1, ',', ' ') . ' Mo';
}

function is_pdf(?string $filename): bool
{
    return (bool) preg_match('/\.pdf$/i', (string) $filename);
}

/** Chemin courant sans query string. */
function current_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    return $path;
}

function redirect(string $url): void
{
    header('Location: ' . $url, true, 302);
    exit;
}

function request_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

/** Lit une valeur POST (chaîne nettoyée). */
function post(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

/** Lit une valeur GET (chaîne nettoyée). */
function get_param(string $key, string $default = ''): string
{
    $value = $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

/** Année courante dans le copyright. */
function copyright_line(string $template): string
{
    return str_replace('{year}', (string) date('Y'), $template);
}
