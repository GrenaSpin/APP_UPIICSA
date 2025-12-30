<?php
require_once __DIR__ . '/auth.php';
require_login();

$pdo = db();
$uid = current_user_id();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT id,title FROM presentations WHERE id=? AND user_id=?");
$stmt->execute([$id, $uid]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) { http_response_code(404); echo "No encontrado"; exit; }
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?php echo h($p['title']); ?> — Editor</title>
  <link rel="stylesheet" href="assets/styles.css"/>
  <link rel="stylesheet" href="assets/editor.css"/>
  <style>
    .topbar .brand{display:flex;gap:10px;align-items:center;}
    .crumb{color:var(--muted);font-size:12px;font-weight:700;}
    .layout{grid-template-columns: 260px 1fr 340px;}
    .top-actions{gap:8px;flex-wrap:wrap;}
    .title-edit{max-width:260px;}
  </style>
</head>
<body>
  <header class="topbar">
    <div class="brand">
      <a class="btn" href="index.php">←</a>
      <div>
        <div style="font-weight:900">Editor</div>
        <div class="crumb"><?php echo h($p['title']); ?></div>
      </div>
    </div>

    <div class="top-actions">
      <input class="title-edit" id="presentation-title" value="<?php echo h($p['title']); ?>" />
      <button class="btn" id="btn-new-slide">+ Slide</button>
      <button class="btn" id="btn-add-text">+ Texto</button>
      <button class="btn" id="btn-add-rect">+ Rect</button>
      <button class="btn" id="btn-add-image">+ Imagen</button>
      <input id="image-picker" type="file" accept="image/png,image/jpeg,image/webp" hidden />
      <span class="sep"></span>
      <button class="btn primary" id="btn-save">Guardar</button>
      <a class="btn" href="present.php?id=<?php echo (int)$p['id']; ?>" target="_blank">Presentar</a>
      <span id="status" class="status"></span>
      <a class="btn" href="logout.php">Salir</a>
    </div>
  </header>

  <main class="layout">
    <aside class="panel left">
      <div class="panel-title">Slides</div>
      <div id="slides-list" class="slides-list"></div>
    </aside>

    <section class="center">
      <div class="stage-wrap">
        <div class="stage" id="stage">
          <div class="slide" id="slide"></div>
        </div>
      </div>
      <div class="hint">
        Tip: arrastra • redimensiona con los puntos • doble click para editar texto • Supr/Backspace borra seleccionado
      </div>
    </section>

    <aside class="panel right">
      <div class="panel-title">Propiedades</div>

      <div class="prop">
        <label>Slide título</label>
        <input id="slide-title" type="text" placeholder="Ej: Portada"/>
      </div>

      <div class="prop">
        <label>Fondo</label>
        <input id="slide-bg" type="color" value="#ffffff"/>
      </div>

      <hr/>

      <div class="prop">
        <label>Elemento seleccionado</label>
        <div id="sel-none" class="muted">Ninguno</div>

        <div id="sel-props" class="sel-props hidden">
          <div class="row2">
            <div>
              <label>X</label>
              <input id="prop-x" type="number" min="0" step="1"/>
            </div>
            <div>
              <label>Y</label>
              <input id="prop-y" type="number" min="0" step="1"/>
            </div>
          </div>

          <div class="row2">
            <div>
              <label>Ancho</label>
              <input id="prop-w" type="number" min="10" step="1"/>
            </div>
            <div>
              <label>Alto</label>
              <input id="prop-h" type="number" min="10" step="1"/>
            </div>
          </div>

          <div class="row2">
            <div>
              <label>Color</label>
              <input id="prop-color" type="color"/>
            </div>
            <div>
              <label>Texto</label>
              <input id="prop-text" type="text" placeholder="(solo texto)"/>
            </div>
          </div>

          <div class="prop">
            <label>Imagen (src)</label>
            <input id="prop-src" type="text" placeholder="(solo imagen)" disabled/>
          </div>

          <button class="btn danger" id="btn-delete">Eliminar elemento</button>
        </div>
      </div>

      <form id="csrfForm" method="post" style="display:none">
        <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>"/>
      </form>
    </aside>
  </main>

  <script>
    window.PRESENTATION_ID = <?php echo (int)$p['id']; ?>;
    window.CSRF_TOKEN = "<?php echo h(csrf_token()); ?>";
  </script>
  <script src="assets/editor.js"></script>
</body>
</html>
