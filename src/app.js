'use strict';

const path = require('node:path');
const fs = require('node:fs');
const crypto = require('node:crypto');
const express = require('express');
const multer = require('multer');

const { all, get, run, getSettings, saveSettings } = require('./db');
const { entities, getEntity } = require('./entities');
const { SETTINGS_GROUPS, SOCIAL_FIELDS } = require('./config');
const {
  sessionMiddleware,
  requireAuth,
  createSession,
  destroySession,
  verifyPassword,
  hashPassword,
  csrfToken,
  verifyCsrf,
  isCsrfValid,
  loginRateLimit,
  countUsers
} = require('./auth');
const {
  slugify,
  paragraphs,
  imageUrl,
  listing,
  parseList,
  toBoolean,
  toInt,
  fileSizeLabel,
  isPdf,
  escapeHtml
} = require('./util');

const ROOT = path.join(__dirname, '..');
const PUBLIC_DIR = path.join(ROOT, 'public');
const UPLOAD_DIR = path.join(PUBLIC_DIR, 'uploads');
fs.mkdirSync(UPLOAD_DIR, { recursive: true });

const { sendContactNotification } = require('./mailer');

const app = express();

app.set('view engine', 'ejs');
app.set('views', path.join(ROOT, 'views'));
app.set('trust proxy', 1);

// Helpers disponibles dans toutes les vues (publiques et admin)
app.locals.imageUrl = imageUrl;
app.locals.paragraphs = paragraphs;
app.locals.listing = listing;
app.locals.truncate = (value, max = 160) => {
  const text = String(value || '').replace(/\s+/g, ' ').trim();
  return text.length > max ? text.slice(0, max - 1).trimEnd() + '…' : text;
};
app.locals.fileSizeLabel = fileSizeLabel;
app.locals.isPdf = isPdf;

app.use(express.urlencoded({ extended: false, limit: '1mb' }));
app.use(express.json({ limit: '1mb' }));
app.use(sessionMiddleware);
app.use(
  express.static(PUBLIC_DIR, {
    maxAge: process.env.NODE_ENV === 'production' ? '7d' : 0,
    etag: true
  })
);

// Locals partagés par toutes les vues
app.use(async (req, res, next) => {
  res.locals.admin = req.user;
  res.locals.currentPath = req.path;
  res.locals.siteSettings = await getSettings();
  if (req.user) {
    res.locals.csrf = csrfToken(req);
    res.locals.entities = entities;
    const unread = await get('SELECT COUNT(*) AS n FROM messages WHERE is_read = 0 AND archived = 0');
    res.locals.unreadCount = Number(unread.n);
  }
  next();
});

/* ------------------------------ Uploads ------------------------------ */

const ALLOWED = new Set([
  'image/jpeg',
  'image/png',
  'image/webp',
  'image/avif',
  'image/gif',
  'image/svg+xml',
  'application/pdf'
]);

const upload = multer({
  storage: multer.diskStorage({
    destination: (req, file, cb) => cb(null, UPLOAD_DIR),
    filename: (req, file, cb) => {
      const ext = path.extname(file.originalname).toLowerCase().slice(0, 8) || '';
      cb(null, `${Date.now().toString(36)}-${crypto.randomBytes(4).toString('hex')}${ext}`);
    }
  }),
  limits: { fileSize: 12 * 1024 * 1024, files: 12 },
  fileFilter: (req, file, cb) => {
    if (ALLOWED.has(file.mimetype)) return cb(null, true);
    cb(new Error('Format de fichier non autorisé (images ou PDF uniquement).'));
  }
});

async function recordMedia(file) {
  await run('INSERT INTO media (filename, original_name, alt, mime, size) VALUES (?, ?, ?, ?, ?)', [
    file.filename,
    file.originalname,
    '',
    file.mimetype,
    file.size
  ]);
}

function cleanupUploads(req) {
  for (const file of req.files || []) {
    try {
      fs.unlinkSync(file.path);
    } catch {
      /* fichier déjà supprimé */
    }
  }
}

/**
 * En multipart/form-data, le corps n'est lisible qu'après multer :
 * on analyse donc les fichiers d'abord, puis le jeton CSRF.
 * En cas de jeton invalide, les fichiers déjà écrits sont supprimés.
 */
function uploadWithCsrf(multerMiddleware) {
  return (req, res, next) => {
    multerMiddleware(req, res, (error) => {
      if (error) return next(error);
      if (!isCsrfValid(req)) {
        cleanupUploads(req);
        return res.status(403).send('Jeton de sécurité invalide. Rechargez la page et réessayez.');
      }
      next();
    });
  };
}

async function mediaAsFiles(files = []) {
  const names = [];
  for (const file of files) {
    await recordMedia(file);
    names.push(file.filename);
  }
  return names;
}

/* --------------------------- Helpers données ------------------------- */

function safeJson(value, fallback = []) {
  try {
    const parsed = JSON.parse(value || '[]');
    return Array.isArray(parsed) ? parsed : fallback;
  } catch {
    return fallback;
  }
}

function mapProject(row) {
  if (!row) return null;
  return {
    ...row,
    techList: parseList(row.tech),
    galleryList: safeJson(row.gallery),
    coverUrl: imageUrl(row.cover),
    demo: row.demo_url,
    repo: row.repo_url
  };
}

async function getPublicProjects() {
  const rows = await all(
    `SELECT * FROM projects WHERE published = 1
      ORDER BY featured DESC, sort ASC, COALESCE(NULLIF(year,''), '0') DESC, id DESC`
  );
  return rows.map(mapProject);
}

async function getPublicData() {
  const settings = await getSettings();
  const projects = await getPublicProjects();
  const skills = await all('SELECT * FROM skills WHERE published = 1 ORDER BY sort ASC, id ASC');
  const skillGroups = skills.reduce((groups, skill) => {
    const key = skill.category || 'Autres';
    (groups[key] = groups[key] || []).push(skill);
    return groups;
  }, {});

  return {
    settings,
    nav: await all('SELECT * FROM nav_items WHERE published = 1 ORDER BY sort ASC, id ASC'),
    projects,
    featuredProjects: projects.filter((project) => project.featured).concat(projects.filter((p) => !p.featured)).slice(0, 3),
    skillGroups,
    skills,
    experiences: await all('SELECT * FROM experiences WHERE published = 1 ORDER BY sort ASC, id DESC'),
    services: await all('SELECT * FROM services WHERE published = 1 ORDER BY sort ASC, id ASC'),
    processSteps: await all('SELECT * FROM process_steps WHERE published = 1 ORDER BY sort ASC, id ASC'),
    testimonials: await all('SELECT * FROM testimonials WHERE published = 1 ORDER BY sort ASC, id ASC'),
    socials: SOCIAL_FIELDS.map((field) => ({ ...field, url: settings[field.key] })).filter((item) => item.url),
    cv: settings.cv_file || ''
  };
}

function viewHelpers(settings) {
  return {
    settings,
    asset: (file) => imageUrl(file),
    paragraphs,
    imageUrl,
    listing,
    formatPeriod: (start, end) => {
      if (!start && !end) return '';
      if (!end) return start || '';
      return `${start} — ${end}`;
    },
    truncate: (value, max = 160) => {
      const text = String(value || '').replace(/\s+/g, ' ').trim();
      return text.length > max ? text.slice(0, max - 1).trimEnd() + '…' : text;
    },
    siteUrl: (settings.canonical_url || process.env.SITE_URL || '').replace(/\/$/, '')
  };
}

/* ------------------------------- Public ------------------------------ */

app.get('/', async (req, res) => {
  const data = await getPublicData();
  res.render('public/home', {
    ...data,
    ...viewHelpers(data.settings),
    pageTitle: data.settings.meta_title || data.settings.site_name,
    sent: req.query.sent === '1',
    contactError: req.query.error || ''
  });
});

app.get('/projets/:slug', async (req, res, next) => {
  const row = await get('SELECT * FROM projects WHERE slug = ? AND published = 1', [req.params.slug]);
  if (!row) return next();
  const data = await getPublicData();
  const project = mapProject(row);
  const related = data.projects.filter((item) => item.id !== project.id && item.category === project.category).slice(0, 2);
  res.render('public/project', {
    ...data,
    ...viewHelpers(data.settings),
    project,
    related,
    pageTitle: `${project.title} — ${data.settings.site_name}`,
    metaDescription: project.summary || data.settings.meta_description,
    ogImage: project.coverUrl
  });
});

const contactAttempts = new Map();

app.post('/contact', async (req, res) => {
  const ip = req.ip || 'unknown';
  const now = Date.now();
  const entry = contactAttempts.get(ip) || { count: 0, first: now };
  if (now - entry.first > 60 * 60 * 1000) {
    entry.count = 0;
    entry.first = now;
  }
  entry.count += 1;
  contactAttempts.set(ip, entry);

  if (entry.count > 5) {
    return res.redirect('/?error=' + encodeURIComponent('Trop de messages envoyés. Merci de réessayer plus tard.') + '#contact');
  }

  const { name = '', email = '', subject = '', project_type = '', budget = '', message = '', website = '', elapsed = '0' } = req.body;

  // Honeypot + délai minimal : bloque les robots de spam
  if (website || Number(elapsed) < 2500) {
    return res.redirect('/?sent=1#contact');
  }

  const clean = {
    name: String(name).trim().slice(0, 120),
    email: String(email).trim().slice(0, 180),
    subject: String(subject).trim().slice(0, 160),
    project_type: String(project_type).trim().slice(0, 80),
    budget: String(budget).trim().slice(0, 60),
    body: String(message).trim().slice(0, 5000)
  };

  const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(clean.email);
  if (clean.name.length < 2 || !emailOk || clean.body.length < 10) {
    return res.redirect('/?error=' + encodeURIComponent('Merci de vérifier votre nom, votre e-mail et votre message (10 caractères minimum).') + '#contact');
  }

  await run(
    `INSERT INTO messages (name, email, subject, project_type, budget, body)
     VALUES (?, ?, ?, ?, ?, ?)`,
    [clean.name, clean.email, clean.subject, clean.project_type, clean.budget, clean.body]
  );

  // Le message est enregistré en base quoi qu'il arrive.
  // La notification e-mail ne doit jamais bloquer l'utilisateur.
  try {
    const result = await sendContactNotification(clean, await getSettings());
    if (!result.ok) {
      console.warn('[brevo] notification non envoyée :', result.error);
    }
  } catch (error) {
    console.warn('[brevo] erreur inattendue :', error.message);
  }

  res.redirect('/?sent=1#contact');
});

app.get('/robots.txt', async (req, res) => {
  const settings = await getSettings();
  const base = (settings.canonical_url || process.env.SITE_URL || '').replace(/\/$/, '');
  res.type('text/plain').send(`User-agent: *\nAllow: /\nDisallow: /admin\n${base ? `Sitemap: ${base}/sitemap.xml\n` : ''}`);
});

app.get('/sitemap.xml', async (req, res) => {
  const settings = await getSettings();
  const base = (settings.canonical_url || process.env.SITE_URL || '').replace(/\/$/, '');
  const projects = await all('SELECT slug, created_at FROM projects WHERE published = 1');
  const urls = [
    `<url><loc>${base}/</loc><changefreq>weekly</changefreq><priority>1.0</priority></url>`,
    ...projects.map(
      (project) =>
        `<url><loc>${base}/projets/${project.slug}</loc><lastmod>${String(project.created_at).slice(0, 10)}</lastmod><priority>0.8</priority></url>`
    )
  ];
  res
    .type('application/xml')
    .send(`<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${urls.join('\n')}\n</urlset>`);
});

/* ------------------------------- Admin ------------------------------- */

const adminRouter = express.Router();

adminRouter.use((req, res, next) => {
  if (req.path === '/login' || req.path === '/setup') return next();
  return requireAuth(req, res, next);
});

adminRouter.get('/setup', async (req, res) => {
  if ((await countUsers()) > 0) return res.redirect('/admin/login');
  res.render('admin/setup', { title: 'Création du compte', error: '', values: {} });
});

adminRouter.post('/setup', loginRateLimit, async (req, res) => {
  if ((await countUsers()) > 0) return res.redirect('/admin/login');
  const name = String(req.body.name || '').trim().slice(0, 80) || 'Administrateur';
  const email = String(req.body.email || '').trim().toLowerCase();
  const password = String(req.body.password || '');
  const confirm = String(req.body.confirm || '');

  const fail = (error) =>
    res.status(400).render('admin/setup', { title: 'Création du compte', error, values: { name, email } });

  if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email)) return fail('Adresse e-mail invalide.');
  if (password.length < 10) return fail('Le mot de passe doit contenir au moins 10 caractères.');
  if (password !== confirm) return fail('Les deux mots de passe ne correspondent pas.');

  await run('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)', [name, email, hashPassword(password)]);
  const user = await get('SELECT id FROM users WHERE email = ?', [email]);
  await createSession(user.id, res);
  res.redirect('/admin');
});

adminRouter.get('/login', async (req, res) => {
  if (req.user) return res.redirect('/admin');
  if ((await countUsers()) === 0) return res.redirect('/admin/setup');
  res.render('admin/login', { title: 'Connexion', error: '', email: '', next: req.query.next || '' });
});

adminRouter.post('/login', loginRateLimit, async (req, res) => {
  const email = String(req.body.email || '').trim().toLowerCase();
  const password = String(req.body.password || '');
  const user = await get('SELECT * FROM users WHERE email = ?', [email]);

  if (!user || !verifyPassword(password, user.password_hash)) {
    return res.status(401).render('admin/login', {
      title: 'Connexion',
      error: 'Identifiants incorrects.',
      email,
      next: req.body.next || ''
    });
  }

  await createSession(user.id, res);
  if (typeof req.resetLoginAttempts === 'function') req.resetLoginAttempts();
  const next = String(req.body.next || '');
  res.redirect(next.startsWith('/admin') ? next : '/admin');
});

adminRouter.post('/logout', verifyCsrf, async (req, res) => {
  await destroySession(req, res);
  res.redirect('/admin/login');
});

/* ------------------------------ Dashboard ---------------------------- */

adminRouter.get('/', async (req, res) => {
  const counts = {
    projects: Number((await get('SELECT COUNT(*) AS n FROM projects')).n),
    skills: Number((await get('SELECT COUNT(*) AS n FROM skills')).n),
    experiences: Number((await get('SELECT COUNT(*) AS n FROM experiences')).n),
    messages: Number((await get('SELECT COUNT(*) AS n FROM messages WHERE archived = 0')).n),
    unread: Number((await get('SELECT COUNT(*) AS n FROM messages WHERE is_read = 0 AND archived = 0')).n),
    testimonials: Number((await get('SELECT COUNT(*) AS n FROM testimonials')).n),
    media: Number((await get('SELECT COUNT(*) AS n FROM media')).n)
  };
  const recentMessages = await all('SELECT * FROM messages ORDER BY id DESC LIMIT 5');
  const recentProjects = await all('SELECT * FROM projects ORDER BY id DESC LIMIT 5');

  res.render('admin/dashboard', {
    title: 'Tableau de bord',
    counts,
    recentMessages,
    recentProjects,
    csrf: csrfToken(req),
    admin: req.user,
    entities
  });
});

/* ------------------------------- Settings ---------------------------- */

adminRouter.get('/settings', async (req, res) => {
  res.render('admin/settings', {
    title: 'Réglages du site',
    groups: SETTINGS_GROUPS,
    values: await getSettings(),
    csrf: csrfToken(req),
    saved: req.query.saved === '1',
    admin: req.user
  });
});

adminRouter.post('/settings', uploadWithCsrf(upload.any()), async (req, res) => {
  const values = {};
  for (const group of SETTINGS_GROUPS) {
    for (const field of group.fields) {
      if (field.type === 'image' || field.type === 'file') continue;
      // Seuls les champs réellement envoyés sont mis à jour
      if (!(field.key in req.body)) continue;
      values[field.key] = String(req.body[field.key] ?? '').trim();
    }
  }

  // Uploads éventuels (photo de profil, favicon, CV…)
  const { DEFAULT_SETTINGS } = require('./config');
  for (const file of req.files || []) {
    await recordMedia(file);
    const key = file.fieldname.replace(/^upload__/, '');
    if (key in DEFAULT_SETTINGS) values[key] = file.filename;
  }

  await saveSettings(values);
  res.redirect('/admin/settings?saved=1');
});

/* -------------------------------- Media ------------------------------ */

adminRouter.get('/media', async (req, res) => {
  res.render('admin/media', {
    title: 'Médiathèque',
    media: await all('SELECT * FROM media ORDER BY id DESC'),
    csrf: csrfToken(req),
    admin: req.user,
    fileSizeLabel,
    error: req.query.error || ''
  });
});

adminRouter.post('/media', uploadWithCsrf(upload.array('files', 12)), async (req, res) => {
  for (const file of req.files || []) await recordMedia(file);
  res.redirect('/admin/media');
});

adminRouter.post('/media/:id/alt', verifyCsrf, async (req, res) => {
  await run('UPDATE media SET alt = ? WHERE id = ?', [String(req.body.alt || '').slice(0, 200), toInt(req.params.id)]);
  res.redirect('/admin/media');
});

adminRouter.post('/media/:id/delete', verifyCsrf, async (req, res) => {
  const item = await get('SELECT * FROM media WHERE id = ?', [toInt(req.params.id)]);
  if (item) {
    const filePath = path.join(UPLOAD_DIR, path.basename(item.filename));
    if (fs.existsSync(filePath)) fs.unlinkSync(filePath);
    await run('DELETE FROM media WHERE id = ?', [item.id]);
  }
  res.redirect('/admin/media');
});

adminRouter.get('/api/media', async (req, res) => {
  res.json(await all('SELECT id, filename, alt, mime FROM media ORDER BY id DESC'));
});

/* ------------------------------- Messages ---------------------------- */

adminRouter.get('/messages', async (req, res) => {
  const showArchived = req.query.archive === '1';
  const rows = await all(
    `SELECT * FROM messages WHERE archived = ? ORDER BY is_read ASC, id DESC`,
    [showArchived ? 1 : 0]
  );
  res.render('admin/messages', {
    title: showArchived ? 'Messages archivés' : 'Boîte de réception',
    messages: rows,
    showArchived,
    csrf: csrfToken(req),
    admin: req.user,
    unread: Number((await get('SELECT COUNT(*) AS n FROM messages WHERE is_read = 0 AND archived = 0')).n)
  });
});

adminRouter.get('/messages/:id', async (req, res, next) => {
  const message = await get('SELECT * FROM messages WHERE id = ?', [toInt(req.params.id)]);
  if (!message) return next();
  await run('UPDATE messages SET is_read = 1 WHERE id = ?', [message.id]);
  res.render('admin/message', { title: 'Message', message, csrf: csrfToken(req), admin: req.user });
});

adminRouter.post('/messages/:id/:action', verifyCsrf, async (req, res) => {
  const id = toInt(req.params.id);
  const action = req.params.action;
  if (action === 'read') await run('UPDATE messages SET is_read = 1 WHERE id = ?', [id]);
  if (action === 'unread') await run('UPDATE messages SET is_read = 0 WHERE id = ?', [id]);
  if (action === 'archive') await run('UPDATE messages SET archived = 1 WHERE id = ?', [id]);
  if (action === 'restore') await run('UPDATE messages SET archived = 0 WHERE id = ?', [id]);
  if (action === 'delete') await run('DELETE FROM messages WHERE id = ?', [id]);
  res.redirect(req.body.back || '/admin/messages');
});

/* -------------------------------- Compte ----------------------------- */

adminRouter.get('/account', async (req, res) => {
  res.render('admin/account', {
    title: 'Mon compte',
    admin: req.user,
    csrf: csrfToken(req),
    error: '',
    saved: req.query.saved === '1'
  });
});

adminRouter.post('/account', verifyCsrf, async (req, res) => {
  const name = String(req.body.name || '').trim().slice(0, 80);
  const email = String(req.body.email || '').trim().toLowerCase();
  const password = String(req.body.password || '');
  const confirm = String(req.body.confirm || '');

  const render = (error) =>
    res.status(400).render('admin/account', { title: 'Mon compte', admin: { ...req.user, name, email }, csrf: csrfToken(req), error, saved: false });

  if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email)) return render('Adresse e-mail invalide.');
  if (password && password.length < 10) return render('Le nouveau mot de passe doit contenir au moins 10 caractères.');
  if (password && password !== confirm) return render('Les deux mots de passe ne correspondent pas.');

  if (password) {
    await run('UPDATE users SET name = ?, email = ?, password_hash = ? WHERE id = ?', [name, email, hashPassword(password), req.user.id]);
    await run('DELETE FROM sessions WHERE user_id = ?', [req.user.id]);
    await createSession(req.user.id, res);
  } else {
    await run('UPDATE users SET name = ?, email = ? WHERE id = ?', [name, email, req.user.id]);
  }
  res.redirect('/admin/account?saved=1');
});

/* --------------------------- Aperçu projet --------------------------- */

adminRouter.get('/preview/project/:id', async (req, res, next) => {
  const row = await get('SELECT * FROM projects WHERE id = ?', [toInt(req.params.id)]);
  if (!row) return next();
  const data = await getPublicData();
  res.render('public/project', {
    ...data,
    ...viewHelpers(data.settings),
    project: mapProject(row),
    related: [],
    preview: true,
    pageTitle: `Aperçu — ${row.title}`,
    metaDescription: row.summary || '',
    ogImage: ''
  });
});

/* ----------------------------- CRUD générique ------------------------ */

function entityById(req) {
  return getEntity(req.params.entity);
}

function coerceField(field, raw) {
  if (field.type === 'boolean') return toBoolean(raw);
  if (field.type === 'number') return toInt(raw, field.default ?? 0);
  const value = String(raw ?? '').trim();
  if (field.type === 'url') return value && !/^https?:\/\//i.test(value) && value.startsWith('www.') ? 'https://' + value : value;
  return value.slice(0, 20000);
}

async function uniqueSlug(table, slug, id) {
  let candidate = slug || 'element';
  let index = 2;
  while (await get(`SELECT id FROM ${table} WHERE slug = ? AND id != ?`, [candidate, id || 0])) {
    candidate = `${slug}-${index++}`;
  }
  return candidate;
}

adminRouter.get('/:entity', async (req, res, next) => {
  const entity = entityById(req);
  if (!entity) return next();
  const order = entity.table === 'nav_items' ? 'sort ASC, id ASC' : 'sort ASC, id DESC';
  const rows = await all(`SELECT * FROM ${entity.table} ORDER BY ${order}`);
  res.render('admin/list', {
    title: entity.label,
    entity,
    entityKey: req.params.entity,
    rows,
    csrf: csrfToken(req),
    admin: req.user,
    saved: req.query.saved === '1',
    deleted: req.query.deleted === '1'
  });
});

adminRouter.get('/:entity/new', async (req, res, next) => {
  const entity = entityById(req);
  if (!entity) return next();
  const values = {};
  for (const field of entity.fields) values[field.name] = field.default ?? (field.type === 'boolean' ? 1 : '');
  res.render('admin/form', {
    title: `Nouveau — ${entity.singular}`,
    entity,
    entityKey: req.params.entity,
    values,
    isNew: true,
    csrf: csrfToken(req),
    admin: req.user,
    error: '',
    media: await all('SELECT * FROM media ORDER BY id DESC')
  });
});

adminRouter.get('/:entity/:id/edit', async (req, res, next) => {
  const entity = entityById(req);
  if (!entity) return next();
  const row = await get(`SELECT * FROM ${entity.table} WHERE id = ?`, [toInt(req.params.id)]);
  if (!row) return next();
  res.render('admin/form', {
    title: `Modifier — ${entity.singular}`,
    entity,
    entityKey: req.params.entity,
    values: row,
    isNew: false,
    csrf: csrfToken(req),
    admin: req.user,
    error: '',
    media: await all('SELECT * FROM media ORDER BY id DESC')
  });
});

async function persistEntity(req, res, entity, existing) {
  const values = {};
  for (const field of entity.fields) {
    values[field.name] = coerceField(field, req.body[field.name]);
  }

  // Fichiers envoyés
  for (const file of req.files || []) {
    await recordMedia(file);
    const key = file.fieldname.replace(/^upload__/, '');
    const field = entity.fields.find((item) => item.name === key);
    if (field && (field.type === 'image' || field.type === 'file')) values[field.name] = file.filename;
  }

  // Champs image : conserver la valeur existante si aucun fichier
  for (const field of entity.fields) {
    if (field.type === 'image' || field.type === 'file') {
      if (!values[field.name]) values[field.name] = existing ? existing[field.name] || '' : '';
      if (req.body[`clear_${field.name}`] === '1') values[field.name] = '';
    }
  }

  // Galerie
  const galleryField = entity.fields.find((field) => field.type === 'gallery');
  if (galleryField) {
    let gallery = safeJson(req.body.gallery_json || '[]');
    const newFiles = await mediaAsFiles((req.files || []).filter((file) => file.fieldname === 'gallery_files'));
    gallery = gallery.concat(newFiles).filter(Boolean);
    values[galleryField.name] = JSON.stringify(gallery);
  }

  // Slug unique
  const slugField = entity.fields.find((field) => field.slugFrom);
  if (slugField) {
    const source = values[slugField.name] || values[slugField.slugFrom] || '';
    values[slugField.name] = await uniqueSlug(entity.table, slugify(source), existing ? existing.id : null);
  }

  // Validation champs requis
  for (const field of entity.fields) {
    if (field.required && !String(values[field.name] ?? '').trim()) {
      return { error: `Le champ « ${field.label} » est obligatoire.` };
    }
  }

  const columns = Object.keys(values);
  if (existing) {
    const assignments = columns.map((column) => `${column} = ?`).join(', ');
    await run(`UPDATE ${entity.table} SET ${assignments} WHERE id = ?`, [...columns.map((c) => values[c]), existing.id]);
  } else {
    const placeholders = columns.map(() => '?').join(', ');
    await run(`INSERT INTO ${entity.table} (${columns.join(', ')}) VALUES (${placeholders})`, columns.map((c) => values[c]));
  }
  return { ok: true };
}

adminRouter.post('/:entity', uploadWithCsrf(upload.any()), async (req, res, next) => {
  const entity = entityById(req);
  if (!entity) return next();
  const result = await persistEntity(req, res, entity, null);
  if (result.error) {
    return res.status(400).render('admin/form', {
      title: `Nouveau — ${entity.singular}`,
      entity,
      entityKey: req.params.entity,
      values: req.body,
      isNew: true,
      csrf: csrfToken(req),
      admin: req.user,
      error: result.error,
      media: await all('SELECT * FROM media ORDER BY id DESC')
    });
  }
  res.redirect(`/admin/${req.params.entity}?saved=1`);
});

adminRouter.post('/:entity/:id', uploadWithCsrf(upload.any()), async (req, res, next) => {
  const entity = entityById(req);
  if (!entity) return next();
  const existing = await get(`SELECT * FROM ${entity.table} WHERE id = ?`, [toInt(req.params.id)]);
  if (!existing) return next();
  const result = await persistEntity(req, res, entity, existing);
  if (result.error) {
    return res.status(400).render('admin/form', {
      title: `Modifier — ${entity.singular}`,
      entity,
      entityKey: req.params.entity,
      values: { ...existing, ...req.body },
      isNew: false,
      csrf: csrfToken(req),
      admin: req.user,
      error: result.error,
      media: await all('SELECT * FROM media ORDER BY id DESC')
    });
  }
  res.redirect(`/admin/${req.params.entity}?saved=1`);
});

adminRouter.post('/:entity/:id/toggle', verifyCsrf, async (req, res, next) => {
  const entity = entityById(req);
  if (!entity) return next();
  const column = entity.fields.some((field) => field.name === 'published') ? 'published' : null;
  if (column) await run(`UPDATE ${entity.table} SET published = 1 - published WHERE id = ?`, [toInt(req.params.id)]);
  res.redirect(`/admin/${req.params.entity}`);
});

adminRouter.post('/:entity/:id/delete', verifyCsrf, async (req, res, next) => {
  const entity = entityById(req);
  if (!entity) return next();
  await run(`DELETE FROM ${entity.table} WHERE id = ?`, [toInt(req.params.id)]);
  res.redirect(`/admin/${req.params.entity}?deleted=1`);
});

app.use('/admin', adminRouter);

/* ------------------------------- Erreurs ----------------------------- */

app.use(async (req, res) => {
  const data = await getPublicData();
  res.status(404).render('public/404', { ...data, ...viewHelpers(data.settings), pageTitle: 'Page introuvable' });
});

app.use((error, req, res, next) => {
  if (res.headersSent) return next(error);
  const status = error && error.code === 'LIMIT_FILE_SIZE' ? 413 : 500;
  const message =
    status === 413
      ? 'Fichier trop volumineux (12 Mo maximum).'
      : 'Une erreur est survenue. Merci de réessayer.';
  console.error('[erreur]', error.message);
  if (req.path.startsWith('/admin')) {
    return res.status(status).send(`<pre style="font:14px/1.6 ui-monospace;padding:40px">${escapeHtml(message)}</pre>`);
  }
  res.status(status).send(message);
});

module.exports = app;
