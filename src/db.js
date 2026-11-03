'use strict';

const path = require('node:path');
const fs = require('node:fs');
const mysql = require('mysql2/promise');
const { DEFAULT_SETTINGS } = require('./config');

/**
 * Accès à la base MySQL (WAMP / MariaDB / MySQL 8).
 * Toutes les valeurs de contenu — réglages du site compris — sont stockées
 * en base : rien n'est gardé en mémoire entre deux redémarrages.
 *
 * Variables d'environnement (.env) :
 *   DB_HOST, DB_PORT, DB_USER, DB_PASSWORD, DB_NAME
 */

const dbConfig = {
  host: process.env.DB_HOST || '127.0.0.1',
  port: Number(process.env.DB_PORT || 3306),
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME || 'malick_portfolio'
};

const pool = mysql.createPool({
  ...dbConfig,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0,
  charset: 'utf8mb4',
  // Les DATETIME reviennent sous forme « YYYY-MM-DD HH:MM:SS », comme avant.
  dateStrings: true
});

/* ------------------------------------------------------------------ */
/* Helpers                                                             */
/* ------------------------------------------------------------------ */

const params = (values = []) => values.map((value) => (value === undefined ? null : value));

const all = async (sql, values = []) => {
  const [rows] = await pool.query(sql, params(values));
  return rows;
};

const get = async (sql, values = []) => {
  const [rows] = await pool.query(sql, params(values));
  return rows[0];
};

const run = async (sql, values = []) => {
  const [result] = await pool.query(sql, params(values));
  return result;
};

async function getSettings() {
  const rows = await all('SELECT `key`, `value` FROM settings');
  const stored = {};
  for (const row of rows) stored[row.key] = row.value;
  return { ...DEFAULT_SETTINGS, ...stored };
}

async function setSetting(key, value) {
  await run(
    'INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
    [key, value == null ? '' : String(value)]
  );
}

async function saveSettings(values) {
  for (const [key, value] of Object.entries(values)) {
    if (key in DEFAULT_SETTINGS) await setSetting(key, value);
  }
}

/* ------------------------------------------------------------------ */
/* Migrations                                                          */
/* ------------------------------------------------------------------ */

const MIGRATIONS_DIR = path.join(__dirname, 'migrations');

async function migrate() {
  const files = fs.existsSync(MIGRATIONS_DIR)
    ? fs.readdirSync(MIGRATIONS_DIR).filter((name) => name.endsWith('.sql')).sort()
    : [];

  // On se connecte sans nom de base : elle peut ne pas encore exister.
  const { database, ...server } = dbConfig;
  const connection = await mysql.createConnection({ ...server, multipleStatements: true });
  try {
    await connection.query(
      `CREATE DATABASE IF NOT EXISTS \`${dbConfig.database}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`
    );
    await connection.query(`USE \`${dbConfig.database}\``);
    await connection.query(
      'CREATE TABLE IF NOT EXISTS schema_migrations (name VARCHAR(190) NOT NULL PRIMARY KEY, applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB'
    );

    const [appliedRows] = await connection.query('SELECT name FROM schema_migrations');
    const applied = new Set(appliedRows.map((row) => row.name));

    for (const file of files) {
      if (applied.has(file)) continue;
      const sql = fs.readFileSync(path.join(MIGRATIONS_DIR, file), 'utf8');
      await connection.query(sql);
      await connection.query('INSERT INTO schema_migrations (name) VALUES (?)', [file]);
      console.log(`  Migration → ${file}`);
    }
  } finally {
    await connection.end();
  }
}

/* ------------------------------------------------------------------ */
/* Données de départ                                                   */
/* ------------------------------------------------------------------ */

async function seed() {
  const settingsCount = await get('SELECT COUNT(*) AS n FROM settings');
  if (!settingsCount || settingsCount.n === 0) {
    for (const [key, value] of Object.entries(DEFAULT_SETTINGS)) {
      await run('INSERT IGNORE INTO settings (`key`, `value`) VALUES (?, ?)', [key, value]);
    }
  }

  const navCount = await get('SELECT COUNT(*) AS n FROM nav_items');
  if (!navCount || navCount.n === 0) {
    const items = [
      ['Projets', '#projets'],
      ['À propos', '#apropos'],
      ['Parcours', '#parcours'],
      ['Services', '#services'],
      ['Contact', '#contact']
    ];
    for (const [index, [label, href]] of items.entries()) {
      await run('INSERT INTO nav_items (label, href, sort) VALUES (?, ?, ?)', [label, href, index]);
    }
  }

  const servicesCount = await get('SELECT COUNT(*) AS n FROM services');
  if (!servicesCount || servicesCount.n === 0) {
    const items = [
      ['Sites web sur mesure', 'Sites vitrines, portfolios et plateformes éditoriales, rapides et bien référencés.', '01'],
      ['Applications web', 'Interfaces applicatives complètes : authentification, tableaux de bord, API.', '02'],
      ['Conception UI/UX', 'Recherche utilisateur, wireframes, maquettes Figma et design systems.', '03'],
      ['Intégration de maquettes', 'Découpage pixel-perfect de vos designs, animations et responsive.', '04'],
      ['Solutions personnalisées', 'Automatisations, intégrations d\u2019API et outils métiers internes.', '05']
    ];
    for (const [index, [title, description, icon]] of items.entries()) {
      await run('INSERT INTO services (title, description, icon, sort) VALUES (?, ?, ?, ?)', [
        title,
        description,
        icon,
        index
      ]);
    }
  }

  const processCount = await get('SELECT COUNT(*) AS n FROM process_steps');
  if (!processCount || processCount.n === 0) {
    const items = [
      ['Découverte du besoin', 'On clarifie l\u2019objectif, le public et les contraintes. Questions, pas de suppositions.'],
      ['Analyse & planification', 'Périmètre, priorités, technologies et calendrier. Un plan réaliste, écrit.'],
      ['Conception UI/UX', 'Structure, parcours et maquettes. On valide avant d\u2019écrire la première ligne de code.'],
      ['Développement', 'Code propre, composants réutilisables, points d\u2019étape réguliers.'],
      ['Tests & optimisation', 'Responsive, accessibilité, performance, sécurité.'],
      ['Livraison', 'Mise en ligne, documentation et accompagnement après lancement.']
    ];
    for (const [index, [title, description]] of items.entries()) {
      await run('INSERT INTO process_steps (title, description, sort) VALUES (?, ?, ?)', [title, description, index]);
    }
  }
}

/** Crée la base si besoin, applique les migrations et les données de départ. */
async function init() {
  await migrate();
  await seed();
}

module.exports = {
  pool,
  dbConfig,
  all,
  get,
  run,
  getSettings,
  setSetting,
  saveSettings,
  migrate,
  seed,
  init
};
