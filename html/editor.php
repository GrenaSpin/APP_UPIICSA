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
                <div id="preview" class="slide-preview">
                    <h1 id="preview-title">Título del Slide</h1>
                    <div id="preview-content" class="slide-content">
                        <p>Contenido del slide...</p>
                    </div>
                </div>
            </div>
            
            <div class="preview-zoom">
                <button type="button" class="btn-zoom" onclick="cambiarZoom(-0.1)">-</button>
                <span id="zoom-level">100%</span>
                <button type="button" class="btn-zoom" onclick="cambiarZoom(0.1)">+</button>
            </div>
        </div>

        <!-- PANEL DERECHO -->
        <div class="editor-options">
            <div class="panel-header">
                <h3>Editar Slide</h3>
                <div class="slide-indicator" id="current-slide-indicator">Slide 1</div>
            </div>

            <div class="options-section">
                <h4>Contenido</h4>
                
                <label>Título del Slide</label>
                <input type="text" id="slide-title" class="input" placeholder="Título del slide" oninput="actualizarSlide()">
                
                <label>Contenido</label>
                <div class="content-toolbar">
                    <button type="button" class="tool-btn" onclick="formatearTexto('bold')" title="Negrita"><b>B</b></button>
                    <button type="button" class="tool-btn" onclick="formatearTexto('italic')" title="Itálica"><i>I</i></button>
                    <button type="button" class="tool-btn" onclick="formatearTexto('bullet')" title="Viñeta">•</button>
                    <button type="button" class="tool-btn" onclick="formatearTexto('number')" title="Numeración">1.</button>
                </div>
                <textarea id="slide-content" placeholder="Escribe el contenido de tu slide aquí..." oninput="actualizarSlide()"></textarea>
                
                <div class="content-hint">
                    <small>💡 Usa * para viñetas y números para listas numeradas</small>
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
<script>
// Variables globales mejoradas
let slides = <?= json_encode($data["diapositivas"] ?? []) ?>;
let actual = -1;
let zoomLevel = 1;
let autoSaveTimer = null;
let currentModalSlide = 0;

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    inicializarEditor();
});

/* =======================
   INICIALIZAR EDITOR
   ======================= */
function inicializarEditor() {
    // Si no hay slides, crear uno por defecto
    if (slides.length === 0) {
        agregarDiapositiva();
    } else {
        cargarListaSlides();
        seleccionar(0);
    }
    
    actualizarContadores();
    configurarAutoSave();
}

/* =======================
   AGREGAR DIAPOSITIVA
   ======================= */
function agregarDiapositiva() {
    const nuevoSlide = {
        titulo: "Nuevo Slide",
        contenido: "Escribe aquí tu contenido...",
        color: "#3b82f6",
        size: "28px",
        alineacion: "center"
    };
    
    slides.push(nuevoSlide);
    cargarListaSlides();
    seleccionar(slides.length - 1);
    marcarCambios();
}

/* =======================
   DUPLICAR SLIDE ACTUAL
   ======================= */
function duplicarSlide() {
    if (actual < 0) return;
    
    const slideDuplicado = JSON.parse(JSON.stringify(slides[actual]));
    slideDuplicado.titulo = slideDuplicado.titulo + " (Copia)";
    
    slides.splice(actual + 1, 0, slideDuplicado);
    cargarListaSlides();
    seleccionar(actual + 1);
    marcarCambios();
}

/* =======================
   ELIMINAR SLIDE ACTUAL
   ======================= */
function eliminarSlide() {
    if (slides.length <= 1) {
        alert("No puedes eliminar el único slide. La presentación debe tener al menos un slide.");
        return;
    }
    
    if (confirm("¿Estás seguro de que quieres eliminar este slide?")) {
        slides.splice(actual, 1);
        
        // Ajustar el índice actual
        if (actual >= slides.length) {
            actual = slides.length - 1;
        }
        
        cargarListaSlides();
        if (slides.length > 0) {
            seleccionar(actual);
        }
        marcarCambios();
    }
}

/* =======================
   REORDENAR SLIDES
   ======================= */
function reordenarSlides() {
    // Implementación básica - en una versión más avanzada podrías usar drag & drop
    alert("Función de reordenar slides - Para implementar con drag & drop");
}

/* =======================
   CARGAR LISTA LATERAL
   ======================= */
function cargarListaSlides() {
    const cont = document.getElementById("lista-slides");
    cont.innerHTML = "";

    slides.forEach((s, i) => {
        const div = document.createElement("div");
        div.className = "slide-item" + (i === actual ? " active" : "");
        div.innerHTML = `
            <div class="slide-number">${i+1}</div>
            <div class="slide-preview-mini">${s.titulo}</div>
        `;
        div.onclick = () => seleccionar(i);
        cont.appendChild(div);
    });
    
    actualizarContadores();
}

/* =======================
   SELECCIONAR SLIDE
   ======================= */
function seleccionar(i) {
    actual = i;
    const s = slides[i];

    // Actualizar controles de edición
    document.getElementById("slide-title").value = s.titulo;
    document.getElementById("slide-content").value = s.contenido;
    document.getElementById("font-size").value = s.size;

    // Actualizar vista previa
    actualizarVistaPrevia();
    
    // Actualizar indicadores
    document.getElementById("current-slide-indicator").textContent = `Slide ${i+1}`;
    document.getElementById("slide-position").textContent = `${i+1} / ${slides.length}`;
    
    // Actualizar botones de navegación
    document.getElementById("btn-prev").disabled = i === 0;
    document.getElementById("btn-next").disabled = i === slides.length - 1;
    
    // Resaltar color activo
    document.querySelectorAll('.color-btn').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-color') === s.color);
    });
    
    cargarListaSlides();
}

/* =======================
   ACTUALIZAR VISTA PREVIA
   ======================= */
function actualizarVistaPrevia() {
    if (actual < 0) return;
    
    const s = slides[actual];
    const preview = document.getElementById("preview");
    const title = document.getElementById("preview-title");
    const content = document.getElementById("preview-content");
    
    // Aplicar estilos
    preview.style.background = s.color;
    preview.style.fontSize = s.size;
    preview.style.textAlign = s.alineacion || "center";
    
    // Actualizar contenido
    title.textContent = s.titulo;
    
    // Procesar contenido con formato básico
    content.innerHTML = procesarContenido(s.contenido);
}

/* =======================
   PROCESAR CONTENIDO CON FORMATO
   ======================= */
function procesarContenido(texto) {
    if (!texto) return "";
    
    // Convertir saltos de línea en <br>
    let html = texto.replace(/\n/g, '<br>');
    
    // Convertir viñetas (*) en listas
    html = html.replace(/\* (.*?)(?=\n|$)/g, '<li>$1</li>');
    html = html.replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>');
    
    // Convertir números en listas numeradas
    html = html.replace(/\d+\. (.*?)(?=\n|$)/g, '<li>$1</li>');
    html = html.replace(/(<li>.*<\/li>)/s, '<ol>$1</ol>');
    
    return html;
}

/* =======================
   ACTUALIZAR SLIDE
   ======================= */
function actualizarSlide() {
    if (actual < 0) return;

    slides[actual].titulo = document.getElementById("slide-title").value;
    slides[actual].contenido = document.getElementById("slide-content").value;
    slides[actual].size = document.getElementById("font-size").value;

    actualizarVistaPrevia();
    cargarListaSlides();
    marcarCambios();
}

/* =======================
   CAMBIAR COLOR
   ======================= */
function cambiarColor(c) {
    if (actual < 0) return;
    slides[actual].color = c;
    actualizarVistaPrevia();
    
    // Actualizar selección visual de colores
    document.querySelectorAll('.color-btn').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-color') === c);
    });
    
    marcarCambios();
}

/* =======================
   CAMBIAR ALINEACIÓN
   ======================= */
function cambiarAlineacion(align) {
    if (actual < 0) return;
    slides[actual].alineacion = align;
    actualizarVistaPrevia();
    marcarCambios();
}

/* =======================
   HERRAMIENTAS DE FORMATO DE TEXTO
   ======================= */
function formatearTexto(tipo) {
    const textarea = document.getElementById("slide-content");
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = textarea.value.substring(start, end);
    
    let nuevoTexto = "";
    
    switch(tipo) {
        case 'bold':
            nuevoTexto = `**${selectedText}**`;
            break;
        case 'italic':
            nuevoTexto = `_${selectedText}_`;
            break;
        case 'bullet':
            nuevoTexto = selectedText ? `* ${selectedText}` : `* `;
            break;
        case 'number':
            nuevoTexto = selectedText ? `1. ${selectedText}` : `1. `;
            break;
    }
    
    // Reemplazar texto seleccionado
    textarea.value = textarea.value.substring(0, start) + nuevoTexto + textarea.value.substring(end);
    
    // Actualizar slide
    actualizarSlide();
    
    // Restaurar foco y selección
    textarea.focus();
    textarea.setSelectionRange(start, start + nuevoTexto.length);
}

/* =======================
   NAVEGACIÓN ENTRE SLIDES
   ======================= */
function slideAnterior() {
    if (actual > 0) {
        seleccionar(actual - 1);
    }
}

function slideSiguiente() {
    if (actual < slides.length - 1) {
        seleccionar(actual + 1);
    }
}

/* =======================
   ZOOM DE VISTA PREVIA
   ======================= */
function cambiarZoom(delta) {
    zoomLevel = Math.max(0.5, Math.min(2, zoomLevel + delta));
    document.getElementById("preview").style.transform = `scale(${zoomLevel})`;
    document.getElementById("zoom-level").textContent = Math.round(zoomLevel * 100) + "%";
}

/* =======================
   VISTA PREVIA COMPLETA
   ======================= */
function previsualizarPresentacion() {
    if (slides.length === 0) {
        alert("No hay slides para previsualizar");
        return;
    }
    
    currentModalSlide = 0;
    actualizarModalPreview();
    document.getElementById("preview-modal").style.display = "block";
}

function cerrarModal() {
    document.getElementById("preview-modal").style.display = "none";
}

function cambiarSlideModal(direction) {
    currentModalSlide += direction;
    
    if (currentModalSlide < 0) currentModalSlide = 0;
    if (currentModalSlide >= slides.length) currentModalSlide = slides.length - 1;
    
    actualizarModalPreview();
}

function actualizarModalPreview() {
    const modalPreview = document.getElementById("full-preview");
    const s = slides[currentModalSlide];
    
    modalPreview.innerHTML = `
        <div class="modal-slide" style="background: ${s.color}; font-size: ${s.size}; text-align: ${s.alineacion || 'center'}">
            <h1>${s.titulo}</h1>
            <div class="modal-content">${procesarContenido(s.contenido)}</div>
        </div>
    `;
    
    document.getElementById("modal-slide-position").textContent = 
        `Slide ${currentModalSlide + 1} de ${slides.length}`;
}

/* =======================
   AUTO-GUARDADO Y ESTADO
   ======================= */
function configurarAutoSave() {
    // Configurar auto-guardado cada 30 segundos
    setInterval(function() {
        if (document.getElementById("save-status").textContent === "Cambios sin guardar") {
            guardarJSON();
            document.getElementById("save-status").textContent = "Auto-guardado";
            setTimeout(() => {
                if (document.getElementById("save-status").textContent === "Auto-guardado") {
                    document.getElementById("save-status").textContent = "Guardado";
                }
            }, 2000);
        }
    }, 30000);
}

function marcarCambios() {
    document.getElementById("save-status").textContent = "Cambios sin guardar";
    actualizarContadores();
}

function actualizarContadores() {
    document.getElementById("slide-count").textContent = slides.length;
}

/* =======================
   GUARDAR JSON
   ======================= */
function guardarJSON() {
    document.getElementById("data").value = JSON.stringify(slides);
    document.getElementById("save-status").textContent = "Guardando...";
    
    // Simular guardado
    setTimeout(() => {
        document.getElementById("save-status").textContent = "Guardado";
    }, 500);
}

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
    const modal = document.getElementById("preview-modal");
    if (event.target === modal) {
        cerrarModal();
    }
}

// Prevenir pérdida accidental de datos
window.addEventListener('beforeunload', function(e) {
    if (document.getElementById("save-status").textContent === "Cambios sin guardar") {
        e.preventDefault();
        e.returnValue = '';
    }
});
</script>

</body>
</html>
