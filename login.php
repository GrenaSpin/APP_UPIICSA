<?php
require_once __DIR__ . '/auth.php';
if (is_logged_in()) { header('Location: index.php'); exit; }

$err=''; $email='';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_csrf();
  $email = trim((string)($_POST['email'] ?? ''));
  $pass = (string)($_POST['password'] ?? '');
  if ($email===''||$pass==='') $err='Completa todos los campos.';
  else {
    $pdo=db();
    $st=$pdo->prepare("SELECT id,password_hash FROM users WHERE email=?");
    $st->execute([strtolower($email)]);
    $u=$st->fetch(PDO::FETCH_ASSOC);
    if(!$u || !password_verify($pass,(string)$u['password_hash'])) $err='Credenciales incorrectas.';
    else { $_SESSION['user_id']=(int)$u['id']; header('Location: index.php'); exit; }
  }
}
?>
<!doctype html><html lang="es"><head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Iniciar sesión</title>
<link rel="stylesheet" href="assets/styles.css"/>
<style>
.auth-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:18px;}
.card{width:420px;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:16px;padding:18px;}
.card h1{margin:0 0 8px 0;font-size:20px;}
.card p{margin:0 0 14px 0;color:var(--muted);font-size:13px;}
.err{background:rgba(255,93,93,.12);border:1px solid rgba(255,93,93,.35);padding:10px;border-radius:12px;margin-bottom:12px;}
.row{display:flex;flex-direction:column;gap:6px;margin-bottom:12px;}
.actions{display:flex;gap:10px;align-items:center;justify-content:space-between;margin-top:10px;}
a{color:var(--accent);text-decoration:none;}
</style></head><body>
<div class="auth-wrap">
<form class="card" method="post">
<h1>Iniciar sesión</h1>
<p>Entra para ver tus presentaciones.</p>
<?php if($err): ?><div class="err"><?php echo h($err); ?></div><?php endif; ?>
<input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>"/>
<div class="row"><label>Email</label><input name="email" value="<?php echo h($email); ?>" required/></div>
<div class="row"><label>Contraseña</label><input type="password" name="password" required/></div>
<div class="actions">
<button class="btn primary" type="submit">Entrar</button>
<div class="muted">¿No tienes cuenta? <a href="register.php">Regístrate</a></div>
</div>
</form></div></body></html>
