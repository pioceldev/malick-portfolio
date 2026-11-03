<?php
/** Pied de page public. Variables attendues : $settings, $nav, $socials. */
$footerHref = static fn (string $href): string => str_starts_with($href, '/') ? u($href) : $href;
?>
<footer class="footer" id="footer">
  <div class="footer__top">
    <div class="footer__identity">
      <p class="footer__kicker"><?= e($settings['role_title']) ?></p>
      <p class="footer__name"><?= e($settings['pro_name'] ?: $settings['display_name']) ?></p>
      <?php if (!empty($settings['footer_note'])): ?><p class="footer__note"><?= e($settings['footer_note']) ?></p><?php endif; ?>
    </div>

    <div class="footer__cols">
      <div class="footer__col">
        <p class="footer__label">Navigation</p>
        <?php foreach ($nav as $item): ?><a href="<?= e($footerHref((string) $item['href'])) ?>"><?= e($item['label']) ?></a><?php endforeach; ?>
      </div>
      <div class="footer__col">
        <p class="footer__label">Contact</p>
        <?php if (!empty($settings['email'])): ?><a href="mailto:<?= e($settings['email']) ?>"><?= e($settings['email']) ?></a><?php endif; ?>
        <?php if (!empty($settings['phone'])): ?><a href="tel:<?= e(preg_replace('/\s+/', '', $settings['phone'])) ?>"><?= e($settings['phone']) ?></a><?php endif; ?>
        <?php if (!empty($settings['location'])): ?><span><?= e($settings['location']) ?></span><?php endif; ?>
      </div>
      <div class="footer__col">
        <p class="footer__label">Réseaux</p>
        <?php if (!$socials): ?><span class="footer__muted">À compléter</span><?php endif; ?>
        <?php foreach ($socials as $social): ?>
          <a href="<?= e($social['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($social['label']) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="footer__bottom">
    <p><?= e(copyright_line((string) $settings['copyright'])) ?></p>
    <a class="footer__up" href="#top" data-magnetic>Retour en haut <span aria-hidden="true">↑</span></a>
  </div>
</footer>
<script src="<?= e(u('/js/site.js')) ?>" defer></script>
</body>
</html>
