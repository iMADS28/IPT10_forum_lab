-- Create and select database
CREATE DATABASE IF NOT EXISTS nested_forum
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE nested_forum;

-- ── Tables ───────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS users (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username        VARCHAR(50)  NOT NULL UNIQUE,
  email           VARCHAR(190) NOT NULL UNIQUE,
  password_hash   VARCHAR(255) NOT NULL,
  avatar_settings JSON,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS discussions (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  title      VARCHAR(200) NOT NULL,
  body       TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS comments (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED NOT NULL,
  discussion_id INT UNSIGNED NOT NULL,
  parent_id     INT UNSIGNED DEFAULT NULL,
  body          TEXT NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_comment_in_discussion (id, discussion_id),
  FOREIGN KEY (user_id)              REFERENCES users(id),
  FOREIGN KEY (discussion_id)        REFERENCES discussions(id),
  FOREIGN KEY (parent_id, discussion_id) REFERENCES comments(id, discussion_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Seed data ─────────────────────────────────────────────────────────────────

-- 5 fictional users (password_hash 'x' — cannot log in via password_verify)
INSERT INTO users (id, username, email, password_hash) VALUES
  (1, 'alice', 'alice@example.test', 'x'),
  (2, 'ben',   'ben@example.test',   'x'),
  (3, 'cara',  'cara@example.test',  'x'),
  (4, 'dee',   'dee@example.test',   'x'),
  (5, 'eli',   'eli@example.test',   'x');

-- 1 discussion
INSERT INTO discussions (id, user_id, title, body) VALUES
  (1, 1, 'How do I learn PHP?', 'Looking for advice on where to start with PHP.');

-- 7 comments forming the exact tree from the lab PDF
-- depth 0
INSERT INTO comments (id, user_id, discussion_id, parent_id, body) VALUES
  (1, 1, 1, NULL, 'Start with the PHP manual.');
INSERT INTO comments (id, user_id, discussion_id, parent_id, body) VALUES
  (5, 5, 1, NULL, 'Practice SQL as well.');

-- depth 1 (children of comment 1)
INSERT INTO comments (id, user_id, discussion_id, parent_id, body) VALUES
  (2, 2, 1, 1, 'Try the examples as you read.');
INSERT INTO comments (id, user_id, discussion_id, parent_id, body) VALUES
  (4, 4, 1, 1, 'Which manual section first?');

-- depth 2 (child of comment 2)
INSERT INTO comments (id, user_id, discussion_id, parent_id, body) VALUES
  (3, 3, 1, 2, 'Build a small project too.');

-- depth 3 (child of comment 3)
INSERT INTO comments (id, user_id, discussion_id, parent_id, body) VALUES
  (6, 2, 1, 3, 'Start with a forum.');

-- depth 4 (child of comment 6)
INSERT INTO comments (id, user_id, discussion_id, parent_id, body) VALUES
  (7, 5, 1, 6, 'Add replies next.');
