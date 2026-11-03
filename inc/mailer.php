<?php
declare(strict_types=1);

/**
 * Envoi d'e-mails transactionnels via l'API Brevo (v3) — cURL, sans dépendance.
 * Rien à modifier ici : les clés se règlent dans inc/config.php.
 */

const BREVO_ENDPOINT = 'https://api.brevo.com/v3/smtp/email';

function mail_configured(): bool
{
    return trim((string) cfg('mail.api_key', '')) !== ''
        && trim((string) cfg('mail.from_email', '')) !== '';
}

/**
 * Envoie un e-mail.
 *
 * @return array{ok: bool, error?: string, skipped?: bool, status?: int}
 */
function send_mail(string $to, string $toName, string $subject, string $html, string $text, string $replyTo = ''): array
{
    $apiKey = trim((string) cfg('mail.api_key', ''));
    $fromEmail = trim((string) cfg('mail.from_email', ''));
    $fromName = trim((string) cfg('mail.from_name', 'Portfolio'));

    if ($apiKey === '' || $fromEmail === '') {
        return ['ok' => false, 'skipped' => true, 'error' => 'Brevo non configuré (api_key / from_email).'];
    }

    $payload = [
        'sender' => ['email' => $fromEmail, 'name' => $fromName],
        'to' => [['email' => $to, 'name' => $toName !== '' ? $toName : $to]],
        'subject' => $subject,
        'htmlContent' => $html,
        'textContent' => $text,
    ];
    if ($replyTo !== '') {
        $payload['replyTo'] = ['email' => $replyTo];
    }

    $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if (function_exists('curl_init')) {
        $ch = curl_init(BREVO_ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_HTTPHEADER => [
                'api-key: ' . $apiKey,
                'content-type: application/json',
                'accept: application/json',
            ],
            CURLOPT_POSTFIELDS => $body,
        ]);
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['ok' => false, 'error' => $error !== '' ? $error : 'Connexion à Brevo impossible.'];
        }
        if ($status >= 400) {
            $decoded = json_decode((string) $response, true);
            $detail = is_array($decoded) ? (string) ($decoded['message'] ?? $decoded['code'] ?? '') : '';
            return ['ok' => false, 'status' => $status, 'error' => $detail !== '' ? $detail : 'Erreur Brevo ' . $status];
        }
        return ['ok' => true];
    }

    // Repli sans cURL (allow_url_fopen)
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "api-key: {$apiKey}\r\ncontent-type: application/json\r\naccept: application/json\r\n",
            'content' => $body,
            'timeout' => 12,
            'ignore_errors' => true,
        ],
    ]);
    $response = @file_get_contents(BREVO_ENDPOINT, false, $context);
    if ($response === false) {
        return ['ok' => false, 'error' => 'Connexion à Brevo impossible.'];
    }
    preg_match('#HTTP/\S+\s(\d{3})#', (string) ($http_response_header[0] ?? ''), $matches);
    $status = (int) ($matches[1] ?? 0);
    if ($status >= 400) {
        return ['ok' => false, 'status' => $status, 'error' => 'Erreur Brevo ' . $status];
    }
    return ['ok' => true];
}

/** Notifie l'arrivée d'un message du formulaire de contact. */
function send_contact_notification(array $message, array $settings): array
{
    $recipient = trim((string) cfg('mail.to_email', ''));
    if ($recipient === '') {
        return ['ok' => false, 'skipped' => true, 'error' => 'Aucune adresse de réception.'];
    }

    $lines = [
        'Nom' => $message['name'] ?? '',
        'E-mail' => $message['email'] ?? '',
        'Sujet' => ($message['subject'] ?? '') !== '' ? $message['subject'] : '—',
        'Type de projet' => ($message['project_type'] ?? '') !== '' ? $message['project_type'] : '—',
        'Budget' => ($message['budget'] ?? '') !== '' ? $message['budget'] : '—',
    ];

    $rowsHtml = '';
    foreach ($lines as $label => $value) {
        $rowsHtml .= '<tr><td style="padding:6px 0;color:#7a766c;width:130px">' . e($label) . '</td>'
            . '<td style="padding:6px 0">' . e($value) . '</td></tr>';
    }

    $html = '
  <div style="font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;max-width:600px;margin:0 auto;color:#14140f">
    <div style="background:#0a0a0b;color:#f1eee7;padding:22px 24px">
      <div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#c9f24d">Nouveau message</div>
      <div style="font-size:20px;margin-top:6px">' . e($message['name'] ?? '') . '</div>
    </div>
    <div style="padding:24px;border:1px solid #e6e2d9;border-top:0">
      <table style="width:100%;border-collapse:collapse;font-size:14px;margin-bottom:18px">' . $rowsHtml . '</table>
      <div style="font-size:12px;color:#7a766c;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:8px">Message</div>
      <div style="white-space:pre-wrap;line-height:1.65;font-size:15px">' . e($message['body'] ?? '') . '</div>
      <p style="margin-top:26px">
        <a href="mailto:' . e($message['email'] ?? '') . '" style="display:inline-block;background:#c9f24d;color:#0a0a0b;padding:10px 18px;text-decoration:none;font-weight:600;border-radius:999px;font-size:14px">Répondre à ' . e($message['name'] ?? '') . '</a>
      </p>
    </div>
    <p style="font-size:11px;color:#9a968c;padding:14px 2px">Envoyé depuis le formulaire de contact du portfolio.</p>
  </div>';

    $text = implode("\n", [
        'NOUVEAU MESSAGE',
        '',
        'Nom : ' . ($message['name'] ?? ''),
        'E-mail : ' . ($message['email'] ?? ''),
        'Sujet : ' . (($message['subject'] ?? '') !== '' ? $message['subject'] : '—'),
        'Type de projet : ' . (($message['project_type'] ?? '') !== '' ? $message['project_type'] : '—'),
        'Budget : ' . (($message['budget'] ?? '') !== '' ? $message['budget'] : '—'),
        '',
        'Message :',
        $message['body'] ?? '',
    ]);

    $subject = '[Portfolio] ' . (($message['subject'] ?? '') !== '' ? $message['subject'] : 'Nouveau message')
        . ' — ' . ($message['name'] ?? '');

    return send_mail(
        $recipient,
        (string) ($settings['display_name'] ?? 'Portfolio'),
        $subject,
        $html,
        $text,
        (string) ($message['email'] ?? '')
    );
}
