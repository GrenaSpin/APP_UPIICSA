<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
  header('Location: login.php');
  exit;
}

$id = $_GET["id"] ?? "";

$data = [
  "id" => $id,
  "titulo" => "",
  "descripcion" => "",
  "diapositivas" => []
];

if ($id && file_exists("presentaciones/$id")) {
  $decoded = json_decode(file_get_contents("presentaciones/$id"), true);
  if (is_array($decoded)) $data = array_merge($data, $decoded);
}

$slides = $data["diapositivas"] ?? [];
if (!is_array($slides)) $slides = [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Editor - <?= htmlspecialchars($data["titulo"] ?: "Presentación") ?></title>
  <link rel="stylesheet" href="../css/editor.css">
</head>
<body>

<div class="header-container">
  <h1>Editor de Presentación</h1>

  <div class="presentation-info">
    <div class="info-item">
      <span class="info-label">Slide:</span>
      <span id="slide-position">1</span>/<span id="slide-count">1</span>
    </div>
    <div class="info-item">
      <span class="info-label">Estado:</span>
      <span id="save-status">Guardado</span>
    </div>
  </div>
</div>

<div class="app">
  <!-- LEFT: slides -->
  <section class="panel">
    <header>
      <h3>Slides</h3>
      <span class="muted">Lista</span>
    </header>

    <div class="list">
      <div class="row">
        <button type="button" class="btn primary" id="btn-add-slide">+ Slide</button>
        <button type="button" class="btn danger" id="btn-delete-slide">Eliminar</button>
      </div>

      <div id="lista-slides" class="list" style="padding:0; gap:8px;"></div>
    </div>
  </section>

  <!-- CENTER: preview -->
  <section class="center">
    <div class="stage-wrap">
      <div class="stage-toolbar">
        <div class="row">
          <button type="button" class="btn" id="btn-prev">◀ Anterior</button>
          <button type="button" class="btn" id="btn-next">Siguiente ▶</button>
          <span class="muted" id="current-slide-indicator">Slide 1</span>
        </div>

        <div class="row">
          <button type="button" class="btn" id="zoom-out">−</button>
          <span class="muted" id="zoom-level">100%</span>
          <button type="button" class="btn" id="zoom-in">+</button>
          <button type="button" class="btn" id="zoom-reset">Reset</button>
          <button type="button" class="btn" id="full-preview">Pantalla completa</button>
        </div>
      </div>

      <div class="stage" id="preview">
        <div class="stage-inner">
          <h1 id="preview-title"></h1>
          <div id="preview-content"></div>
        </div>
      </div>
    </div>
  </section>

  <!-- RIGHT: editor -->
  <section class="panel">
    <header>
      <h3>Editar</h3>
      <span class="muted">Propiedades</span>
    </header>

    <form method="POST" action="guardar.php" id="editor-form" class="form">
      <input type="hidden" name="id" value="<?= htmlspecialchars($data["id"] ?? "") ?>">
      <input type="hidden" id="data" name="data" value="">

      <div>
        <label>Título de la Presentación</label>
        <input type="text" name="titulo" value="<?= htmlspecialchars($data["titulo"] ?? "") ?>" />
      </div>

      <div>
        <label>Descripción</label>
        <input type="text" name="descripcion" value="<?= htmlspecialchars($data["descripcion"] ?? "") ?>" />
      </div>

      <hr style="border:0;border-top:1px solid rgba(255,255,255,.08);width:100%;">

      <div>
        <label>Título del Slide</label>
        <input type="text" id="slide-title" />
      </div>

      <div>
        <label>Contenido del Slide</label>
        <div class="row" style="margin-top:8px;">
          <button type="button" class="toolbar-btn" id="tool-bold" title="Negrita"><b>B</b></button>
          <button type="button" class="toolbar-btn" id="tool-italic" title="Itálica"><i>I</i></button>
        </div>
        <textarea id="slide-content" placeholder="Escribe aquí..."></textarea>
      </div>

      <div class="options-section">
        <label>Fondo del slide</label>
        <div class="color-grid">
          <button type="button" class="color-btn" data-bgcolor="#111827" style="background:#111827"></button>
          <button type="button" class="color-btn" data-bgcolor="#1e3a8a" style="background:#1e3a8a"></button>
          <button type="button" class="color-btn" data-bgcolor="#2563eb" style="background:#2563eb"></button>
          <button type="button" class="color-btn" data-bgcolor="#16a34a" style="background:#16a34a"></button>
          <button type="button" class="color-btn" data-bgcolor="#e11d48" style="background:#e11d48"></button>
          <button type="button" class="color-btn" data-bgcolor="#7c3aed" style="background:#7c3aed"></button>
        </div>
        <div style="margin-top:10px;">
          <label>Color personalizado</label>
          <input type="color" id="custom-color" value="#111827" style="width:100%;height:40px;border:0;border-radius:10px;">
        </div>
      </div>

      <div class="options-section">
        <label>Tamaño del título</label>
        <select id="slide-size">
          <option value="28px">28px</option>
          <option value="34px" selected>34px</option>
          <option value="40px">40px</option>
          <option value="48px">48px</option>
          <option value="56px">56px</option>
        </select>
      </div>

      <div class="options-section">
        <label>Alineación</label>
        <div class="alignment-buttons">
          <button type="button" class="align-btn" id="align-left">⯇</button>
          <button type="button" class="align-btn active" id="align-center">≡</button>
          <button type="button" class="align-btn" id="align-right">⯈</button>
        </div>
      </div>

      <div class="options-section">
        <label>Canvas</label>
        <button type="button" class="btn" id="add-text-btn">+ Texto libre</button>

        <div id="text-controls" style="display:none; margin-top:10px;">
          <label>Tamaño texto seleccionado</label>
          <input type="number" id="text-font-size" min="8" max="200" value="18">
          <div class="row" style="margin-top:8px;">
            <button type="button" class="btn" id="text-bold-btn"><b>B</b></button>
            <span class="muted">Tip: Delete/Backspace elimina, click derecho elimina</span>
          </div>
        </div>
      </div>

      <div class="row" style="justify-content:space-between; margin-top:6px;">
        <button type="submit" class="btn primary">Guardar</button>
        <button type="button" class="btn" onclick="window.location='index.php'">Salir</button>
      </div>
    </form>
  </section>
</div>

<!-- Modal preview -->
<div id="preview-modal">
  <div class="modal-card">
    <div class="modal-head">
      <div class="row" style="gap:10px;">
        <strong id="modal-slide-title">Vista previa</strong>
        <span class="muted" id="modal-slide-position">Slide 1 de 1</span>
      </div>
      <div class="row">
        <button type="button" class="btn" id="modal-prev">◀</button>
        <button type="button" class="btn" id="modal-next">▶</button>
        <button type="button" class="btn" id="modal-close">Cerrar</button>
      </div>
    </div>
    <div class="modal-body" id="modal-slide-body"></div>
  </div>
</div>

<!-- Datos iniciales (diapositivas) para editor.js -->
<script id="initial-data" type="application/json"><?= json_encode($slides, JSON_UNESCAPED_UNICODE) ?></script>
<script src="../js/editor.js"></script>

</body>
</html>
