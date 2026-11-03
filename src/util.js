'use strict';

const path = require('node:path');
const fs = require('node:fs');

function slugify(value = '') {
  return String(value)
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 80);
}

function escapeHtml(value = '') {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

/** Transforme un texte brut en paragraphes HTML sûrs. */
function paragraphs(value = '') {
  return String(value)
    .split(/\n\s*\n/)
    .map((block) => block.trim())
    .filter(Boolean)
    .map((block) => `<p>${escapeHtml(block).replace(/\n/g, '<br>')}</p>`)
    .join('\n');
}

function imageUrl(filename, fallback = '') {
  if (!filename) return fallback;
  if (/^https?:\/\//i.test(filename)) return filename;
  return '/uploads/' + encodeURIComponent(path.basename(filename));
}

function isPdf(filename = '') {
  return /\.pdf$/i.test(filename);
}

function listing(list = []) {
  const items = list.filter(Boolean);
  if (items.length === 0) return '';
  if (items.length === 1) return items[0];
  return items.slice(0, -1).join(', ') + ' et ' + items[items.length - 1];
}

function formatPeriod(start, end) {
  if (!start && !end) return '';
  if (!end) return start || '';
  return `${start} — ${end}`;
}

function parseList(value = '') {
  return String(value)
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean);
}

function toBoolean(value) {
  return value === '1' || value === 'on' || value === true || value === 'true' ? 1 : 0;
}

function toInt(value, fallback = 0) {
  const n = Number.parseInt(value, 10);
  return Number.isFinite(n) ? n : fallback;
}

function localUploadPath(filename) {
  return path.join(__dirname, '..', 'public', 'uploads', path.basename(String(filename)));
}

function fileExists(filename) {
  try {
    return fs.existsSync(localUploadPath(filename));
  } catch {
    return false;
  }
}

function fileSizeLabel(bytes) {
  if (!bytes) return '—';
  if (bytes < 1024) return bytes + ' o';
  if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' Ko';
  return (bytes / 1024 / 1024).toFixed(1) + ' Mo';
}

module.exports = {
  slugify,
  escapeHtml,
  paragraphs,
  imageUrl,
  isPdf,
  listing,
  formatPeriod,
  parseList,
  toBoolean,
  toInt,
  fileExists,
  fileSizeLabel,
  localUploadPath
};
