<?php
$id = $_GET["id"] ?? null;

$data = [
    "id" => "",
    "titulo" => "",
    "descripcion" => "",
    "diapositivas" => []
];

if ($id && file_exists("presentaciones/$id")) {
    $data = json_decode(file_get_contents("presentaciones/$id"), true);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Editor de Presentación</title>
<link rel="stylesheet" href="../css/editor.css">
</head>
<body>

<div class="header-container">
    <h1>Editor de Presentación</h1>
    <div class="presentation-info">
        <div class="info-item">
            <span class="info-label">Slides:</span>
            <span id="slide-count">0</span>
        </div>
        <div class="info-item">
            <span class="info-label">Estado:</span>
            <span id="save-status">Guardado</span>
        </div>
    </div>
</div>

<!-- ================= FORMULARIO PRINCIPAL ================= -->
<form method="POST" action="guardar.php" id="presentation-form">

    <div class="form-section">
        <label>Título de la Presentación:</label>
        <input type="text" name="titulo" class="input" value="<?= htmlspecialchars($data["titulo"]) ?>" placeholder="Ingresa el título de tu presentación" required>

        <label>Descripción:</label>
        <textarea name="descripcion" placeholder="Describe brevemente tu presentación"><?= htmlspecialchars($data["descripcion"]) ?></textarea>
    </div>

    <br><br>

    <!-- CONTENEDOR DEL EDITOR COMPLETO -->
    <div class="editor-container">

        <!-- PANEL IZQUIERDO -->
        <div class="slides-panel">
            <div class="panel-header">
                <h3>Slides</h3>
                <button type="button" class="btn-new-slide" onclick="agregarDiapositiva()" title="Agregar nuevo slide">
                    <span class="icon">+</span> Nuevo Slide
                </button>
            </div>
            
            <div class="slides-actions">
                <button type="button" class="btn-action" onclick="duplicarSlide()" title="Duplicar slide actual">
                    <span class="icon">📄</span>
                </button>
                <button type="button" class="btn-action" onclick="eliminarSlide()" title="Eliminar slide actual">
                    <span class="icon">🗑️</span>
                </button>
                <button type="button" class="btn-action" onclick="reordenarSlides()" title="Reordenar slides">
                    <span class="icon">⇅</span>
                </button>
            </div>

            <div id="lista-slides" class="slides-list"></div>
        </div>

        <!-- PREVIEW -->
        <div class="preview-panel">
            <div class="panel-header">
                <h3>Vista Previa</h3>
                <div class="preview-controls">
                    <button type="button" class="btn-preview" onclick="slideAnterior()" id="btn-prev">◀</button>
                    <span id="slide-position">1 / 1</span>
                    <button type="button" class="btn-preview" onclick="slideSiguiente()" id="btn-next">▶</button>
                </div>
            </div>
            
            <div class="preview-box">
                <!--
                  IMPORTANTE:
                  - No bloquees eventos del preview (pointer-events:none), porque el drag & drop depende de mousedown/mousemove.
                  - Creamos estructura interna para que editor.js pueda actualizar título/contenido.
                -->
                <div id="preview" class="slide-preview">
                    <h1 id="preview-title"></h1>
                    <div id="preview-content"></div>
                </div>
            </div>
            
            <!-- <div class="preview-zoom">
                <button type="button" class="btn-zoom" onclick="cambiarZoom(-0.1)">-</button>
                <span id="zoom-level">100%</span>
                <button type="button" class="btn-zoom" onclick="cambiarZoom(0.1)">+</button>
            </div> -->
        </div>

        <!-- PANEL DERECHO -->
        <div class="editor-options">
            <div class="panel-header">
                <h3>Editar Slide</h3>
                <div class="slide-indicator" id="current-slide-indicator">Slide 1</div>
            </div>

            <div class="options-section">
                <!--<h4>Contenido</h4>

                <label>Título del Slide</label>
                <input type="text" id="slide-title" class="input" placeholder="Título del slide" oninput="actualizarSlide()">
                
                <label>Contenido</label>
                <div class="content-toolbar">
                    <button type="button" class="tool-btn" onclick="formatearTexto('bold')" title="Negrita"><b>B</b></button>
                    <button type="button" class="tool-btn" onclick="formatearTexto('italic')" title="Itálica"><i>I</i></button>
                    <button type="button" class="tool-btn" onclick="formatearTexto('bullet')" title="Viñeta">•</button>
                    <button type="button" class="tool-btn" onclick="formatearTexto('number')" title="Numeración">1.</button>
                </div>
                <textarea id="slide-content" placeholder="Escribe el contenido de tu slide aquí..." oninput="actualizarSlide()"></textarea>-->
                
                <div class="menu-item" id="add-text-btn">
                    <span class="icon">T</span>
                    <span class="text">Texto</span>
                </div>

            </div>

            <div class="options-section">
                <h4>Estilo</h4>
                
                <label>Color de Fondo</label>
                <div class="color-grid">
                    <div class="color-btn" style="background:#3b82f6" onclick="cambiarColor('#3b82f6')" data-color="#3b82f6"></div>
                    <div class="color-btn" style="background:#10b981" onclick="cambiarColor('#10b981')" data-color="#10b981"></div>
                    <div class="color-btn" style="background:#8b5cf6" onclick="cambiarColor('#8b5cf6')" data-color="#8b5cf6"></div>
                    <div class="color-btn" style="background:#ef4444" onclick="cambiarColor('#ef4444')" data-color="#ef4444"></div>
                    <div class="color-btn" style="background:#f97316" onclick="cambiarColor('#f97316')" data-color="#f97316"></div>
                    <div class="color-btn" style="background:#6366f1" onclick="cambiarColor('#6366f1')" data-color="#6366f1"></div>
                    <div class="color-btn" style="background:#06b6d4" onclick="cambiarColor('#06b6d4')" data-color="#06b6d4"></div>
                    <div class="color-btn" style="background:#ec4899" onclick="cambiarColor('#ec4899')" data-color="#ec4899"></div>
                    <div class="color-btn" style="background:#f59e0b" onclick="cambiarColor('#f59e0b')" data-color="#f59e0b"></div>
                </div>
                
                <div class="color-custom">
                    <label>Color Personalizado</label>
                    <input type="color" id="custom-color" onchange="cambiarColor(this.value)" value="#3b82f6">
                </div>

                <label>Tamaño de Fuente</label>
                <select id="font-size" onchange="actualizarSlide()">
                    <option value="18px">Pequeño</option>
                    <option value="22px">Mediano</option>
                    <option value="28px" selected>Grande</option>
                    <option value="36px">Muy Grande</option>
                    <option value="42px">Extra Grande</option>
                </select>
                
                <label>Alineación de Texto</label>
                <div class="alignment-buttons">
                    <button type="button" class="align-btn" onclick="cambiarAlineacion('left')" title="Alinear a la izquierda">◀</button>
                    <button type="button" class="align-btn" onclick="cambiarAlineacion('center')" title="Centrar">●</button>
                    <button type="button" class="align-btn" onclick="cambiarAlineacion('right')" title="Alinear a la derecha">▶</button>
                </div>
            </div>
        </div>

    </div>

    <!-- JSON FINAL -->
    <input type="hidden" id="data" name="data">
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="form-actions">
        <button type="button" class="btn-secondary" onclick="previsualizarPresentacion()">Vista Previa Completa</button>
        <button type="submit" class="btn-primary">Guardar Presentación</button>
    </div>

</form>

<!-- MODAL PARA VISTA PREVIA COMPLETA -->
<div id="preview-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Vista Previa de la Presentación</h2>
            <button type="button" class="close-modal" onclick="cerrarModal()">×</button>
        </div>
        <div class="modal-body">
            <div id="full-preview" class="full-preview"></div>
            <div class="modal-controls">
                <button type="button" class="btn-preview" onclick="cambiarSlideModal(-1)">◀ Anterior</button>
                <span id="modal-slide-position">Slide 1 de 1</span>
                <button type="button" class="btn-preview" onclick="cambiarSlideModal(1)">Siguiente ▶</button>
            </div>
        </div>
    </div>
</div>

<!-- ========== LÓGICA DEL EDITOR MEJORADA ========== -->
<!-- Datos iniciales (diapositivas) para editor.js -->
<script id="initial-data" type="application/json"><?= json_encode($data["diapositivas"], JSON_UNESCAPED_UNICODE) ?></script>
<script src="../js/editor.js"></script>

</body>
</html>
