'use strict';

/**
 * Configuration centrale : schéma des réglages du site.
 * Chaque champ ajouté ici apparaît automatiquement dans /admin/settings
 * et devient disponible dans les vues publiques (via `settings.<key>`).
 */

const SETTINGS_GROUPS = [
  {
    key: 'identity',
    label: 'Identité',
    icon: '◐',
    fields: [
      { key: 'site_name', label: 'Nom du site', type: 'text', hint: 'Affiché dans le titre du navigateur et le footer.' },
      { key: 'display_name', label: 'Nom affiché', type: 'text', hint: 'Utilisé dans la navigation, le hero et le footer.' },
      { key: 'pro_name', label: 'Nom professionnel (majuscules)', type: 'text' },
      { key: 'full_name', label: 'Nom complet', type: 'text' },
      { key: 'role_title', label: 'Titre professionnel', type: 'text' },
      { key: 'tagline', label: 'Slogan / accroche', type: 'textarea' },
      { key: 'availability', label: 'Statut de disponibilité', type: 'text', hint: 'Laisser vide pour masquer le badge.' },
      { key: 'location', label: 'Localisation', type: 'text' },
      { key: 'email', label: 'E-mail public', type: 'email' },
      { key: 'phone', label: 'Téléphone', type: 'tel' },
      { key: 'whatsapp', label: 'WhatsApp (numéro international sans +)', type: 'tel' },
      { key: 'portrait', label: 'Photo de profil', type: 'image' }
    ]
  },
  {
    key: 'about',
    label: 'À propos',
    icon: '◑',
    fields: [
      { key: 'about_kicker', label: 'Sur-titre de section', type: 'text' },
      { key: 'about_title', label: 'Titre de section', type: 'text' },
      { key: 'bio', label: 'Biographie', type: 'textarea', rows: 7, hint: 'Une ligne vide crée un nouveau paragraphe.' },
      { key: 'vision', label: 'Vision du développement web', type: 'textarea', rows: 5 },
      { key: 'goals', label: 'Objectifs', type: 'textarea', rows: 4 },
      { key: 'interests', label: 'Centres d\u2019intérêt (séparés par des virgules)', type: 'text' },
      { key: 'stat_1_value', label: 'Statistique 1 — valeur', type: 'text' },
      { key: 'stat_1_label', label: 'Statistique 1 — libellé', type: 'text' },
      { key: 'stat_2_value', label: 'Statistique 2 — valeur', type: 'text' },
      { key: 'stat_2_label', label: 'Statistique 2 — libellé', type: 'text' },
      { key: 'stat_3_value', label: 'Statistique 3 — valeur', type: 'text' },
      { key: 'stat_3_label', label: 'Statistique 3 — libellé', type: 'text' }
    ]
  },
  {
    key: 'sections',
    label: 'Titres des sections',
    icon: '◒',
    fields: [
      { key: 'work_kicker', label: 'Projets — sur-titre', type: 'text' },
      { key: 'work_title', label: 'Projets — titre', type: 'text' },
      { key: 'skills_kicker', label: 'Compétences — sur-titre', type: 'text' },
      { key: 'skills_title', label: 'Compétences — titre', type: 'text' },
      { key: 'exp_kicker', label: 'Parcours — sur-titre', type: 'text' },
      { key: 'exp_title', label: 'Parcours — titre', type: 'text' },
      { key: 'services_kicker', label: 'Services — sur-titre', type: 'text' },
      { key: 'services_title', label: 'Services — titre', type: 'text' },
      { key: 'process_kicker', label: 'Processus — sur-titre', type: 'text' },
      { key: 'process_title', label: 'Processus — titre', type: 'text' },
      { key: 'testimonials_kicker', label: 'Témoignages — sur-titre', type: 'text' },
      { key: 'testimonials_title', label: 'Témoignages — titre', type: 'text' },
      { key: 'contact_kicker', label: 'Contact — sur-titre', type: 'text' },
      { key: 'contact_title', label: 'Contact — titre', type: 'text' },
      { key: 'contact_intro', label: 'Contact — introduction', type: 'textarea', rows: 3 },
      { key: 'portfolio_cta_title', label: 'Bloc CV — titre', type: 'text' },
      { key: 'portfolio_cta_text', label: 'Bloc CV — texte', type: 'textarea', rows: 3 },
      { key: 'cv_file', label: 'CV (PDF)', type: 'file' }
    ]
  },
  {
    key: 'design',
    label: 'Apparence',
    icon: '◓',
    fields: [
      { key: 'accent_color', label: 'Couleur d\u2019accent', type: 'color' },
      { key: 'ink_color', label: 'Couleur de fond', type: 'color' },
      { key: 'surface_color', label: 'Couleur des surfaces', type: 'color' },
      { key: 'text_color', label: 'Couleur du texte', type: 'color' },
      { key: 'muted_color', label: 'Couleur du texte secondaire', type: 'color' },
      { key: 'font_display', label: 'Police d\u2019affichage', type: 'select', options: ['Instrument Serif', 'Inter', 'Georgia'] },
      { key: 'font_body', label: 'Police de texte', type: 'select', options: ['Inter', 'Instrument Serif', 'Georgia'] },
      { key: 'motion', label: 'Animations', type: 'select', options: ['activées', 'désactivées'], hint: 'Les préférences système « reduced motion » restent prioritaires.' },
      { key: 'grain', label: 'Texture granuleuse', type: 'select', options: ['activée', 'désactivée'] }
    ]
  },
  {
    key: 'social',
    label: 'Réseaux',
    icon: '◔',
    fields: [
      { key: 'github', label: 'GitHub', type: 'url' },
      { key: 'linkedin', label: 'LinkedIn', type: 'url' },
      { key: 'x', label: 'X / Twitter', type: 'url' },
      { key: 'dribbble', label: 'Dribbble', type: 'url' },
      { key: 'behance', label: 'Behance', type: 'url' },
      { key: 'instagram', label: 'Instagram', type: 'url' },
      { key: 'facebook', label: 'Facebook', type: 'url' },
      { key: 'youtube', label: 'YouTube', type: 'url' }
    ]
  },
  {
    key: 'seo',
    label: 'SEO & partage',
    icon: '◕',
    fields: [
      { key: 'meta_title', label: 'Titre SEO', type: 'text', hint: 'Laisser vide pour utiliser le nom du site.' },
      { key: 'meta_description', label: 'Meta description', type: 'textarea', rows: 3 },
      { key: 'keywords', label: 'Mots-clés', type: 'text' },
      { key: 'og_image', label: 'Image de partage (1200×630)', type: 'image' },
      { key: 'favicon', label: 'Favicon', type: 'image' },
      { key: 'canonical_url', label: 'URL canonique du site', type: 'url', hint: 'Ex. https://malick.dev' }
    ]
  },
  {
    key: 'footer',
    label: 'Footer',
    icon: '◖',
    fields: [
      { key: 'footer_note', label: 'Note du footer', type: 'text' },
      { key: 'copyright', label: 'Copyright', type: 'text', hint: 'L\u2019année courante peut être insérée avec {year}.' }
    ]
  }
];

const DEFAULT_SETTINGS = {
  site_name: 'Malick',
  display_name: 'Malick',
  pro_name: 'MALICK',
  full_name: 'Malick',
  role_title: 'Développeur Full-Stack & UI/UX Designer',
  tagline: 'Je conçois et développe des produits web rapides, soignés, et pensés pour durer.',
  availability: 'Disponible pour missions freelance et CDI',
  location: 'Bénin · Remote international',
  email: 'contact@malick.dev',
  phone: '',
  whatsapp: '',
  portrait: '',

  about_kicker: '01 — À propos',
  about_title: 'Des interfaces pensées, du code tenu.',
  bio: 'Je suis développeur web full-stack et designer d\u2019interfaces. Mon travail consiste à transformer une idée ou un besoin métier en un produit numérique clair, rapide et agréable à utiliser — de la première maquette jusqu\u2019au déploiement.',
  vision: 'Le web que je défends est sobre et rapide. Une bonne interface ne se remarque pas : elle se traverse. Je préfère une animation utile à dix effets décoratifs, et un site qui charge en une seconde à un site qui impressionne pendant dix.',
  goals: 'Collaborer avec des équipes produit exigeantes, en Afrique comme en Europe, et construire des applications dont l\u2019expérience utilisateur est aussi soignée que le code.',
  interests: 'Design d\u2019interface, typographie, intelligence artificielle, open source, photographie',
  stat_1_value: '',
  stat_1_label: '',
  stat_2_value: '',
  stat_2_label: '',
  stat_3_value: '',
  stat_3_label: '',

  work_kicker: '02 — Sélection',
  work_title: 'Projets',
  skills_kicker: '03 — Compétences',
  skills_title: 'Ce que je maîtrise',
  exp_kicker: '04 — Parcours',
  exp_title: 'Expériences & formation',
  services_kicker: '05 — Services',
  services_title: 'Ce que je peux faire pour vous',
  process_kicker: '06 — Méthode',
  process_title: 'Comment je travaille',
  testimonials_kicker: '07 — Retours',
  testimonials_title: 'Ils m\u2019ont fait confiance',
  contact_kicker: '08 — Contact',
  contact_title: 'Parlons de votre projet.',
  contact_intro: 'Un site à construire, une application à repenser, une équipe à renforcer ? Écrivez-moi, je réponds sous 48 h.',
  portfolio_cta_title: 'Le portfolio complet, en PDF.',
  portfolio_cta_text: 'Parcours, projets et compétences détaillés — prêt à transmettre à un recruteur.',
  cv_file: '',

  accent_color: '#C9F24D',
  ink_color: '#0A0A0B',
  surface_color: '#121214',
  text_color: '#F1EEE7',
  muted_color: '#8C887F',
  font_display: 'Instrument Serif',
  font_body: 'Inter',
  motion: 'activées',
  grain: 'activée',

  github: '',
  linkedin: '',
  x: '',
  dribbble: '',
  behance: '',
  instagram: '',
  facebook: '',
  youtube: '',

  meta_title: 'Malick — Développeur Full-Stack & UI/UX Designer',
  meta_description: 'Développeur web full-stack et designer d\u2019interfaces basé au Bénin. Conception et développement de sites, d\u2019applications web et de solutions digitales sur mesure.',
  keywords: 'développeur full-stack, développeur web, UI/UX designer, Bénin, React, Node.js',
  og_image: '',
  favicon: '',
  canonical_url: '',

  footer_note: 'Conçu et développé avec soin.',
  copyright: '© {year} Malick. Tous droits réservés.'
};

const SOCIAL_FIELDS = [
  { key: 'github', label: 'GitHub' },
  { key: 'linkedin', label: 'LinkedIn' },
  { key: 'x', label: 'X' },
  { key: 'dribbble', label: 'Dribbble' },
  { key: 'behance', label: 'Behance' },
  { key: 'instagram', label: 'Instagram' },
  { key: 'facebook', label: 'Facebook' },
  { key: 'youtube', label: 'YouTube' }
];

const SKILL_CATEGORIES = ['Frontend', 'Backend', 'UI/UX Design', 'Bases de données', 'Outils & technologies', 'Intelligence artificielle'];

const EXPERIENCE_KINDS = ['Expérience', 'Formation', 'Certification', 'Étape'];

module.exports = {
  SETTINGS_GROUPS,
  DEFAULT_SETTINGS,
  SOCIAL_FIELDS,
  SKILL_CATEGORIES,
  EXPERIENCE_KINDS
};
