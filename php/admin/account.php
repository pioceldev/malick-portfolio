<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/bootstrap.php';

$admin = require_auth();
$title = 'Mon compte';
$activeKey = 'account';

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require_csrf();

    $name = mb_substr(post('name'), 0, 80);
    $email = strtolower(post('email'));
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse e-mail invalide.';
    } elseif ($password !== '' && strlen($password) < 10) {
        $error = 'Le nouveau mot de passe doit contenir au moins 10 caractères.';
    } elseif ($password !== '' && $password !== $confirm) {
        $error = 'Les deux mots de passe ne correspondent pas.';
    } else {
        try {
            if ($password !== '') {
                sql_exec(
                    'UPDATE users SET name = ?, email = ?, password_hash = ? WHERE id = ?',
                    [$name, $email, hash_password($password), (int) $admin['id']]
                );
                session_regenerate_id(true);
            } else {
                sql_exec('UPDATE users SET name = ?, email = ? WHERE id = ?', [$name, $email, (int) $admin['id']]);
            }
            redirect('/admin/account.php?saved=1');
        } catch (PDOException $exception) {
            $error = str_contains($exception->getMessage(), 'Duplicate')
                ? 'Cette adresse e-mail est déjà utilisée.'
                : 'Enregistrement impossible.';
        }
    }

    $admin = ['id' => $admin['id'], 'name' => $name, 'email' => $email];
}

$saved = get_param('saved') === '1' && $error === '';

include dirname(__DIR__) . '/inc/admin-start.php';
?>

<?php if ($saved): ?><div class="alert alert--ok">Compte mis à jour.</div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert--error" role="alert"><?= e($error) ?></div><?php endif; ?>

<div class="two-col">
  <section class="panel">
    <h2 class="panel__title">Identifiants</h2>
    <form class="admin-form" method="post" action="/admin/account.php">
      <?= csrf_field() ?>
      <label class="field">
        <span>Nom</span>
        <input type="text" name="name" value="<?= e($admin['name']) ?>" required autocomplete="name">
      </label>
      <label class="field">
        <span>Adresse e-mail</span>
        <input type="email" name="email" value="<?= e($admin['email']) ?>" required autocomplete="username">
      </label>
      <label class="field">
        <span>Nouveau mot de passe (optionnel)</span>
        <input type="password" name="password" minlength="10" autocomplete="new-password" placeholder="Laisser vide pour conserver">
      </label>
      <label class="field">
        <span>Confirmer le mot de passe</span>
        <input type="password" name="confirm" minlength="10" autocomplete="new-password">
      </label>
      <button class="btn btn--primary" type="submit">Enregistrer</button>
    </form>
  </section>

  <section class="panel panel--help">
    <h2 class="panel__title">Sécurité</h2>
    <ul class="help-list">
      <li>Les mots de passe sont hachés avec bcrypt et un sel aléatoire.</li>
      <li>La session expire automatiquement après 12 h.</li>
      <li>Les formulaires du back-office sont protégés par un jeton CSRF.</li>
      <li>Après un changement de mot de passe, la session est renouvelée.</li>
      <li>La page de création de compte disparaît dès qu’un administrateur existe.</li>
    </ul>
    <p class="muted">Servez le site en HTTPS pour activer les cookies sécurisés.</p>
  </section>
</div>

<?php include dirname(__DIR__) . '/inc/admin-end.php'; ?>
