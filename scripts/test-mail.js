'use strict';

/**
 * Vérifie la configuration Brevo sans passer par le site :
 *   npm run mail:test
 */

require('../src/env').load();

const { sendMail, mailConfig, isMailConfigured } = require('../src/mailer');

async function main() {
  const config = mailConfig();

  console.log('\nConfiguration Brevo');
  console.log('  Clé API      :', config.apiKey ? `présente (${config.apiKey.slice(0, 8)}…)` : 'MANQUANTE');
  console.log('  Expéditeur   :', config.fromEmail || 'MANQUANT');
  console.log('  Destinataire :', config.toEmail || 'MANQUANT');
  console.log('');

  if (!isMailConfigured()) {
    console.error('Renseignez BREVO_API_KEY et MAIL_FROM_EMAIL dans le fichier .env, puis relancez.');
    process.exit(1);
  }

  const result = await sendMail({
    to: config.toEmail || config.fromEmail,
    subject: '[Portfolio] Test d’envoi Brevo',
    html: '<p>Si vous lisez ce message, l’envoi d’e-mails du portfolio fonctionne.</p>',
    text: 'Si vous lisez ce message, l’envoi d’e-mails du portfolio fonctionne.'
  });

  if (result.ok) {
    console.log('✓ E-mail envoyé. Identifiant Brevo :', result.messageId);
    return;
  }

  console.error('✗ Échec :', result.error);
  if (result.status === 401) {
    console.error('  → Clé API invalide ou révoquée.');
  } else if (result.status === 400) {
    console.error('  → Vérifiez que MAIL_FROM_EMAIL est un expéditeur validé dans Brevo.');
  }
  process.exit(1);
}

main();
