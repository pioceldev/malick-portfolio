'use strict';

const crypto = require('node:crypto');
const { get, run } = require('./db');

const SESSION_COOKIE = 'sid';
const SESSION_TTL_MS = 1000 * 60 * 60 * 12; // 12 h

/* ---------------------------- Mots de passe --------------------------- */

function hashPassword(password) {
  const salt = crypto.randomBytes(16);
  const derived = crypto.scryptSync(password, salt, 64, { N: 16384, r: 8, p: 1 });
  return `scrypt$${salt.toString('hex')}$${derived.toString('hex')}`;
}

function verifyPassword(password, stored) {
  if (typeof stored !== 'string') return false;
  const [scheme, saltHex, hashHex] = stored.split('$');
  if (scheme !== 'scrypt' || !saltHex || !hashHex) return false;
  const salt = Buffer.from(saltHex, 'hex');
  const expected = Buffer.from(hashHex, 'hex');
  let derived;
  try {
    derived = crypto.scryptSync(password, salt, expected.length, { N: 16384, r: 8, p: 1 });
  } catch {
    return false;
  }
  return expected.length === derived.length && crypto.timingSafeEqual(expected, derived);
}

/* ------------------------------ Sessions ----------------------------- */

async function createSession(userId, res) {
  const token = crypto.randomBytes(32).toString('hex');
  const csrf = crypto.randomBytes(24).toString('hex');
  const expires = new Date(Date.now() + SESSION_TTL_MS);
  await run('INSERT INTO sessions (token, user_id, csrf, expires_at) VALUES (?, ?, ?, ?)', [
    token,
    userId,
    csrf,
    expires
  ]);
  await run('DELETE FROM sessions WHERE expires_at < NOW()');
  res.cookie(SESSION_COOKIE, token, {
    httpOnly: true,
    sameSite: 'lax',
    secure: process.env.NODE_ENV === 'production',
    maxAge: SESSION_TTL_MS,
    path: '/'
  });
  return token;
}

async function destroySession(req, res) {
  const token = req.cookies ? req.cookies[SESSION_COOKIE] : null;
  if (token) await run('DELETE FROM sessions WHERE token = ?', [token]);
  res.clearCookie(SESSION_COOKIE, { path: '/' });
}

function parseCookies(header = '') {
  const out = {};
  for (const part of header.split(';')) {
    const index = part.indexOf('=');
    if (index === -1) continue;
    const key = part.slice(0, index).trim();
    const value = part.slice(index + 1).trim();
    if (key) out[key] = decodeURIComponent(value);
  }
  return out;
}

/** Charge la session (si valide) et l'utilisateur courant sur req. */
async function sessionMiddleware(req, res, next) {
  req.cookies = parseCookies(req.headers.cookie || '');
  req.user = null;
  req.session = null;

  const token = req.cookies[SESSION_COOKIE];
  if (token && /^[a-f0-9]{64}$/.test(token)) {
    const row = await get(
      `SELECT s.token, s.csrf, s.expires_at, u.id, u.email, u.name
         FROM sessions s JOIN users u ON u.id = s.user_id
        WHERE s.token = ? AND s.expires_at > NOW()`,
      [token]
    );
    if (row) {
      req.session = row;
      req.user = { id: row.id, email: row.email, name: row.name };
    } else {
      await run('DELETE FROM sessions WHERE token = ? AND expires_at <= NOW()', [token]);
    }
  }
  next();
}

/** Protection des routes d'administration. */
function requireAuth(req, res, next) {
  if (req.user) return next();
  if (req.path.startsWith('/api') || req.get('accept') === 'application/json') {
    return res.status(401).json({ error: 'Non authentifié' });
  }
  return res.redirect('/admin/login?next=' + encodeURIComponent(req.originalUrl));
}

/* -------------------------------- CSRF ------------------------------- */

function csrfToken(req) {
  return req.session ? req.session.csrf : '';
}

function isCsrfValid(req) {
  const sent = (req.body && req.body._csrf) || req.get('x-csrf-token') || '';
  const expected = req.session ? req.session.csrf : '';
  const a = Buffer.from(String(sent));
  const b = Buffer.from(String(expected));
  if (!expected || a.length !== b.length) return false;
  return crypto.timingSafeEqual(a, b);
}

function verifyCsrf(req, res, next) {
  if (!isCsrfValid(req)) {
    return res.status(403).send('Jeton de sécurité invalide. Rechargez la page et réessayez.');
  }
  next();
}

/* ------------------------- Limitation de débit ----------------------- */

const attempts = new Map();
const WINDOW_MS = 15 * 60 * 1000;
const MAX_ATTEMPTS = 6;

function loginRateLimit(req, res, next) {
  const key = req.ip || 'unknown';
  const now = Date.now();
  const entry = attempts.get(key) || { count: 0, first: now };
  if (now - entry.first > WINDOW_MS) {
    entry.count = 0;
    entry.first = now;
  }
  if (entry.count >= MAX_ATTEMPTS) {
    const minutes = Math.ceil((WINDOW_MS - (now - entry.first)) / 60000);
    return res.status(429).render('admin/login', {
      title: 'Connexion',
      error: `Trop de tentatives. Réessayez dans ${minutes} minute(s).`,
      email: '',
      next: req.query.next || '',
      layout: 'admin-bare'
    });
  }
  entry.count += 1;
  attempts.set(key, entry);
  req.resetLoginAttempts = () => attempts.delete(key);
  next();
}

async function countUsers() {
  const row = await get('SELECT COUNT(*) AS n FROM users');
  return Number(row.n);
}

module.exports = {
  hashPassword,
  verifyPassword,
  createSession,
  destroySession,
  sessionMiddleware,
  requireAuth,
  csrfToken,
  isCsrfValid,
  verifyCsrf,
  loginRateLimit,
  countUsers,
  SESSION_COOKIE
};
