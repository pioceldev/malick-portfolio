<?php
/**
 * Ouverture de la coque d'administration.
 * Variables attendues : $title, $admin, $activeKey, $csrf.
 */
$siteSettings = settings();
$csrf = csrf_token();
$unreadCount = unread_messages();
$adminInitial = $admin ? mb_strtoupper(mb_substr((string) $admin['name'], 0, 1)) : 'A';

$isActive = static function (string $key) use ($activeKey): string {
    return $activeKey === $key ? 'is-active' : '';
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($title) ?> — Administration</title>
  <link rel="preload" href="/fonts/inter-latin.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="/css/admin.css">
</head>
<body class="admin-body">
<a class="skip-link" href="#admin-main">Aller au contenu</a>

<button class="admin-burger" type="button" id="admin-burger" aria-label="Ouvrir le menu d’administration" aria-expanded="false">
  <span></span><span></span>
</button>

<aside class="sidebar" id="sidebar">
  <a class="sidebar__brand" href="/admin/">
    <span class="sidebar__mark">◈</span>
    <span>
      <strong>Administration</strong>
      <small><?= e($siteSettings['site_name']) ?></small>
    </span>
  </a>

  <nav class="sidebar__nav" aria-label="Navigation administration">
    <p class="sidebar__label">Pilotage</p>
    <a href="/admin/" class="<?= $isActive('dashboard') ?>"><span>Tableau de bord</span></a>
    <a href="/admin/messages.php" class="<?= $isActive('messages') ?>">
      <span>Messages</span>
      <?php if ($unreadCount > 0): ?><em class="badge"><?= $unreadCount ?></em><?php endif; ?>
    </a>
    <a href="/admin/media.php" class="<?= $isActive('media') ?>"><span>Médiathèque</span></a>

    <p class="sidebar__label">Contenu</p>
    <?php foreach (content_entity_keys() as $key): ?>
      <?php $item = get_entity($key); if ($item === null) { continue; } ?>
      <a href="<?= e(entity_list_url($key)) ?>" class="<?= $isActive('entity:' . $key) ?>">
        <span><?= e($item['icon']) ?> <?= e($item['label']) ?></span>
      </a>
    <?php endforeach; ?>

    <p class="sidebar__label">Site</p>
    <a href="/admin/settings.php" class="<?= $isActive('settings') ?>"><span>Réglages</span></a>
    <a href="<?= e(entity_list_url('navigation')) ?>" class="<?= $isActive('entity:navigation') ?>"><span>Navigation</span></a>
    <a href="/admin/account.php" class="<?= $isActive('account') ?>"><span>Mon compte</span></a>
  </nav>

  <div class="sidebar__foot">
    <a class="sidebar__view" href="/" target="_blank" rel="noopener">Voir le site ↗</a>
    <form action="/admin/logout.php" method="post">
      <?= csrf_field() ?>
      <button type="submit" class="sidebar__logout">Déconnexion</button>
    </form>
  </div>
</aside>

<div class="admin-main">
  <header class="admin-topbar">
    <div>
      <p class="admin-topbar__kicker">Administration</p>
      <h1><?= e($title) ?></h1>
    </div>
    <div class="admin-topbar__user">
      <span class="avatar"><?= e($adminInitial) ?></span>
      <span>
        <strong><?= e($admin['name'] ?? '') ?></strong>
        <small><?= e($admin['email'] ?? '') ?></small>
      </span>
    </div>
  </header>

  <main class="admin-content" id="admin-main">
