<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;

  $dbFile = __DIR__ . '/data/app.db';
  $needInit = !file_exists($dbFile);

  $pdo = new PDO('sqlite:' . $dbFile);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->exec('PRAGMA foreign_keys = ON;');

  init_db($pdo);
  return $pdo;
}

function init_db(PDO $pdo): void {
  $pdo->exec("CREATE TABLE IF NOT EXISTS users (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL,
      email TEXT NOT NULL UNIQUE,
      password_hash TEXT NOT NULL,
      created_at TEXT NOT NULL
    );");
  $pdo->exec("CREATE TABLE IF NOT EXISTS presentations (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      title TEXT NOT NULL,
      content_json TEXT NOT NULL,
      created_at TEXT NOT NULL,
      updated_at TEXT NOT NULL,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );");
}

function is_logged_in(): bool { return isset($_SESSION['user_id']) && is_int($_SESSION['user_id']); }
function require_login(): void { if (!is_logged_in()) { header('Location: login.php'); exit; } }
function current_user_id(): int { return (int)($_SESSION['user_id'] ?? 0); }
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function csrf_token(): string {
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
  return (string)$_SESSION['csrf'];
}
function require_csrf(): void {
  $t = $_POST['csrf'] ?? '';
  if (!$t || !hash_equals((string)($_SESSION['csrf'] ?? ''), (string)$t)) {
    http_response_code(403); echo "CSRF inválido"; exit;
  }
}
