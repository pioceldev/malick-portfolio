<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/bootstrap.php';

// Déjà connecté
if (current_user()) {
    redirect('/admin/');
}

// Aucun compte : on force la création du premier administrateur
if (count_users() === 0) {
    redirect('/admin/setup.php');
}

$error = '';
$email = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $email = strtolower(post('email'));
    $password = (string) ($_POST['password'] ?? '');

    $limit = rate_hit('login:' . request_ip(), 6, 900);
    if (!$limit['allowed']) {
        http_response_code(429);
        $error = 'Trop de tentatives. Réessayez dans ' . $limit['minutes'] . ' minute(s).';
    } else {
        $user = row('SELECT * FROM users WHERE email = ?', [$email]);
        if ($user === null || !verify_password($password, (string) $user['password_hash'])) {
            http_response_code(401);
            $error = 'Identifiants incorrects.';
        } else {
            login_user($user);
            $next = post('next');
            redirect(str_starts_with($next, '/admin') ? $next : '/admin/');
        }
    }
}

$next = get_param('next', post('next'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Connexion — Administration</title>
  <link rel="stylesheet" href="/css/admin.css">
</head>
<body class="auth-body">
  <div class="auth">
    <section class="auth__panel">
      <p class="auth__kicker">Espace privé</p>
      <h1 class="auth__title">Connexion<br><em>administrateur</em></h1>
      <p class="auth__text">Ce panneau permet de modifier l’intégralité du portfolio : identité, projets, compétences, couleurs et messages reçus.</p>
      <a class="auth__back" href="/">← Retour au site</a>
    </section>

    <section class="auth__form">
      <h2>Accéder au tableau de bord</h2>
      <?php if ($error !== ''): ?><div class="alert alert--error" role="alert"><?= e($error) ?></div><?php endif; ?>

      <form method="post" action="/admin/login.php" class="admin-form">
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <label class="field">
          <span>Adresse e-mail</span>
          <input type="email" name="email" value="<?= e($email) ?>" required autocomplete="username" autofocus>
        </label>
        <label class="field">
          <span>Mot de passe</span>
          <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button class="btn btn--primary btn--block" type="submit">Se connecter</button>
      </form>
      <p class="auth__hint">Accès protégé — 6 tentatives maximum toutes les 15 minutes.</p>
    </section>
  </div>
</body>
</html>
