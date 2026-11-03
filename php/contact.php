<?php
declare(strict_types=1);

require __DIR__ . '/inc/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect('/#contact');
}

/* ------------------------- Limite anti-spam ------------------------- */

$limit = rate_hit('contact:' . request_ip(), 5, 3600);
if (!$limit['allowed']) {
    redirect('/?error=' . rawurlencode('Trop de messages envoyés. Merci de réessayer plus tard.') . '#contact');
}

$name = mb_substr(post('name'), 0, 120);
$email = mb_substr(post('email'), 0, 180);
$subject = mb_substr(post('subject'), 0, 160);
$projectType = mb_substr(post('project_type'), 0, 80);
$budget = mb_substr(post('budget'), 0, 60);
$body = mb_substr(post('message'), 0, 5000);
$honeypot = post('website');
$elapsed = (int) post('elapsed', '0');

// Piège à robots + délai minimal de remplissage
if ($honeypot !== '' || $elapsed < 2500) {
    redirect('/?sent=1#contact');
}

$emailOk = (bool) filter_var($email, FILTER_VALIDATE_EMAIL);

if (mb_strlen($name) < 2 || !$emailOk || mb_strlen($body) < 10) {
    redirect('/?error=' . rawurlencode('Merci de vérifier votre nom, votre e-mail et votre message (10 caractères minimum).') . '#contact');
}

/* --------------------------- Enregistrement -------------------------- */

sql_exec(
    'INSERT INTO messages (name, email, subject, project_type, budget, body) VALUES (?, ?, ?, ?, ?, ?)',
    [$name, $email, $subject, $projectType, $budget, $body]
);

// Le message est enregistré quoi qu'il arrive : l'e-mail ne doit jamais
// bloquer le visiteur.
try {
    $result = send_contact_notification([
        'name' => $name,
        'email' => $email,
        'subject' => $subject,
        'project_type' => $projectType,
        'budget' => $budget,
        'body' => $body,
    ], settings());

    if (empty($result['ok']) && empty($result['skipped'])) {
        error_log('[brevo] notification non envoyée : ' . ($result['error'] ?? 'inconnue'));
    }
} catch (Throwable $error) {
    error_log('[brevo] erreur inattendue : ' . $error->getMessage());
}

redirect('/?sent=1#contact');
