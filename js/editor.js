// Variables globales mejoradas
let slides = [];
let actual = -1;
let zoomLevel = 1;
let autoSaveTimer = null;
let currentModalSlide = 0;

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    // Obtener datos iniciales desde el HTML
    const initialDataElement = document.getElementById('initial-data');
    if (initialDataElement) {
        slides = JSON.parse(initialDataElement.textContent);
    }
    
    inicializarEditor();
});

// -----------------------
// Helpers de formateo
// -----------------------
function escapeHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

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
        alineacion: "center",
        // Textareas arrastrables de este slide
        textos: []
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
    if (!cont) return;
    
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
    const slideTitle = document.getElementById("slide-title");
    const slideContent = document.getElementById("slide-content");
    const fontSize = document.getElementById("font-size");
    
    if (slideTitle) slideTitle.value = s.titulo;
    if (slideContent) slideContent.value = s.contenido;
    if (fontSize) fontSize.value = s.size;

    // Actualizar vista previa
    actualizarVistaPrevia();
    
    // Actualizar indicadores
    const currentIndicator = document.getElementById("current-slide-indicator");
    const slidePosition = document.getElementById("slide-position");
    
    if (currentIndicator) currentIndicator.textContent = `Slide ${i+1}`;
    if (slidePosition) slidePosition.textContent = `${i+1} / ${slides.length}`;
    
    // Actualizar botones de navegación
    const btnPrev = document.getElementById("btn-prev");
    const btnNext = document.getElementById("btn-next");
    
    if (btnPrev) btnPrev.disabled = i === 0;
    if (btnNext) btnNext.disabled = i === slides.length - 1;
    
    // Resaltar color activo
    document.querySelectorAll('.color-btn').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-color') === s.color);
    });
    
    cargarListaSlides();

    // Renderizar textos arrastrables del slide seleccionado
    renderSlideTexts();
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
    
    if (!preview || !title || !content) return;
    
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
    // Soporte básico para lo que ya usas en la toolbar:
    // **negrita**, _itálica_, listas con "* " y numeradas "1. ".
    const raw = String(texto ?? '');

    // Escapar HTML primero
    const safe = escapeHtml(raw);

    // Transformaciones inline
    let html = safe
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/_(.+?)_/g, '<em>$1</em>');

    // Procesar líneas para listas
    const lines = html.split(/\r?\n/);
    let out = '';
    let inUl = false;
    let inOl = false;

    const closeLists = () => {
        if (inUl) { out += '</ul>'; inUl = false; }
        if (inOl) { out += '</ol>'; inOl = false; }
    };

    for (const line of lines) {
        const trimmed = line.trim();

        // Viñetas
        if (/^\*\s+/.test(trimmed)) {
            if (inOl) { out += '</ol>'; inOl = false; }
            if (!inUl) { out += '<ul>'; inUl = true; }
            out += `<li>${trimmed.replace(/^\*\s+/, '')}</li>`;
            continue;
        }

        // Numeración
        if (/^\d+\.\s+/.test(trimmed)) {
            if (inUl) { out += '</ul>'; inUl = false; }
            if (!inOl) { out += '<ol>'; inOl = true; }
            out += `<li>${trimmed.replace(/^\d+\.\s+/, '')}</li>`;
            continue;
        }

        // Línea normal
        closeLists();
        if (trimmed.length === 0) {
            out += '<br>';
        } else {
            out += `<p>${line}</p>`;
        }
    }
    closeLists();

    return out;
}

/* =======================
   ACTUALIZAR SLIDE
   ======================= */
function actualizarSlide() {
    if (actual < 0) return;

    const slideTitle = document.getElementById("slide-title");
    const slideContent = document.getElementById("slide-content");
    const fontSize = document.getElementById("font-size");
    
    if (slideTitle && slideContent && fontSize) {
        slides[actual].titulo = slideTitle.value;
        slides[actual].contenido = slideContent.value;
        slides[actual].size = fontSize.value;
    }

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
    if (!textarea) return;
    
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
    const preview = document.getElementById("preview");
    const zoomLevelElement = document.getElementById("zoom-level");
    
    if (!preview || !zoomLevelElement) return;
    
    zoomLevel = Math.max(0.5, Math.min(2, zoomLevel + delta));
    preview.style.transform = `scale(${zoomLevel})`;
    zoomLevelElement.textContent = Math.round(zoomLevel * 100) + "%";
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
    
    const modal = document.getElementById("preview-modal");
    if (modal) {
        modal.style.display = "block";
    }
}

function cerrarModal() {
    const modal = document.getElementById("preview-modal");
    if (modal) {
        modal.style.display = "none";
    }
}

function cambiarSlideModal(direction) {
    currentModalSlide += direction;
    
    if (currentModalSlide < 0) currentModalSlide = 0;
    if (currentModalSlide >= slides.length) currentModalSlide = slides.length - 1;
    
    actualizarModalPreview();
}

function actualizarModalPreview() {
    const modalPreview = document.getElementById("full-preview");
    const modalPosition = document.getElementById("modal-slide-position");
    
    if (!modalPreview || !modalPosition) return;
    
    const s = slides[currentModalSlide];
    
    modalPreview.innerHTML = `
        <div class="modal-slide" style="background: ${s.color}; font-size: ${s.size}; text-align: ${s.alineacion || 'center'}">
            <h1>${s.titulo}</h1>
            <div class="modal-content">${procesarContenido(s.contenido)}</div>
        </div>
    `;
    
    modalPosition.textContent = `Slide ${currentModalSlide + 1} de ${slides.length}`;
}

/* =======================
   AUTO-GUARDADO Y ESTADO
   ======================= */
function configurarAutoSave() {
    // Configurar auto-guardado cada 30 segundos
    setInterval(function() {
        const saveStatus = document.getElementById("save-status");
        if (saveStatus && saveStatus.textContent === "Cambios sin guardar") {
            guardarJSON();
            saveStatus.textContent = "Auto-guardado";
            setTimeout(() => {
                if (saveStatus.textContent === "Auto-guardado") {
                    saveStatus.textContent = "Guardado";
                }
            }, 2000);
        }
    }, 30000);
}

function marcarCambios() {
    const saveStatus = document.getElementById("save-status");
    if (saveStatus) {
        saveStatus.textContent = "Cambios sin guardar";
    }
    actualizarContadores();
}

function actualizarContadores() {
    const slideCount = document.getElementById("slide-count");
    if (slideCount) {
        slideCount.textContent = slides.length;
    }
}

/* =======================
   GUARDAR JSON
   ======================= */
function guardarJSON() {
    const dataInput = document.getElementById("data");
    const saveStatus = document.getElementById("save-status");
    
    if (dataInput) {
        dataInput.value = JSON.stringify(slides);
    }
    
    if (saveStatus) {
        saveStatus.textContent = "Guardando...";
        
        // Simular guardado
        setTimeout(() => {
            saveStatus.textContent = "Guardado";
        }, 500);
    }
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
    const saveStatus = document.getElementById("save-status");
    if (saveStatus && saveStatus.textContent === "Cambios sin guardar") {
        e.preventDefault();
        e.returnValue = '';
    }
});

const initialDataElement = document.getElementById('initial-data');
if (initialDataElement) {
    slides = JSON.parse(initialDataElement.textContent);
}

/* =======================
   TEXTOS ARRASTRABLES POR SLIDE
   ======================= */

// Asegurarnos de que el contenedor preview existe
const canvas = document.getElementById('preview');
const addTextBtn = document.getElementById('add-text-btn');

// Utilidades
function ensureTextArray(slideIndex) {
    if (!slides[slideIndex]) return;
    if (!Array.isArray(slides[slideIndex].textos)) {
        slides[slideIndex].textos = [];
    }
}

// Renderiza (borra y crea) todos los textos del slide actual
function renderSlideTexts() {
    if (actual < 0 || !canvas) return;

    // Elimina los elementos .draggable-text visibles previos
    const existing = canvas.querySelectorAll('.draggable-text');
    existing.forEach(el => el.remove());

    ensureTextArray(actual);

    // Crear textarea por cada texto guardado en slides[actual].textos
    slides[actual].textos.forEach((t, index) => {
        createDraggableTextElement(actual, index, t);
    });
}

// Crea el elemento textarea arrastrable y lo enlaza con slides[slideIndex].textos[index]
function createDraggableTextElement(slideIndex, index, textoObj) {
    const el = document.createElement('textarea');
    el.className = 'draggable-text';
    el.value = textoObj.texto ?? '';
    el.dataset.slide = slideIndex;
    el.dataset.index = index;

    // Estilos base si no vienen
    const w = textoObj.width ?? 200;
    const h = textoObj.height ?? 100;
    const top = (typeof textoObj.top !== 'undefined') ? textoObj.top : 50;
    const left = (typeof textoObj.left !== 'undefined') ? textoObj.left : 50;

    el.style.position = 'absolute';
    el.style.top = `${top}px`;
    el.style.left = `${left}px`;
    el.style.width = `${w}px`;
    el.style.height = `${h}px`;
    el.style.resize = 'both';
    el.style.zIndex = 50;
    el.style.cursor = 'move'; // para indicar que se puede mover

    // Cuando el usuario escribe, actualizamos directamente el objeto
    el.addEventListener('input', (ev) => {
        const sIdx = Number(el.dataset.slide);
        const iIdx = Number(el.dataset.index);
        // proteger por si se eliminó
        if (!slides[sIdx] || !slides[sIdx].textos || !slides[sIdx].textos[iIdx]) return;
        slides[sIdx].textos[iIdx].texto = el.value;
        // también guardar tamaño actual
        slides[sIdx].textos[iIdx].width = el.clientWidth;
        slides[sIdx].textos[iIdx].height = el.clientHeight;
        marcarCambios();
    });

    // Al terminar de redimensionar (pointerup), actualizar tamaño
    el.addEventListener('mouseup', () => {
        const sIdx = Number(el.dataset.slide);
        const iIdx = Number(el.dataset.index);
        if (!slides[sIdx] || !slides[sIdx].textos || !slides[sIdx].textos[iIdx]) return;
        slides[sIdx].textos[iIdx].width = el.clientWidth;
        slides[sIdx].textos[iIdx].height = el.clientHeight;
    });

    canvas.appendChild(el);

    // Hacer arrastrable
    makeElementDraggable(el, slideIndex, index);
}

// Lógica de arrastre
function makeElementDraggable(elmnt, slideIndex, index) {
    let pos3 = 0, pos4 = 0;

    const elementDrag = (e) => {
        e = e || window.event;
        // usar clientX/clientY (soporta mouse)
        const clientX = e.clientX;
        const clientY = e.clientY;

        let newTop = elmnt.offsetTop + (clientY - pos4);
        let newLeft = elmnt.offsetLeft + (clientX - pos3);

        // actualizar referencias
        pos3 = clientX;
        pos4 = clientY;

        // límites dentro del canvas
        newTop = Math.max(0, Math.min(newTop, canvas.clientHeight - elmnt.offsetHeight));
        newLeft = Math.max(0, Math.min(newLeft, canvas.clientWidth - elmnt.offsetWidth));

        elmnt.style.top = newTop + "px";
        elmnt.style.left = newLeft + "px";

        // Guardar en slides
        if (slides[slideIndex] && slides[slideIndex].textos && slides[slideIndex].textos[index]) {
            slides[slideIndex].textos[index].top = newTop;
            slides[slideIndex].textos[index].left = newLeft;
        }
    };

    const closeDragElement = () => {
        document.removeEventListener('mousemove', elementDrag);
        document.removeEventListener('mouseup', closeDragElement);
        marcarCambios();
    };

    const dragMouseDown = (e) => {
        e = e || window.event;
        // iniciar posiciones
        pos3 = e.clientX;
        pos4 = e.clientY;
        document.addEventListener('mousemove', elementDrag);
        document.addEventListener('mouseup', closeDragElement);
        e.preventDefault();
    };

    // Iniciar el arrastre desde cualquier punto del textarea
    elmnt.addEventListener('mousedown', dragMouseDown);
}

// Agregar nuevo texto al slide actual
function addTextToCurrentSlide() {
    if (actual < 0) return;
    ensureTextArray(actual);

    const slideTexts = slides[actual].textos;
    const index = slideTexts.length;

    // crear objeto default
    const textoObj = {
        texto: 'Escribe tu texto...',
        top: 50,
        left: 50,
        width: 200,
        height: 100
    };

    // push al modelo
    slideTexts.push(textoObj);

    // renderizar (añade el nuevo)
    createDraggableTextElement(actual, index, textoObj);
    marcarCambios();
}

// Conectar botón de "Texto" (si existe)
if (addTextBtn) {
    addTextBtn.addEventListener('click', (e) => {
        e.preventDefault();
        addTextToCurrentSlide();
    });
}

// Al guardar JSON, ya estás serializando `slides`, así que no necesitas mover nada.
// Sin embargo, asegurémonos que antes de serializar el tamaño/posiciones estén actualizadas:
// (por si hay textareas visibles y el usuario no terminó una acción)
function syncVisibleTextsToModel() {
    const visibles = canvas.querySelectorAll('.draggable-text');
    visibles.forEach(el => {
        const sIdx = Number(el.dataset.slide);
        const iIdx = Number(el.dataset.index);
        if (!slides[sIdx] || !slides[sIdx].textos || !slides[sIdx].textos[iIdx]) return;
        slides[sIdx].textos[iIdx].texto = el.value;
        slides[sIdx].textos[iIdx].top = el.offsetTop;
        slides[sIdx].textos[iIdx].left = el.offsetLeft;
        slides[sIdx].textos[iIdx].width = el.clientWidth;
        slides[sIdx].textos[iIdx].height = el.clientHeight;
    });
}

// Interceptar guardarJSON para sincronizar antes
const originalGuardarJSON = guardarJSON;
guardarJSON = function() {
    syncVisibleTextsToModel();
    originalGuardarJSON();
};

// Hacer funciones disponibles globalmente
window.agregarDiapositiva = agregarDiapositiva;
window.duplicarSlide = duplicarSlide;
window.eliminarSlide = eliminarSlide;
window.reordenarSlides = reordenarSlides;
window.seleccionar = seleccionar;
window.actualizarSlide = actualizarSlide;
window.cambiarColor = cambiarColor;
window.cambiarAlineacion = cambiarAlineacion;
window.formatearTexto = formatearTexto;
window.slideAnterior = slideAnterior;
window.slideSiguiente = slideSiguiente;
window.cambiarZoom = cambiarZoom;
window.previsualizarPresentacion = previsualizarPresentacion;
window.cerrarModal = cerrarModal;
window.cambiarSlideModal = cambiarSlideModal;