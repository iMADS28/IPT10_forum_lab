<?php
declare(strict_types=1);

// ── Session setup ──────────────────────────────────────────────────────────
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', '1');
session_start();

// ── Autoloader ────────────────────────────────────────────────────────────
require_once __DIR__ . '/../vendor/autoload.php';

// ── Routes ────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../config/routes.php';

