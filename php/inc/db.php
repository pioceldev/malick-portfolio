<?php
declare(strict_types=1);

/**
 * Accès à la base MySQL via PDO.
 * Toutes les valeurs de contenu — réglages du site compris — vivent en base.
 */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        cfg('db.host'),
        (int) cfg('db.port', 3306),
        cfg('db.name')
    );

    $pdo = new PDO($dsn, (string) cfg('db.user'), (string) cfg('db.password'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

/** Prépare et exécute une requête. */
function q(string $sql, array $params = []): PDOStatement
{
    $statement = db()->prepare($sql);
    $statement->execute(array_values($params));
    return $statement;
}

/** Toutes les lignes d'un résultat. */
function rows(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

/** Première ligne (ou null). */
function row(string $sql, array $params = []): ?array
{
    $result = q($sql, $params)->fetch();
    return $result === false ? null : $result;
}

/** Première colonne de la première ligne. */
function val(string $sql, array $params = [], mixed $default = null): mixed
{
    $result = q($sql, $params)->fetchColumn();
    return $result === false ? $default : $result;
}

/** Exécute un INSERT / UPDATE / DELETE. */
function sql_exec(string $sql, array $params = []): void
{
    q($sql, $params);
}

/* ---------------------------------------------------------------------- */
/* Réglages                                                               */
/* ---------------------------------------------------------------------- */

function settings(): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    $stored = [];
    foreach (rows('SELECT `key`, `value` FROM settings') as $item) {
        $stored[$item['key']] = $item['value'];
    }

    $cache = array_merge(DEFAULT_SETTINGS, $stored);
    return $cache;
}

function setting_save(string $key, string $value): void
{
    sql_exec(
        'INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
        [$key, $value]
    );
}

function settings_save(array $values): void
{
    foreach ($values as $key => $value) {
        if (array_key_exists($key, DEFAULT_SETTINGS)) {
            setting_save((string) $key, (string) ($value ?? ''));
        }
    }
}

function count_users(): int
{
    return (int) val('SELECT COUNT(*) FROM users', [], 0);
}
