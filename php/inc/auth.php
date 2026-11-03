<?php
declare(strict_types=1);

/**
 * Authentification : sessions PHP natives, jeton CSRF, hachage bcrypt,
 * limitation du nombre de tentatives (stockée en base).
 */

function auth_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_name('PORTFOLIOSESSID');
    session_set_cookie_params([
        'lifetime' => (int) cfg('session_ttl', 43200),
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(24));
    }

    $ttl = (int) cfg('session_ttl', 43200);
    if (isset($_SESSION['started_at']) && (time() - (int) $_SESSION['started_at']) > $ttl) {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['csrf'] = bin2hex(random_bytes(24));
    }
    $_SESSION['started_at'] = $_SESSION['started_at'] ?? time();
}

function csrf_token(): string
{
    return (string) ($_SESSION['csrf'] ?? '');
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    $sent = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $expected = csrf_token();
    return $expected !== '' && is_string($sent) && hash_equals($expected, $sent);
}

/** Stoppe la requête si le jeton est absent ou invalide. */
function require_csrf(): void
{
    if (!csrf_valid()) {
        http_response_code(403);
        exit('Jeton de sécurité invalide. Rechargez la page et réessayez.');
    }
}

/* ------------------------------ Compte -------------------------------- */

function hash_password(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function verify_password(string $password, string $hash): bool
{
    return $hash !== '' && password_verify($password, $hash);
}

function current_user(): ?array
{
    static $user = null;
    static $loaded = false;
    if ($loaded) {
        return $user;
    }
    $loaded = true;

    $id = (int) ($_SESSION['uid'] ?? 0);
    if ($id <= 0) {
        return null;
    }

    $found = row('SELECT id, name, email FROM users WHERE id = ?', [$id]);
    if (!$found) {
        unset($_SESSION['uid']);
        return null;
    }

    $user = $found;
    return $user;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $user['id'];
    $_SESSION['started_at'] = time();
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'secure' => (bool) $params['secure'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    session_destroy();
}

/** Protège une page d'administration. */
function require_auth(): array
{
    $user = current_user();
    if ($user) {
        return $user;
    }

    $next = $_SERVER['REQUEST_URI'] ?? '/admin/';
    redirect('/admin/login.php?next=' . rawurlencode($next));
}

/** Nombre de messages non lus (badge du menu). */
function unread_messages(): int
{
    return (int) val('SELECT COUNT(*) FROM messages WHERE is_read = 0 AND archived = 0', [], 0);
}

/* ------------------------ Limitation de débit ------------------------- */

/**
 * Enregistre une tentative et indique si elle est autorisée.
 *
 * @return array{allowed: bool, minutes: int}
 */
function rate_hit(string $key, int $max, int $window): array
{
    $key = substr($key, 0, 190);
    $current = row('SELECT hits, UNIX_TIMESTAMP(window_started) AS started FROM rate_limits WHERE k = ?', [$key]);
    $now = time();

    if ($current === null || ($now - (int) $current['started']) > $window) {
        sql_exec(
            'INSERT INTO rate_limits (k, hits, window_started) VALUES (?, 1, NOW())
             ON DUPLICATE KEY UPDATE hits = 1, window_started = NOW()',
            [$key]
        );
        return ['allowed' => true, 'minutes' => 0];
    }

    if ((int) $current['hits'] >= $max) {
        $remaining = $window - ($now - (int) $current['started']);
        return ['allowed' => false, 'minutes' => max(1, (int) ceil($remaining / 60))];
    }

    sql_exec('UPDATE rate_limits SET hits = hits + 1 WHERE k = ?', [$key]);
    return ['allowed' => true, 'minutes' => 0];
}
