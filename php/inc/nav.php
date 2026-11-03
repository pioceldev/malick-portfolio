<?php
/** Navigation publique. Variables attendues : $settings, $nav, $socials, $cv. */
?>
<header class="nav" id="nav">
  <a class="nav__brand" href="/" aria-label="<?= e($settings['display_name']) ?> — accueil">
    <span class="nav__mark" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($settings['display_name'] ?: 'M', 0, 1))) ?></span>
    <span class="nav__name"><?= e($settings['display_name']) ?></span>
  </a>

  <nav class="nav__links" aria-label="Navigation principale">
    <?php foreach ($nav as $item): ?>
      <a class="nav__link" href="<?= e($item['href']) ?>"><span><?= e($item['label']) ?></span></a>
    <?php endforeach; ?>
  </nav>

  <div class="nav__actions">
    <?php if ($cv !== ''): ?>
      <a class="btn btn--ghost nav__cv" href="<?= e(image_url($cv)) ?>" download>
        <span>CV</span>
      </a>
    <?php endif; ?>
    <a class="btn btn--solid nav__cta" href="#contact" data-magnetic>
      <span>Me contacter</span>
    </a>
    <button class="nav__burger" type="button" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="mobile-menu">
      <span></span><span></span>
    </button>
  </div>
</header>

<div class="mobile-menu" id="mobile-menu" hidden>
  <nav aria-label="Navigation mobile">
    <?php foreach ($nav as $index => $item): ?>
      <a class="mobile-menu__link" href="<?= e($item['href']) ?>" style="--i:<?= (int) $index ?>">
        <span class="mobile-menu__num">0<?= (int) $index + 1 ?></span>
        <span><?= e($item['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="mobile-menu__foot">
    <?php if (!empty($settings['email'])): ?><a href="mailto:<?= e($settings['email']) ?>"><?= e($settings['email']) ?></a><?php endif; ?>
    <div class="mobile-menu__socials">
      <?php foreach ($socials as $social): ?>
        <a href="<?= e($social['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($social['label']) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
