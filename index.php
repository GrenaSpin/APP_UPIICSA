<?php
require_once __DIR__ . '/auth.php';
require_login();

$pdo = db();
$uid = current_user_id();
$user = $pdo->query("SELECT name,email FROM users WHERE id=".(int)$uid)->fetch(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Inicio</title>
  <link rel="stylesheet" href="assets/styles.css"/>
  <style>
    .app-shell{height:100vh;display:flex;flex-direction:column;}
    .nav{height:56px;display:flex;align-items:center;justify-content:space-between;
      padding:0 14px;border-bottom:1px solid var(--border);
      background: linear-gradient(180deg, rgba(255,255,255,.05), transparent);}
    .nav-left{display:flex;gap:10px;align-items:center;}
    .burger{width:40px;height:40px;border-radius:12px;display:grid;place-items:center;cursor:pointer;
      background:rgba(255,255,255,.06);border:1px solid var(--border);}
    .burger:hover{background:rgba(255,255,255,.10);}
    .brand{font-weight:800;letter-spacing:.3px;}
    .nav-right{display:flex;gap:10px;align-items:center;}
    .pill{font-size:12px;color:var(--muted);}
    .shell{flex:1;display:flex;min-height:0;}
    .sidebar{width:260px;background:var(--panel);border-right:1px solid var(--border);
      padding:12px;transition:transform .2s ease;min-height:0;overflow:auto;}
    .sidebar.closed{transform:translateX(-100%);width:0;padding:0;border-right:none;}
    .menu-title{font-weight:800;margin-bottom:10px;}
    .menu a{display:flex;gap:10px;align-items:center;padding:10px;border-radius:12px;border:1px solid var(--border);
      background:rgba(255,255,255,.04);color:var(--text);text-decoration:none;margin-bottom:10px;font-weight:700;}
    .menu a.active{outline:2px solid rgba(110,168,254,.45);background:rgba(110,168,254,.10);}
    .content{flex:1;min-width:0;min-height:0;overflow:auto;padding:16px;}
    .content h1{margin:0 0 6px 0;font-size:20px;}
    .content p{margin:0 0 14px 0;color:var(--muted);}
    .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px;}
    .card{background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:16px;padding:14px;
      display:flex;flex-direction:column;gap:10px;}
    .card .t{font-weight:900;}
    .card .m{color:var(--muted);font-size:12px;}
    .card .actions{display:flex;gap:8px;flex-wrap:wrap;}
    .toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:12px 0 14px;}
    .input{min-width:260px;}
  </style>
</head>
<body>
<div class="app-shell">
  <div class="nav">
    <div class="nav-left">
      <div class="burger" id="burger" title="Menú">☰</div>
      <div class="brand">Canva Lite</div>
    </div>
    <div class="nav-right">
      <div class="pill"><?php echo h((string)($user['name'] ?? '')); ?> • <?php echo h((string)($user['email'] ?? '')); ?></div>
      <a class="btn" href="logout.php">Salir</a>
    </div>
  </div>

  <div class="shell">
    <aside class="sidebar" id="sidebar">
      <div class="menu-title">Menú</div>
      <div class="menu">
        <a class="active" href="index.php">📽️ Presentaciones</a>
      </div>
    </aside>

    <main class="content">
      <h1>Presentaciones</h1>
      <p>Crea, edita, elimina o presenta.</p>

      <div class="toolbar">
        <input class="input" id="newTitle" placeholder="Título de la presentación (ej: Expo Unix)"/>
        <button class="btn primary" id="btnCreate">+ Crear</button>
        <span id="status" class="status"></span>
      </div>

      <div id="grid" class="grid"></div>

      <form id="csrfForm" method="post" style="display:none">
        <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>"/>
      </form>
    </main>
  </div>
</div>

<script>
const sidebar = document.getElementById('sidebar');
document.getElementById('burger').addEventListener('click', () => sidebar.classList.toggle('closed'));

const statusEl = document.getElementById('status');
const grid = document.getElementById('grid');
const titleInp = document.getElementById('newTitle');
const csrf = document.querySelector('#csrfForm input[name="csrf"]').value;

function setStatus(t){ statusEl.textContent = t || ''; }

async function api(action, payload){
  const res = await fetch('api.php?action=' + encodeURIComponent(action), {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({csrf, ...payload})
  });
  return await res.json();
}

function card(p){
  const el = document.createElement('div');
  el.className = 'card';
  el.innerHTML = `
    <div class="t"></div>
    <div class="m"></div>
    <div class="actions">
      <a class="btn primary" href="editor.php?id=${p.id}">Editar</a>
      <a class="btn" href="present.php?id=${p.id}" target="_blank">Presentar</a>
      <button class="btn danger" data-del="${p.id}">Eliminar</button>
    </div>
  `;
  el.querySelector('.t').textContent = p.title;
  el.querySelector('.m').textContent = `Actualizado: ${p.updated_at}`;
  el.querySelector('[data-del]').addEventListener('click', async () => {
    if(!confirm('¿Eliminar esta presentación?')) return;
    setStatus('Eliminando...');
    const r = await api('delete_presentation', {id: p.id});
    if(!r.ok) return setStatus('Error: ' + (r.error||''));
    setStatus('Eliminado');
    await load();
  });
  return el;
}

async function load(){
  setStatus('Cargando...');
  const r = await api('list_presentations', {});
  if(!r.ok){ setStatus('Error: ' + (r.error||'')); return; }
  grid.innerHTML = '';
  (r.items||[]).forEach(p => grid.appendChild(card(p)));
  setStatus('');
}

document.getElementById('btnCreate').addEventListener('click', async () => {
  const title = (titleInp.value || '').trim() || 'Nueva presentación';
  setStatus('Creando...');
  const r = await api('create_presentation', {title});
  if(!r.ok){ setStatus('Error: ' + (r.error||'')); return; }
  location.href = 'editor.php?id=' + r.id;
});

load();
</script>
</body>
</html>
