<?php
declare(strict_types=1);

require __DIR__ . '/inc/bootstrap.php';

$data = public_data();

$settings = $data['settings'];
$nav = $data['nav'];
$projects = $data['projects'];
$skills = $data['skills'];
$skillGroups = $data['skillGroups'];
$experiences = $data['experiences'];
$services = $data['services'];
$processSteps = $data['processSteps'];
$testimonials = $data['testimonials'];
$socials = $data['socials'];
$cv = $data['cv'];

$pageTitle = $settings['meta_title'] ?: $settings['site_name'];
$metaDescription = $settings['meta_description'];
$canonicalPath = '/';
$sent = ($_GET['sent'] ?? '') === '1';
$contactError = is_string($_GET['error'] ?? null) ? (string) $_GET['error'] : '';

include __DIR__ . '/inc/head.php';
include __DIR__ . '/inc/nav.php';
?>

<main id="main">

  <!-- ============================ HERO ============================ -->
  <section class="hero" id="top">
    <div class="hero__grid">
      <div class="hero__lead">
        <div class="hero__meta reveal" data-delay="0">
          <?php if (!empty($settings['location'])): ?><span class="hero__chip"><?= e($settings['location']) ?></span><?php endif; ?>
          <?php if (!empty($settings['availability'])): ?>
            <span class="hero__chip hero__chip--live"><span class="dot"></span><?= e($settings['availability']) ?></span>
          <?php endif; ?>
        </div>

        <h1 class="hero__title">
          <span class="line"><span class="line__inner"><?= e($settings['pro_name'] ?: $settings['display_name']) ?></span></span>
          <span class="line line--sub"><span class="line__inner"><em><?= e($settings['role_title']) ?></em></span></span>
        </h1>

        <p class="hero__tagline reveal" data-delay="2"><?= e($settings['tagline']) ?></p>

        <div class="hero__actions reveal" data-delay="3">
          <?php if ($projects): ?>
            <a class="btn btn--solid" href="#projets" data-magnetic><span>Découvrir mes projets</span></a>
          <?php endif; ?>
          <a class="btn btn--ghost" href="#contact" data-magnetic><span>Me contacter</span></a>
        </div>

        <div class="hero__foot reveal" data-delay="4">
          <span class="hero__scroll"><span class="hero__scroll-line"></span>Faites défiler</span>
          <?php if (!empty($settings['email'])): ?>
            <a class="hero__mail" href="mailto:<?= e($settings['email']) ?>"><?= e($settings['email']) ?></a>
          <?php endif; ?>
        </div>
      </div>

      <figure class="hero__portrait reveal" data-delay="2">
        <?php if (!empty($settings['portrait'])): ?>
          <img src="<?= e(image_url($settings['portrait'])) ?>" alt="Portrait de <?= e($settings['display_name']) ?>" width="640" height="840" loading="eager" decoding="async">
        <?php else: ?>
          <div class="hero__monogram" aria-hidden="true">
            <span><?= e(mb_strtoupper(mb_substr($settings['display_name'] ?: 'M', 0, 1))) ?></span>
            <small><?= e($settings['pro_name'] ?: $settings['display_name']) ?></small>
          </div>
        <?php endif; ?>
        <figcaption><?= e($settings['role_title']) ?></figcaption>
      </figure>
    </div>

    <div class="marquee" aria-hidden="true">
      <div class="marquee__track">
        <?php
        $words = $skills
            ? array_map(static fn (array $skill): string => (string) $skill['name'], $skills)
            : ['Développement Full-Stack', 'Développement Web', 'React.js', 'UI/UX Design', 'WordPress', 'HTML / CSS / JavaScript', 'PHP & Laravel', 'Python & SQL', 'Figma', 'Git & GitHub', 'Applications Web', 'Responsive Design'];
        for ($loop = 0; $loop < 2; $loop++):
            foreach ($words as $word): ?>
              <span class="marquee__item"><?= e($word) ?><i>✳</i></span>
            <?php endforeach;
        endfor; ?>
      </div>
    </div>
  </section>

  <!-- =========================== PROJETS ========================== -->
  <?php if ($projects): ?>
  <section class="section section--work" id="projets">
    <header class="section__head">
      <p class="section__kicker reveal"><?= e($settings['work_kicker']) ?></p>
      <h2 class="section__title reveal"><?= e($settings['work_title']) ?></h2>
      <p class="section__count reveal"><?= str_pad((string) count($projects), 2, '0', STR_PAD_LEFT) ?> projets</p>
    </header>

    <div class="projects" data-cursor="Voir">
      <figure class="projects__preview" aria-hidden="true"><img alt="" src="" width="520" height="340"></figure>
      <?php foreach ($projects as $index => $project): ?>
        <a class="project reveal" href="<?= e(project_url((string) $project['slug'])) ?>"
           data-cover="<?= e($project['coverUrl']) ?>"
           data-delay="<?= (int) $index % 3 ?>">
          <span class="project__index"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <span class="project__main">
            <span class="project__title"><?= e($project['title']) ?></span>
            <span class="project__summary"><?= e(truncate_text($project['summary'] ?? '', 110)) ?></span>
          </span>
          <span class="project__meta">
            <span><?= e($project['category']) ?></span>
            <span><?= e($project['year']) ?></span>
          </span>
          <span class="project__arrow" aria-hidden="true">↗</span>
          <span class="project__thumb" aria-hidden="true">
            <?php if ($project['coverUrl'] !== ''): ?><img src="<?= e($project['coverUrl']) ?>" alt="" loading="lazy" decoding="async"><?php endif; ?>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ========================== À PROPOS ========================== -->
  <section class="section section--about" id="apropos">
    <header class="section__head">
      <p class="section__kicker reveal"><?= e($settings['about_kicker']) ?></p>
      <h2 class="section__title reveal"><?= e($settings['about_title']) ?></h2>
    </header>

    <div class="about">
      <div class="about__text reveal">
        <?= paragraphs($settings['bio']) ?>
        <?php if (!empty($settings['vision'])): ?>
          <blockquote class="about__quote"><?= paragraphs($settings['vision']) ?></blockquote>
        <?php endif; ?>
        <?php if (!empty($settings['goals'])): ?>
          <p class="about__label">Objectifs</p>
          <?= paragraphs($settings['goals']) ?>
        <?php endif; ?>
        <?php if (!empty($settings['interests'])): ?>
          <ul class="tags">
            <?php foreach (parse_list($settings['interests']) as $item): ?>
              <li><?= e($item) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

      <aside class="about__aside">
        <?php
        $stats = array_values(array_filter([
            ['value' => $settings['stat_1_value'], 'label' => $settings['stat_1_label']],
            ['value' => $settings['stat_2_value'], 'label' => $settings['stat_2_label']],
            ['value' => $settings['stat_3_value'], 'label' => $settings['stat_3_label']],
        ], static fn (array $stat): bool => trim((string) $stat['value']) !== ''));
        ?>
        <?php if ($stats): ?>
          <ul class="stats">
            <?php foreach ($stats as $stat): ?>
              <li class="reveal"><span class="stats__value"><?= e($stat['value']) ?></span><span class="stats__label"><?= e($stat['label']) ?></span></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <?php if (!empty($settings['portrait'])): ?>
          <figure class="about__photo reveal">
            <img src="<?= e(image_url($settings['portrait'])) ?>" alt="<?= e($settings['display_name']) ?>" loading="lazy" decoding="async" width="480" height="600">
          </figure>
        <?php endif; ?>
      </aside>
    </div>
  </section>

  <!-- ========================= COMPÉTENCES ======================== -->
  <?php if ($skills): ?>
  <section class="section section--skills" id="competences">
    <header class="section__head">
      <p class="section__kicker reveal"><?= e($settings['skills_kicker']) ?></p>
      <h2 class="section__title reveal"><?= e($settings['skills_title']) ?></h2>
    </header>

    <div class="skills">
      <?php $groupIndex = 0; foreach ($skillGroups as $category => $items): ?>
        <div class="skills__group reveal" data-delay="<?= $groupIndex++ % 3 ?>">
          <h3 class="skills__category"><span><?= e($category) ?></span><span class="skills__count"><?= str_pad((string) count($items), 2, '0', STR_PAD_LEFT) ?></span></h3>
          <ul>
            <?php foreach ($items as $skill): ?>
              <li class="skill">
                <span class="skill__name"><?= e($skill['name']) ?></span>
                <?php if ((int) $skill['level'] > 0): ?>
                  <span class="skill__meter"><span style="--level: <?= min(100, (int) $skill['level']) ?>%"></span></span>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- =========================== PARCOURS ========================= -->
  <?php if ($experiences): ?>
  <section class="section section--timeline" id="parcours">
    <header class="section__head">
      <p class="section__kicker reveal"><?= e($settings['exp_kicker']) ?></p>
      <h2 class="section__title reveal"><?= e($settings['exp_title']) ?></h2>
    </header>

    <ol class="timeline">
      <?php foreach ($experiences as $index => $item): ?>
        <li class="timeline__item reveal" data-delay="<?= (int) $index % 3 ?>">
          <span class="timeline__date"><?= e($item['start_date']) ?><?php if (!empty($item['end_date'])): ?><br><span><?= e($item['end_date']) ?></span><?php endif; ?></span>
          <div class="timeline__body">
            <span class="timeline__kind"><?= e($item['kind']) ?></span>
            <h3 class="timeline__title"><?= e($item['title']) ?><?php if (!empty($item['org'])): ?> <span>· <?= e($item['org']) ?></span><?php endif; ?></h3>
            <?php if (!empty($item['description'])): ?><?= paragraphs($item['description']) ?><?php endif; ?>
            <div class="timeline__links">
              <?php if (!empty($item['url'])): ?><a href="<?= e($item['url']) ?>" target="_blank" rel="noopener noreferrer">Voir le lien ↗</a><?php endif; ?>
              <?php if (!empty($item['document'])): ?><a href="<?= e(image_url($item['document'])) ?>" target="_blank" rel="noopener">Document ↗</a><?php endif; ?>
            </div>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </section>
  <?php endif; ?>

  <!-- =========================== SERVICES ========================= -->
  <?php if ($services): ?>
  <section class="section section--services" id="services">
    <header class="section__head">
      <p class="section__kicker reveal"><?= e($settings['services_kicker']) ?></p>
      <h2 class="section__title reveal"><?= e($settings['services_title']) ?></h2>
    </header>

    <ul class="services">
      <?php foreach ($services as $index => $service): ?>
        <li class="service reveal" data-delay="<?= (int) $index % 3 ?>">
          <span class="service__num"><?= e($service['icon'] ?: str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span>
          <h3 class="service__title"><?= e($service['title']) ?></h3>
          <p class="service__text"><?= e($service['description']) ?></p>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>

  <!-- =========================== PROCESSUS ======================== -->
  <?php if ($processSteps): ?>
  <section class="section section--process" id="processus">
    <header class="section__head">
      <p class="section__kicker reveal"><?= e($settings['process_kicker']) ?></p>
      <h2 class="section__title reveal"><?= e($settings['process_title']) ?></h2>
    </header>

    <ol class="process">
      <?php foreach ($processSteps as $index => $step): ?>
        <li class="process__step reveal" data-delay="<?= (int) $index % 3 ?>">
          <span class="process__num"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div>
            <h3><?= e($step['title']) ?></h3>
            <p><?= e($step['description']) ?></p>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </section>
  <?php endif; ?>

  <!-- ========================== TÉMOIGNAGES ======================= -->
  <?php if ($testimonials): ?>
  <section class="section section--quotes" id="temoignages">
    <header class="section__head">
      <p class="section__kicker reveal"><?= e($settings['testimonials_kicker']) ?></p>
      <h2 class="section__title reveal"><?= e($settings['testimonials_title']) ?></h2>
    </header>

    <div class="quotes">
      <?php foreach ($testimonials as $index => $item): ?>
        <figure class="quote reveal" data-delay="<?= (int) $index % 3 ?>">
          <blockquote>« <?= e($item['message']) ?> »</blockquote>
          <figcaption>
            <?php if (!empty($item['photo'])): ?><img src="<?= e(image_url($item['photo'])) ?>" alt="" width="48" height="48" loading="lazy"><?php endif; ?>
            <span>
              <strong><?= e($item['name']) ?></strong>
              <small><?= e(implode(' · ', array_filter([(string) $item['role'], (string) $item['company']]))) ?></small>
            </span>
            <?php if ((int) $item['rating'] > 0): ?><span class="quote__stars" aria-label="<?= (int) $item['rating'] ?> sur 5"><?= str_repeat('★', min(5, (int) $item['rating'])) ?></span><?php endif; ?>
          </figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ============================ CONTACT ========================= -->
  <section class="section section--contact" id="contact">
    <header class="section__head">
      <p class="section__kicker reveal"><?= e($settings['contact_kicker']) ?></p>
      <h2 class="section__title reveal"><?= e($settings['contact_title']) ?></h2>
    </header>

    <div class="contact">
      <div class="contact__info reveal">
        <?php if (!empty($settings['contact_intro'])): ?><p class="contact__intro"><?= e($settings['contact_intro']) ?></p><?php endif; ?>

        <ul class="contact__list">
          <?php if (!empty($settings['email'])): ?>
            <li><span>E-mail</span><a class="contact__big" href="mailto:<?= e($settings['email']) ?>" data-magnetic><?= e($settings['email']) ?></a></li>
          <?php endif; ?>
          <?php if (!empty($settings['phone'])): ?>
            <li><span>Téléphone</span><a href="tel:<?= e(preg_replace('/\s+/', '', $settings['phone'])) ?>"><?= e($settings['phone']) ?></a></li>
          <?php endif; ?>
          <?php if (!empty($settings['whatsapp'])): ?>
            <li><span>WhatsApp</span><a href="https://wa.me/<?= e(preg_replace('/[^0-9]/', '', $settings['whatsapp'])) ?>" target="_blank" rel="noopener noreferrer">Discuter sur WhatsApp ↗</a></li>
          <?php endif; ?>
          <?php if (!empty($settings['location'])): ?><li><span>Localisation</span><p><?= e($settings['location']) ?></p></li><?php endif; ?>
        </ul>

        <div class="contact__socials">
          <?php foreach ($socials as $social): ?>
            <a href="<?= e($social['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($social['label']) ?> ↗</a>
          <?php endforeach; ?>
        </div>

        <?php if ($cv !== ''): ?>
          <a class="cv-card" href="<?= e(image_url($cv)) ?>" download data-magnetic>
            <span class="cv-card__label">PDF</span>
            <span class="cv-card__body">
              <strong><?= e($settings['portfolio_cta_title'] ?: 'Télécharger mon CV') ?></strong>
              <small><?= e($settings['portfolio_cta_text']) ?></small>
            </span>
            <span class="cv-card__arrow" aria-hidden="true">↓</span>
          </a>
        <?php endif; ?>
      </div>

      <div class="contact__form-wrap reveal">
        <?php if ($sent): ?>
          <div class="notice notice--ok" role="status">
            <strong>Message envoyé.</strong>
            <span>Merci, je reviens vers vous sous 48 h.</span>
          </div>
        <?php endif; ?>
        <?php if ($contactError !== ''): ?>
          <div class="notice notice--error" role="alert"><?= e($contactError) ?></div>
        <?php endif; ?>

        <form class="form" action="<?= e(u('/contact.php')) ?>" method="post" novalidate>
          <input type="hidden" name="elapsed" value="0" data-elapsed>
          <div class="form__honeypot" aria-hidden="true">
            <label>Ne pas remplir<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
          </div>

          <div class="form__row">
            <label class="field">
              <span>Nom<em>*</em></span>
              <input type="text" name="name" required minlength="2" autocomplete="name" placeholder="Votre nom">
            </label>
            <label class="field">
              <span>E-mail<em>*</em></span>
              <input type="email" name="email" required autocomplete="email" placeholder="vous@entreprise.com">
            </label>
          </div>

          <div class="form__row">
            <label class="field">
              <span>Type de projet</span>
              <select name="project_type">
                <option value="">Choisir…</option>
                <option>Site web</option>
                <option>Application web</option>
                <option>UI/UX Design</option>
                <option>Intégration</option>
                <option>Autre</option>
              </select>
            </label>
            <label class="field">
              <span>Budget (optionnel)</span>
              <input type="text" name="budget" placeholder="Ex. 1 500 €">
            </label>
          </div>

          <label class="field">
            <span>Sujet</span>
            <input type="text" name="subject" placeholder="En deux mots">
          </label>

          <label class="field">
            <span>Message<em>*</em></span>
            <textarea name="message" rows="5" required minlength="10" placeholder="Parlez-moi de votre projet, de vos objectifs et de votre échéance."></textarea>
          </label>

          <button class="btn btn--solid btn--wide" type="submit" data-magnetic><span>Envoyer le message</span></button>
          <p class="form__legal">Vos données servent uniquement à répondre à votre message. Aucun transfert à des tiers.</p>
        </form>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/inc/footer.php'; ?>
