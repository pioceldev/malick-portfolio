-- Schéma initial — MySQL 8 / MariaDB (WAMP)
-- Tous les contenus éditoriaux vivent en base : réglages, compte admin, sessions.

CREATE TABLE IF NOT EXISTS settings (
  `key`   VARCHAR(100) NOT NULL PRIMARY KEY,
  `value` MEDIUMTEXT   NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(120) NOT NULL DEFAULT '',
  email         VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sessions (
  token      CHAR(64)     NOT NULL PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  csrf       CHAR(48)     NOT NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME     NOT NULL,
  KEY idx_sessions_user (user_id),
  KEY idx_sessions_expires (expires_at),
  CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS projects (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title       VARCHAR(255) NOT NULL,
  slug        VARCHAR(255) NOT NULL UNIQUE,
  category    VARCHAR(120) NOT NULL DEFAULT '',
  year        VARCHAR(20)  NOT NULL DEFAULT '',
  summary     TEXT         NOT NULL,
  description MEDIUMTEXT   NOT NULL,
  tech        VARCHAR(500) NOT NULL DEFAULT '',
  cover       VARCHAR(255) NOT NULL DEFAULT '',
  gallery     MEDIUMTEXT   NOT NULL,
  demo_url    VARCHAR(500) NOT NULL DEFAULT '',
  repo_url    VARCHAR(500) NOT NULL DEFAULT '',
  status      VARCHAR(60)  NOT NULL DEFAULT 'Livré',
  featured    TINYINT      NOT NULL DEFAULT 0,
  published   TINYINT      NOT NULL DEFAULT 1,
  sort        INT          NOT NULL DEFAULT 0,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_projects_public (published, featured, sort)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS skills (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(255) NOT NULL,
  category    VARCHAR(120) NOT NULL DEFAULT 'Frontend',
  level       INT          NOT NULL DEFAULT 0,
  icon        VARCHAR(80)  NOT NULL DEFAULT '',
  description TEXT         NOT NULL,
  published   TINYINT      NOT NULL DEFAULT 1,
  sort        INT          NOT NULL DEFAULT 0,
  KEY idx_skills_public (published, sort)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS experiences (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  kind        VARCHAR(60)  NOT NULL DEFAULT 'Expérience',
  title       VARCHAR(255) NOT NULL,
  org         VARCHAR(255) NOT NULL DEFAULT '',
  start_date  VARCHAR(40)  NOT NULL DEFAULT '',
  end_date    VARCHAR(40)  NOT NULL DEFAULT '',
  description TEXT         NOT NULL,
  url         VARCHAR(500) NOT NULL DEFAULT '',
  document    VARCHAR(255) NOT NULL DEFAULT '',
  published   TINYINT      NOT NULL DEFAULT 1,
  sort        INT          NOT NULL DEFAULT 0,
  KEY idx_experiences_public (published, sort)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS services (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title       VARCHAR(255) NOT NULL,
  description TEXT         NOT NULL,
  icon        VARCHAR(80)  NOT NULL DEFAULT '',
  published   TINYINT      NOT NULL DEFAULT 1,
  sort        INT          NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS process_steps (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title       VARCHAR(255) NOT NULL,
  description TEXT         NOT NULL,
  published   TINYINT      NOT NULL DEFAULT 1,
  sort        INT          NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS testimonials (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name      VARCHAR(255) NOT NULL,
  role      VARCHAR(120) NOT NULL DEFAULT '',
  company   VARCHAR(120) NOT NULL DEFAULT '',
  message   TEXT         NOT NULL,
  rating    INT          NOT NULL DEFAULT 0,
  photo     VARCHAR(255) NOT NULL DEFAULT '',
  published TINYINT      NOT NULL DEFAULT 1,
  sort      INT          NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(255) NOT NULL,
  email        VARCHAR(255) NOT NULL,
  subject      VARCHAR(255) NOT NULL DEFAULT '',
  project_type VARCHAR(120) NOT NULL DEFAULT '',
  budget       VARCHAR(80)  NOT NULL DEFAULT '',
  body         TEXT         NOT NULL,
  is_read      TINYINT      NOT NULL DEFAULT 0,
  archived     TINYINT      NOT NULL DEFAULT 0,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_messages_inbox (archived, is_read, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  filename      VARCHAR(255) NOT NULL UNIQUE,
  original_name VARCHAR(255) NOT NULL DEFAULT '',
  alt           VARCHAR(255) NOT NULL DEFAULT '',
  mime          VARCHAR(120) NOT NULL DEFAULT '',
  size          BIGINT       NOT NULL DEFAULT 0,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS nav_items (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  label     VARCHAR(120) NOT NULL,
  href      VARCHAR(255) NOT NULL,
  published TINYINT      NOT NULL DEFAULT 1,
  sort      INT          NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
