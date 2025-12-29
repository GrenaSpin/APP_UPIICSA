<?php
$id = $_GET["id"] ?? null;

$data = [
  "id" => "",
  "titulo" => "Mi Presentación",
  "descripcion" => "",
  "diapositivas" => []
];

if ($id && file_exists("presentaciones/$id")) {
  $json = file_get_contents("presentaciones/$id");
  $tmp = json_decode($json, true);
  if (is_array($tmp)) $data = $tmp;
}

$slides = $data["diapositivas"] ?? [];
if (!is_array($slides)) $slides = [];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Editor de Presentación</title>
</head>
<body>
<div class="app">
  <!-- Left: slides -->
  <section class="panel">
    <header>
      <h3>Slides</h3>
      <div class="row">
        <button class="btn" id="btn-add-slide" type="button">+ Slide</button>
        <button class="btn danger" id="btn-delete-slide" type="button">🗑</button>
      </div>
    </header>
    <div class="list" id="lista-slides"></div>
    <div style="padding:10px;border-top:1px solid var(--stroke)" class="muted">
      Slide <span id="slide-position">1</span> / <span id="slide-count">1</span>
    </div>
  </section>

  <!-- Center: canvas/preview -->
  <section class="center">
    <div class="stage-wrap">
      <div class="stage-toolbar">
        <div class="row">
          <button class="btn" id="btn-prev" type="button">◀</button>
          <div class="muted" id="current-slide-indicator">Slide 1</div>
          <button class="btn" id="btn-next" type="button">▶</button>
        </div>
        <div class="row">
          <button class="btn" id="add-text-btn" type="button">+ Texto</button>
          <button class="btn" id="full-preview" type="button">Pantalla completa</button>
          <button class="btn" id="zoom-out" type="button">-</button>
          <div class="muted" id="zoom-level">100%</div>
          <button class="btn" id="zoom-in" type="button">+</button>
          <button class="btn" id="zoom-reset" type="button">Reset</button>
          <div class="muted" id="save-status">Guardado</div>
        </div>
      </div>

      <div style="overflow:auto; padding:6px;">
        <div id="preview" class="stage">
          <div class="stage-inner">
            <h1 id="preview-title"></h1>
            <div id="preview-content"></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Right: editor -->
  <section class="panel">
    <header>
      <h3>Editar Slide</h3>
      <span class="muted">Cambios se guardan al enviar</span>
    </header>

    <form class="form" id="editor-form" method="POST" action="guardar.php">
      <input type="hidden" name="id" value="<?= htmlspecialchars($data["id"] ?? "") ?>">
      <label>Título de presentación</label>
      <input type="text" name="titulo" value="<?= htmlspecialchars($data["titulo"] ?? "") ?>" placeholder="Título">

      <label>Descripción</label>
      <input type="text" name="descripcion" value="<?= htmlspecialchars($data["descripcion"] ?? "") ?>" placeholder="Descripción">

      <hr style="border:0;border-top:1px solid var(--stroke);width:100%">

      <label>Título del slide</label>
      <input type="text" id="slide-title" placeholder="Título del slide">

      <label>Contenido</label>
      <div class="row">
        <button class="toolbar-btn" id="tool-bold" type="button" title="Negrita"><b>B</b></button>
        <button class="toolbar-btn" id="tool-italic" type="button" title="Itálica"><i>I</i></button>
      </div>
      <textarea id="slide-content" placeholder="Escribe aquí..."></textarea>

      <div id="text-controls" style="display:none;">
        <hr style="border:0;border-top:1px solid var(--stroke);width:100%">
        <div class="muted" style="font-weight:700;">Texto seleccionado</div>
        <label>Tamaño de letra</label>
        <input type="number" id="text-font-size" min="8" max="96" value="18">
        <div class="row">
          <button class="toolbar-btn" id="text-bold-btn" type="button" title="Negritas"><b>B</b></button>
        </div>
        <div class="muted">Tip: click derecho para eliminar · Ctrl+C/Ctrl+V · Ctrl+D · Delete</div>
      </div>

      <input type="hidden" id="data" name="data" value="">

      <div class="row" style="margin-top:6px;">
        <button class="btn primary" type="submit">Guardar</button>
        <a class="btn" href="index.php" style="text-decoration:none;display:inline-flex;align-items:center;">Volver</a>
      </div>
    </form>
  </section>
</div>

<!-- Modal -->
<div id="preview-modal">
  <div class="modal-card">
    <div class="modal-head">
      <div class="row">
        <strong id="modal-slide-title">Slide</strong>
        <span class="muted" id="modal-slide-position">Slide 1 de 1</span>
      </div>
      <div class="row">
        <button class="btn" id="modal-prev" type="button">◀</button>
        <button class="btn" id="modal-next" type="button">▶</button>
        <button class="btn danger" id="modal-close" type="button">Cerrar</button>
      </div>
    </div>
    <div class="modal-body" id="modal-slide-body"></div>
  </div>
</div>

<script id="initial-data" type="application/json"><?= json_encode($slides, JSON_UNESCAPED_UNICODE) ?></script>
<script src="../js/editor.js"></script>
</body>
</html>