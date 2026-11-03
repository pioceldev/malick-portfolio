'use strict';

/**
 * Import unique des données SQLite (ancienne version) vers MySQL.
 *   npm run migrate
 *
 * Copie réglages, compte(s) admin, messages, projets, médias… puis laisse
 * la base SQLite intacte. Relançable : les lignes existantes sont mises à jour.
 */

require('../src/env').load();

const path = require('node:path');
const fs = require('node:fs');
const mysql = require('mysql2/promise');

const { dbConfig, init, pool } = require('../src/db');

const ROOT = path.join(__dirname, '..');
const SQLITE_PATH = path.isAbsolute(process.env.DATABASE_PATH || '')
  ? process.env.DATABASE_PATH
  : path.join(ROOT, process.env.DATABASE_PATH || 'data/portfolio.db');

// L'ordre respecte les clés étrangères (sessions → users).
const TABLES = [
  { name: 'users', pk: 'id' },
  { name: 'sessions', pk: 'token' },
  { name: 'settings', pk: 'key' },
  { name: 'projects', pk: 'id' },
  { name: 'skills', pk: 'id' },
  { name: 'experiences', pk: 'id' },
  { name: 'services', pk: 'id' },
  { name: 'process_steps', pk: 'id' },
  { name: 'testimonials', pk: 'id' },
  { name: 'messages', pk: 'id' },
  { name: 'media', pk: 'id' },
  { name: 'nav_items', pk: 'id' }
];

async function main() {
  console.log('\nImport SQLite → MySQL');
  console.log('  Source      :', SQLITE_PATH);
  console.log('  Destination :', `${dbConfig.user}@${dbConfig.host}:${dbConfig.port}/${dbConfig.database}`);
  console.log('');

  if (!fs.existsSync(SQLITE_PATH)) {
    console.log('  Aucun fichier SQLite trouvé : rien à importer (base MySQL initialisée à neuf).\n');
    await init();
    await pool.end();
    return;
  }

  const { DatabaseSync } = require('node:sqlite');
  const sqlite = new DatabaseSync(SQLITE_PATH, { readOnly: true });
  await init();

  const connection = await mysql.createConnection({ ...dbConfig, dateStrings: true });
  try {
    await connection.beginTransaction();

    for (const { name, pk } of TABLES) {
      const exists = sqlite
        .prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name = ?")
        .get(name);
      if (!exists) continue;

      const rows = sqlite.prepare(`SELECT * FROM ${name}`).all();
      if (!rows.length) {
        console.log(`  ${name.padEnd(14)} 0 ligne`);
        continue;
      }

      const columns = Object.keys(rows[0]);
      const updates = columns
        .filter((column) => column !== pk)
        .map((column) => `\`${column}\` = VALUES(\`${column}\`)`)
        .join(', ');

      const sql =
        `INSERT INTO \`${name}\` (${columns.map((column) => `\`${column}\``).join(', ')})` +
        ` VALUES (${columns.map(() => '?').join(', ')})` +
        (updates ? ` ON DUPLICATE KEY UPDATE ${updates}` : '');

      for (const row of rows) {
        await connection.query(sql, columns.map((column) => row[column]));
      }
      console.log(`  ${name.padEnd(14)} ${rows.length} ligne(s) importée(s)`);
    }

    await connection.commit();
    console.log('\n✓ Import terminé. Le fichier SQLite n’a pas été modifié.\n');
  } catch (error) {
    await connection.rollback();
    console.error('\n✗ Import annulé :', error.message, '\n');
    process.exitCode = 1;
  } finally {
    await connection.end();
    await pool.end();
    sqlite.close();
  }
}

main().catch((error) => {
  console.error('\n✗ Échec :', error.message, '\n');
  process.exit(1);
});
