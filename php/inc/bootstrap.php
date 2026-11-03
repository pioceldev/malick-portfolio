<?php
declare(strict_types=1);

/**
 * Amorçage commun à toutes les pages.
 * Les pages publiques et le back-office incluent uniquement ce fichier.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/entities.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/data.php';

mb_internal_encoding('UTF-8');

$debug = (bool) cfg('debug', false);
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');

if (!$debug) {
    set_exception_handler(static function (Throwable $error): void {
        error_log('[portfolio] ' . $error->getMessage() . ' @ ' . $error->getFile() . ':' . $error->getLine());
        http_response_code(500);
        echo '<!doctype html><meta charset="utf-8"><title>Erreur</title>'
            . '<div style="font:15px/1.6 system-ui;padding:48px;max-width:640px;margin:0 auto">'
            . '<h1 style="font-size:22px">Une erreur est survenue.</h1>'
            . '<p>Merci de réessayer dans un instant.</p>'
            . '<p><a href="/">Retour à l’accueil</a></p></div>';
    });
}

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

auth_boot();

/** Échappement court dans les gabarits : <?= e($v) ?> */
function view(string $file, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    require $file;
}
