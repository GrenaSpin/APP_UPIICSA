<?php
require_once __DIR__ . '/auth.php';
require_login();

$pdo = db();
$uid = current_user_id();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo "ID inválido"; exit; }

$stmt = $pdo->prepare("SELECT title, content_json FROM presentations WHERE id=? AND user_id=?");
$stmt->execute([$id, $uid]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) { http_response_code(404); echo "No encontrado"; exit; }

$content = json_decode((string)$p['content_json'], true);
if (!is_array($content)) $content = ['slides'=>[]];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?php echo h($p['title']); ?> — Presentar</title>
  <link rel="stylesheet" href="assets/styles.css"/>
  <style>
    body{background:#000;}
    .viewer{min-height:100vh;display:flex;flex-direction:column;}
    .bar{
      height:52px;display:flex;align-items:center;justify-content:space-between;
      padding:0 12px;border-bottom:1px solid rgba(255,255,255,.12);
      background:rgba(0,0,0,.6);color:#fff;
    }
    .bar .t{font-weight:900;}
    .bar .c{color:rgba(255,255,255,.7);font-size:12px;}
    .stage{flex:1;display:flex;align-items:center;justify-content:center;padding:18px;}
    .slide{
      width:min(1200px, 92vw);
      aspect-ratio: 16/9;
      background:#fff;border-radius:16px;position:relative;overflow:hidden;
    }
    .el{position:absolute;}
    .el.text{
      padding:6px 8px; font-weight:700; border-radius:10px; background:rgba(255,255,255,.75);
    }
    .el.rect{border-radius:14px;}
    .el.image{border-radius:14px; overflow:hidden;}
    .el.image img{width:100%;height:100%;object-fit:cover;display:block;}
    .navbtn{background:rgba(255,255,255,.10);border:1px solid rgba(255,255,255,.18);color:#fff;
      padding:8px 10px;border-radius:12px;cursor:pointer;font-weight:800;}
    .navbtn:hover{background:rgba(255,255,255,.16);}
  </style>
</head>
<body>
<div class="viewer">
  <div class="bar">
    <div>
      <div class="t"><?php echo h($p['title']); ?></div>
      <div class="c">←/→ cambiar slide • Esc salir</div>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
      <button class="navbtn" id="prev">←</button>
      <div class="c" id="counter"></div>
      <button class="navbtn" id="next">→</button>
      <a class="navbtn" href="editor.php?id=<?php echo (int)$id; ?>">Editar</a>
    </div>
  </div>

  <div class="stage">
    <div class="slide" id="slide"></div>
  </div>
</div>

<script>
const content = <?php echo json_encode($content, JSON_UNESCAPED_UNICODE); ?>;
const slides = Array.isArray(content.slides) ? content.slides : [];
let idx = 0;

const slideEl = document.getElementById('slide');
const counter = document.getElementById('counter');

function hexToRgba(hex, a=1){
  const h = (hex || "#000000").replace("#","");
  const full = h.length === 3 ? h.split("").map(c=>c+c).join("") : h.padEnd(6,"0");
  const r = parseInt(full.slice(0,2),16);
  const g = parseInt(full.slice(2,4),16);
  const b = parseInt(full.slice(4,6),16);
  return `rgba(${r},${g},${b},${a})`;
}

function render(){
  if(slides.length === 0){
    slideEl.innerHTML = '';
    slideEl.style.background = '#fff';
    counter.textContent = '0 / 0';
    return;
  }
  const s = slides[idx];
  slideEl.style.background = s.bg || '#ffffff';
  slideEl.innerHTML = '';
  (s.elements||[]).forEach(el => {
    const node = document.createElement('div');
    node.className = `el ${el.type}`;
    node.style.left = (el.x||0) + 'px';
    node.style.top = (el.y||0) + 'px';
    node.style.width = (el.w||0) + 'px';
    node.style.height = (el.h||0) + 'px';

    if(el.type === 'text'){
      node.textContent = el.text || '';
      node.style.color = el.color || '#111111';
    } else if(el.type === 'rect'){
      node.style.background = hexToRgba(el.color || '#6ea8fe', 0.28);
    } else if(el.type === 'image'){
      const img = document.createElement('img');
      img.src = el.src || '';
      node.appendChild(img);
    }
    slideEl.appendChild(node);
  });
  counter.textContent = (idx+1) + ' / ' + slides.length;
}

function prev(){ if(!slides.length) return; idx = (idx - 1 + slides.length) % slides.length; render(); }
function next(){ if(!slides.length) return; idx = (idx + 1) % slides.length; render(); }

document.getElementById('prev').addEventListener('click', prev);
document.getElementById('next').addEventListener('click', next);

document.addEventListener('keydown', (e) => {
  if(e.key === 'ArrowLeft') prev();
  if(e.key === 'ArrowRight') next();
  if(e.key === 'Escape') history.back();
});

render();
</script>
</body>
</html>
