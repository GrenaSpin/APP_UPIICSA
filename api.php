<?php
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

function ok($arr = []) { echo json_encode(array_merge(['ok'=>true], $arr), JSON_UNESCAPED_UNICODE); exit; }
function fail($msg, $code=400){ http_response_code($code); echo json_encode(['ok'=>false,'error'=>$msg], JSON_UNESCAPED_UNICODE); exit; }

function require_login_json(){ if(!is_logged_in()) fail('No autenticado', 401); }

function parse_json_body(): array {
  $raw = file_get_contents('php://input');
  $json = json_decode($raw, true);
  return is_array($json) ? $json : [];
}

$pdo = db();

if ($action === 'list_presentations') {
  require_login_json();
  $b = parse_json_body();
  $t = $b['csrf'] ?? '';
  if(!$t || !hash_equals((string)($_SESSION['csrf'] ?? ''), (string)$t)) fail('CSRF inválido', 403);

  $uid = current_user_id();
  $st = $pdo->prepare("SELECT id,title,created_at,updated_at FROM presentations WHERE user_id=? ORDER BY datetime(updated_at) DESC");
  $st->execute([$uid]);
  ok(['items'=>$st->fetchAll(PDO::FETCH_ASSOC)]);
}

if ($action === 'create_presentation') {
  require_login_json();
  $b = parse_json_body();
  $t = $b['csrf'] ?? '';
  if(!$t || !hash_equals((string)($_SESSION['csrf'] ?? ''), (string)$t)) fail('CSRF inválido', 403);

  $uid = current_user_id();
  $title = trim((string)($b['title'] ?? 'Nueva presentación'));
  if($title==='') $title='Nueva presentación';
  $empty = json_encode(['slides'=>[]], JSON_UNESCAPED_UNICODE);
  $now = date('c');
  $st = $pdo->prepare("INSERT INTO presentations (user_id,title,content_json,created_at,updated_at) VALUES (?,?,?,?,?)");
  $st->execute([$uid,$title,$empty,$now,$now]);
  ok(['id'=>(int)$pdo->lastInsertId()]);
}

if ($action === 'delete_presentation') {
  require_login_json();
  $b = parse_json_body();
  $t = $b['csrf'] ?? '';
  if(!$t || !hash_equals((string)($_SESSION['csrf'] ?? ''), (string)$t)) fail('CSRF inválido', 403);

  $uid = current_user_id();
  $id = (int)($b['id'] ?? 0);
  if($id<=0) fail('ID inválido');
  $st = $pdo->prepare("DELETE FROM presentations WHERE id=? AND user_id=?");
  $st->execute([$id,$uid]);
  ok(['deleted'=>true]);
}

if ($action === 'get_presentation') {
  require_login_json();
  $b = parse_json_body();
  $t = $b['csrf'] ?? '';
  if(!$t || !hash_equals((string)($_SESSION['csrf'] ?? ''), (string)$t)) fail('CSRF inválido', 403);

  $uid = current_user_id();
  $pid = (int)($b['presentation_id'] ?? 0);
  if($pid<=0) fail('presentation_id inválido');

  $st = $pdo->prepare("SELECT title, content_json FROM presentations WHERE id=? AND user_id=?");
  $st->execute([$pid,$uid]);
  $p = $st->fetch(PDO::FETCH_ASSOC);
  if(!$p) fail('No encontrado', 404);
  $content = json_decode((string)$p['content_json'], true);
  if(!is_array($content)) $content = ['slides'=>[]];
  ok(['title'=>$p['title'], 'content'=>$content]);
}

if ($action === 'save_presentation') {
  require_login_json();
  $b = parse_json_body();
  $t = $b['csrf'] ?? '';
  if(!$t || !hash_equals((string)($_SESSION['csrf'] ?? ''), (string)$t)) fail('CSRF inválido', 403);

  $uid = current_user_id();
  $pid = (int)($b['presentation_id'] ?? 0);
  if($pid<=0) fail('presentation_id inválido');

  $title = trim((string)($b['title'] ?? 'Presentación'));
  if($title==='') $title='Presentación';

  $content = $b['content'] ?? ['slides'=>[]];
  if(!is_array($content)) $content = ['slides'=>[]];

  $json = json_encode($content, JSON_UNESCAPED_UNICODE);
  if($json === false) fail('Contenido inválido');

  $now = date('c');
  $st = $pdo->prepare("UPDATE presentations SET title=?, content_json=?, updated_at=? WHERE id=? AND user_id=?");
  $st->execute([$title,$json,$now,$pid,$uid]);
  ok(['saved'=>true]);
}

if ($action === 'upload_image') {
  require_login_json();
  $t = $_POST['csrf'] ?? '';
  if(!$t || !hash_equals((string)($_SESSION['csrf'] ?? ''), (string)$t)) fail('CSRF inválido', 403);

  if(!isset($_FILES['image'])) fail('No se recibió archivo "image"');
  $f = $_FILES['image'];
  if($f['error'] !== UPLOAD_ERR_OK) fail('Error subiendo archivo');
  if($f['size'] > 8*1024*1024) fail('Imagen demasiado grande (max 8MB)');

  $tmp = $f['tmp_name'];
  $info = @getimagesize($tmp);
  if($info === false) fail('Archivo no es una imagen válida');

  $mime = $info['mime'] ?? '';
  $allowed = ['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'];
  if(!isset($allowed[$mime])) fail('Formato no permitido (PNG/JPG/WebP)');
  $ext = $allowed[$mime];

  $uploadDir = __DIR__ . '/uploads';
  if(!is_dir($uploadDir)) { if(!mkdir($uploadDir, 0777, true)) fail('No se pudo crear uploads/', 500); }

  $name = 'img_' . bin2hex(random_bytes(8)) . '.' . $ext;
  $dest = $uploadDir . '/' . $name;
  if(!move_uploaded_file($tmp, $dest)) fail('No se pudo guardar imagen', 500);

  ok(['url' => 'uploads/' . $name]);
}

fail('Acción no válida');
