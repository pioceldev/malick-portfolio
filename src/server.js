'use strict';

require('./env').load();

const app = require('./app');
const { init } = require('./db');

const port = Number(process.env.PORT || 3000);

init()
  .then(() => {
    app.listen(port, () => {
      console.log(`\n  Portfolio → http://localhost:${port}`);
      console.log(`  Admin     → http://localhost:${port}/admin\n`);
      console.log(`  Base      → MySQL ${process.env.DB_NAME || 'malick_portfolio'}\n`);
      if (!process.env.BREVO_API_KEY) {
        console.log('  E-mail    → Brevo non configuré (voir .env.example)\n');
      }
    });
  })
  .catch((error) => {
    console.error('\n  ✗ Connexion MySQL impossible :', error.message);
    console.error('  → Vérifiez que WAMP/MySQL est démarré et les variables DB_* du fichier .env\n');
    process.exit(1);
  });
