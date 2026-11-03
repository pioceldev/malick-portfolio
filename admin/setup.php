<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/bootstrap.php';

// La page disparaît dès qu'un administrateur existe.
if (count_users() > 0) {
    redirect('/admin/login.php');
}

$error = '';
$name = '';
$email = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $limit = rate_hit('setup:' . request_ip(), 6, 900);
    if (!$limit['allowed']) {
        http_response_code(429);
        $error = 'Trop de tentatives. Réessayez dans ' . $limit['minutes'] . ' minute(s).';
    } else {
        $name = mb_substr(post('name'), 0, 80);
        $email = strtolower(post('email'));
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['confirm'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            $error = 'Adresse e-mail invalide.';
        } elseif (strlen($password) < 10) {
            http_response_code(400);
            $error = 'Le mot de passe doit contenir au moins 10 caractères.';
        } elseif ($password !== $confirm) {
            http_response_code(400);
            $error = 'Les deux mots de passe ne correspondent pas.';
        } else {
            sql_exec(
                'INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)',
                [$name !== '' ? $name : 'Administrateur', $email, hash_password($password)]
            );
            login_user((array) row('SELECT id, name, email FROM users WHERE email = ?', [$email]));
            redirect('/admin/');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Création du compte — Administration</title>
  <link rel="stylesheet" href="<?= e(u('/css/admin.css')) ?>">
</head>
<body class="auth-body">
  <div class="auth">
    <section class="auth__panel">
      <p class="auth__kicker">Première configuration</p>
      <h1 class="auth__title">Créez votre<br><em>compte admin</em></h1>
      <p class="auth__text">Aucun compte n’existe encore. Cette page disparaîtra automatiquement dès que votre compte sera créé.</p>
      <a class="auth__back" href="<?= e(u('/')) ?>">← Retour au site</a>
    </section>

    <section class="auth__form">
      <h2>Votre compte</h2>
      <?php if ($error !== ''): ?><div class="alert alert--error" role="alert"><?= e($error) ?></div><?php endif; ?>

      <form method="post" action="<?= e(u('/admin/setup.php')) ?>" class="admin-form">
        <label class="field">
          <span>Nom</span>
          <input type="text" name="name" value="<?= e($name) ?>" required autocomplete="name">
        </label>
        <label class="field">
          <span>Adresse e-mail</span>
          <input type="email" name="email" value="<?= e($email) ?>" required autocomplete="username">
        </label>
        <label class="field">
          <span>Mot de passe (10 caractères minimum)</span>
          <input type="password" name="password" required minlength="10" autocomplete="new-password">
        </label>
        <label class="field">
          <span>Confirmer le mot de passe</span>
          <input type="password" name="confirm" required minlength="10" autocomplete="new-password">
        </label>
        <button class="btn btn--primary btn--block" type="submit">Créer le compte</button>
      </form>
      <p class="auth__hint">Le mot de passe est haché avec bcrypt : il n’est jamais stocké en clair.</p>
    </section>
  </div>
</body>
</html>
