'use strict';

const { escapeHtml } = require('./util');

/**
 * Envoi d'e-mails transactionnels via l'API Brevo (v3).
 * Une seule clé à changer : BREVO_API_KEY dans .env
 * (plus l'expéditeur validé et l'adresse de réception).
 */

const ENDPOINT = 'https://api.brevo.com/v3/smtp/email';

// Boîte de réception du portfolio : les notifications y sont envoyées
// directement, quel que soit l'e-mail public affiché sur le site.
const DEFAULT_RECIPIENT = 'malickabdou54@gmail.com';

function mailConfig() {
  return {
    apiKey: (process.env.BREVO_API_KEY || '').trim(),
    fromEmail: (process.env.MAIL_FROM_EMAIL || '').trim(),
    fromName: (process.env.MAIL_FROM_NAME || 'Portfolio').trim(),
    toEmail: (process.env.MAIL_TO_EMAIL || DEFAULT_RECIPIENT).trim()
  };
}

function isMailConfigured() {
  const { apiKey, fromEmail } = mailConfig();
  return Boolean(apiKey && fromEmail);
}

async function sendMail({ to, toName = '', subject, html, text, replyTo }) {
  const { apiKey, fromEmail, fromName } = mailConfig();
  if (!apiKey || !fromEmail) {
    return { ok: false, skipped: true, error: 'Brevo non configuré (BREVO_API_KEY / MAIL_FROM_EMAIL).' };
  }

  const payload = {
    sender: { email: fromEmail, name: fromName },
    to: [{ email: to, name: toName || to }],
    subject,
    htmlContent: html,
    textContent: text
  };
  if (replyTo) payload.replyTo = { email: replyTo };

  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), 12000);

  try {
    const response = await fetch(ENDPOINT, {
      method: 'POST',
      headers: {
        'api-key': apiKey,
        'content-type': 'application/json',
        accept: 'application/json'
      },
      body: JSON.stringify(payload),
      signal: controller.signal
    });

    if (!response.ok) {
      let detail = '';
      try {
        const body = await response.json();
        detail = body.message || body.code || JSON.stringify(body);
      } catch {
        detail = await response.text().catch(() => '');
      }
      return { ok: false, status: response.status, error: detail || `Erreur Brevo ${response.status}` };
    }

    const body = await response.json().catch(() => ({}));
    return { ok: true, messageId: body.messageId };
  } catch (error) {
    const reason = error.name === 'AbortError' ? 'Délai dépassé (12 s).' : error.message;
    return { ok: false, error: reason };
  } finally {
    clearTimeout(timer);
  }
}

/** Compose et envoie la notification d'un message du formulaire de contact. */
async function sendContactNotification(message, settings = {}) {
  const config = mailConfig();
  const recipient = config.toEmail;
  if (!recipient) {
    return { ok: false, skipped: true, error: 'Aucune adresse de réception (MAIL_TO_EMAIL).' };
  }

  const rows = [
    ['Nom', message.name],
    ['E-mail', message.email],
    ['Sujet', message.subject || '—'],
    ['Type de projet', message.project_type || '—'],
    ['Budget', message.budget || '—']
  ];

  const html = `
  <div style="font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;max-width:600px;margin:0 auto;color:#14140f">
    <div style="background:#0a0a0b;color:#f1eee7;padding:22px 24px">
      <div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#c9f24d">Nouveau message</div>
      <div style="font-size:20px;margin-top:6px">${escapeHtml(message.name)}</div>
    </div>
    <div style="padding:24px;border:1px solid #e6e2d9;border-top:0">
      <table style="width:100%;border-collapse:collapse;font-size:14px;margin-bottom:18px">
        ${rows
          .map(
            ([label, value]) =>
              `<tr><td style="padding:6px 0;color:#7a766c;width:130px">${label}</td><td style="padding:6px 0">${escapeHtml(value)}</td></tr>`
          )
          .join('')}
      </table>
      <div style="font-size:12px;color:#7a766c;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:8px">Message</div>
      <div style="white-space:pre-wrap;line-height:1.65;font-size:15px">${escapeHtml(message.body)}</div>
      <p style="margin-top:26px">
        <a href="mailto:${escapeHtml(message.email)}" style="display:inline-block;background:#c9f24d;color:#0a0a0b;padding:10px 18px;text-decoration:none;font-weight:600;border-radius:999px;font-size:14px">Répondre à ${escapeHtml(message.name)}</a>
      </p>
    </div>
    <p style="font-size:11px;color:#9a968c;padding:14px 2px">Envoyé depuis le formulaire de contact du portfolio.</p>
  </div>`;

  const text = [
    'NOUVEAU MESSAGE',
    '',
    `Nom : ${message.name}`,
    `E-mail : ${message.email}`,
    `Sujet : ${message.subject || '—'}`,
    `Type de projet : ${message.project_type || '—'}`,
    `Budget : ${message.budget || '—'}`,
    '',
    'Message :',
    message.body
  ].join('\n');

  return sendMail({
    to: recipient,
    toName: settings.display_name || 'Portfolio',
    subject: `[Portfolio] ${message.subject || 'Nouveau message'} — ${message.name}`,
    html,
    text,
    replyTo: message.email
  });
}

module.exports = { sendMail, sendContactNotification, isMailConfigured, mailConfig, DEFAULT_RECIPIENT };
