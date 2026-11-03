<?php
/**
 * En-tête public.
 * Variables attendues : $settings, $pageTitle, $metaDescription, $ogImage,
 * $canonicalPath, $bodyClass.
 */
$pageTitle = $pageTitle ?? ($settings['site_name'] ?? '');
$metaDescription = $metaDescription ?? ($settings['meta_description'] ?? '');
$canonicalPath = $canonicalPath ?? '/';
$bodyClass = $bodyClass ?? '';
$base = site_base();

$share = $ogImage ?? '';
if ($share === '' && !empty($settings['og_image'])) {
    $share = image_url($settings['og_image']);
}
if ($share !== '' && $base !== '' && !preg_match('#^https?://#i', $share)) {
    $share = $base . $share;
}

$socialLinks = [];
foreach (['github', 'linkedin', 'x', 'dribbble', 'behance', 'instagram', 'facebook', 'youtube'] as $network) {
    if (!empty($settings[$network])) {
        $socialLinks[] = $settings[$network];
    }
}

$schema = array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Person',
    'name' => $settings['full_name'] ?: $settings['display_name'],
    'jobTitle' => $settings['role_title'],
    'description' => $settings['meta_description'],
    'url' => $base !== '' ? $base : null,
    'email' => !empty($settings['email']) ? 'mailto:' . $settings['email'] : null,
    'address' => !empty($settings['location'])
        ? ['@type' => 'PostalAddress', 'addressLocality' => $settings['location']]
        : null,
    'sameAs' => $socialLinks ?: null,
], static fn ($value) => $value !== null);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e($metaDescription) ?>">
  <?php if (!empty($settings['keywords'])): ?><meta name="keywords" content="<?= e($settings['keywords']) ?>"><?php endif; ?>
  <meta name="author" content="<?= e($settings['full_name'] ?: $settings['display_name']) ?>">
  <meta name="theme-color" content="<?= e($settings['ink_color']) ?>">
  <meta name="robots" content="index, follow">
  <?php if ($base !== ''): ?><link rel="canonical" href="<?= e($base . $canonicalPath) ?>"><?php endif; ?>

  <meta property="og:type" content="website">
  <meta property="og:site_name" content="<?= e($settings['site_name']) ?>">
  <meta property="og:title" content="<?= e($pageTitle) ?>">
  <meta property="og:description" content="<?= e($metaDescription) ?>">
  <?php if ($base !== ''): ?><meta property="og:url" content="<?= e($base . $canonicalPath) ?>"><?php endif; ?>
  <?php if ($share !== ''): ?><meta property="og:image" content="<?= e($share) ?>"><?php endif; ?>
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($pageTitle) ?>">
  <meta name="twitter:description" content="<?= e($metaDescription) ?>">

  <?php if (!empty($settings['favicon'])): ?>
    <link rel="icon" href="<?= e(image_url($settings['favicon'])) ?>">
  <?php else: ?>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' fill='%230A0A0B'/><text x='16' y='23' font-family='Georgia,serif' font-size='19' fill='%23C9F24D' text-anchor='middle'><?= e(mb_substr($settings['display_name'] ?: 'M', 0, 1)) ?></text></svg>">
  <?php endif; ?>

  <link rel="preload" href="<?= e(u('/fonts/inter-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="<?= e(u('/fonts/instrument-serif-italic.woff2')) ?>" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?= e(u('/css/site.css')) ?>">
  <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
</head>
<body class="<?= e($bodyClass) ?>" style="
  --accent: <?= e($settings['accent_color']) ?>;
  --ink: <?= e($settings['ink_color']) ?>;
  --surface: <?= e($settings['surface_color']) ?>;
  --text: <?= e($settings['text_color']) ?>;
  --muted: <?= e($settings['muted_color']) ?>;
  --font-display: '<?= e($settings['font_display']) ?>', Georgia, serif;
  --font-body: '<?= e($settings['font_body']) ?>', system-ui, sans-serif;
  --motion: <?= $settings['motion'] === 'désactivées' ? 'off' : 'on' ?>;
">
<a class="skip-link" href="#main">Aller au contenu</a>
<div class="grain" aria-hidden="true"></div>
<div class="cursor" aria-hidden="true"><span class="cursor__dot"></span><span class="cursor__ring"></span><span class="cursor__label"></span></div>
<div class="scroll-progress" aria-hidden="true"><span></span></div>
