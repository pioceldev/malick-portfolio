<?php
declare(strict_types=1);

if (!function_exists('public_data')) {
    require __DIR__ . '/inc/bootstrap.php';
}

http_response_code(404);

$data = public_data();

$settings = $data['settings'];
$nav = $data['nav'];
$socials = $data['socials'];
$cv = $data['cv'];
$pageTitle = 'Page introuvable — ' . $settings['site_name'];
$metaDescription = $settings['meta_description'];
$canonicalPath = '/404';

include __DIR__ . '/inc/head.php';
include __DIR__ . '/inc/nav.php';
?>

<main id="main" class="error-page">
  <p class="section__kicker">Erreur 404</p>
  <h1 class="error-page__title">Cette page<br><em>n’existe pas.</em></h1>
  <p class="error-page__text">Le lien est peut-être obsolète, ou l’adresse contient une faute de frappe.</p>
  <div class="hero__actions">
    <a class="btn btn--solid" href="<?= e(u('/')) ?>" data-magnetic><span>Retour à l’accueil</span></a>
    <a class="btn btn--ghost" href="<?= e(u('/#contact')) ?>" data-magnetic><span>Me contacter</span></a>
  </div>
</main>

<?php include __DIR__ . '/inc/footer.php'; ?>
