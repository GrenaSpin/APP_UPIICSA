<?php
require_once __DIR__ . '/auth.php';
if (is_logged_in()) { header('Location: index.php'); exit; }

$err=''; $name=''; $email='';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_csrf();
  $name = trim((string)($_POST['name'] ?? ''));
  $email = trim((string)($_POST['email'] ?? ''));
  $pass = (string)($_POST['password'] ?? '');
  $pass2 = (string)($_POST['password2'] ?? '');

  if ($name===''||$email===''||$pass==='') $err='Completa todos los campos.';
  elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err='Email inválido.';
  elseif (strlen($pass)<6) $err='La contraseña debe tener al menos 6 caracteres.';
  elseif ($pass!==$pass2) $err='Las contraseñas no coinciden.';
  else {
    try{
      $pdo=db();
      $st=$pdo->prepare("INSERT INTO users (name,email,password_hash,created_at) VALUES (?,?,?,?)");
      $st->execute([$name, strtolower($email), password_hash($pass, PASSWORD_DEFAULT), date('c')]);
      $_SESSION['user_id']=(int)$pdo->lastInsertId();
      header('Location: index.php'); exit;
    } catch(Throwable $e){
      $err = str_contains($e->getMessage(),'UNIQUE') ? 'Ese email ya está registrado.' : 'Error registrando usuario.';
    }
  }
}
?>
<!doctype html><html lang="es"><head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Registro</title>
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
<h1>Crear cuenta</h1>
<p>Primero necesitas registrarte para entrar al editor.</p>
<?php if($err): ?><div class="err"><?php echo h($err); ?></div><?php endif; ?>
<input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>"/>
<div class="row"><label>Nombre</label><input name="name" value="<?php echo h($name); ?>" required/></div>
<div class="row"><label>Email</label><input name="email" value="<?php echo h($email); ?>" required/></div>
<div class="row"><label>Contraseña</label><input type="password" name="password" required/></div>
<div class="row"><label>Confirmar contraseña</label><input type="password" name="password2" required/></div>
<div class="actions">
<button class="btn primary" type="submit">Registrarme</button>
<div class="muted">¿Ya tienes cuenta? <a href="login.php">Iniciar sesión</a></div>
</div>
</form></div></body></html>
