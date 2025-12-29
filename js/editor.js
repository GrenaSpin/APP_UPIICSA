// ===== Editor de Presentación (Slides + Canvas de Textos) =====
// Modelo: slides = [ { titulo, contenido, color, size, alineacion, textos: [ {id, texto, top,left,width,height,fontSize,bold} ] } ]

let slides = [];
let actual = -1;
let zoomLevel = 1;

// Estado de selección de textbox
let selectedTextId = null;
let clipboardTextObj = null;

document.addEventListener("DOMContentLoaded", () => {
  const initial = document.getElementById("initial-data");
  if (initial && initial.textContent.trim()) {
    try { slides = JSON.parse(initial.textContent); } catch { slides = []; }
  }
  if (!Array.isArray(slides)) slides = [];

  // Normaliza slides
  slides = slides.map(s => normalizarSlide(s));

  if (slides.length === 0) slides.push(nuevoSlide());

  wireUI();
  cargarListaSlides();
  seleccionar(0);
  actualizarZoomUI();
});

function normalizarSlide(s) {
  return {
    titulo: s?.titulo ?? "Nuevo Slide",
    contenido: s?.contenido ?? "",
    color: s?.color ?? "#111827",
    size: s?.size ?? "34px",
    alineacion: s?.alineacion ?? "center",
    textos: Array.isArray(s?.textos) ? s.textos : []
  };
}

function wireUI() {
  // slide navigation
  document.getElementById("btn-prev")?.addEventListener("click", () => cambiarSlide(-1));
  document.getElementById("btn-next")?.addEventListener("click", () => cambiarSlide(1));
  document.getElementById("btn-add-slide")?.addEventListener("click", () => {
    slides.push(nuevoSlide());
    cargarListaSlides();
    seleccionar(slides.length - 1);
    marcarCambios();
  });
  document.getElementById("btn-delete-slide")?.addEventListener("click", () => {
    if (slides.length <= 1) return;
    slides.splice(actual, 1);
    const next = Math.min(actual, slides.length - 1);
    cargarListaSlides();
    seleccionar(next);
    marcarCambios();
  });

  // slide editing
  document.getElementById("slide-title")?.addEventListener("input", (e) => {
    if (actual < 0) return;
    slides[actual].titulo = e.target.value;
    document.querySelector(`#lista-slides [data-index="${actual}"] .thumb-title`)?.textContent =
      e.target.value || `Slide ${actual + 1}`;
    renderPreview();
    marcarCambios();
  });

  document.getElementById("slide-content")?.addEventListener("input", (e) => {
    if (actual < 0) return;
    slides[actual].contenido = e.target.value;
    renderPreview();
    marcarCambios();
  });

  // formatting for slide content textarea
  document.getElementById("tool-bold")?.addEventListener("click", () => formatearTexto("bold"));
  document.getElementById("tool-italic")?.addEventListener("click", () => formatearTexto("italic"));

  // slide style controls
  // preset background colors
  document.querySelectorAll("[data-bgcolor]").forEach(btn => {
    btn.addEventListener("click", () => {
      if (actual < 0) return;
      slides[actual].color = btn.getAttribute("data-bgcolor") || "#111827";
      renderPreview();
      marcarCambios();
      // UI active ring
      document.querySelectorAll("[data-bgcolor]").forEach(b => b.classList.remove("active"));
      btn.classList.add("active");
      const cc = document.getElementById("custom-color");
      if (cc) cc.value = slides[actual].color;
    });
  });

  // custom color picker
  document.getElementById("custom-color")?.addEventListener("input", (e) => {
    if (actual < 0) return;
    slides[actual].color = e.target.value;
    renderPreview();
    marcarCambios();
    document.querySelectorAll("[data-bgcolor]").forEach(b => b.classList.remove("active"));
  });

  // title size
  document.getElementById("slide-size")?.addEventListener("change", (e) => {
    if (actual < 0) return;
    slides[actual].size = e.target.value;
    renderPreview();
    marcarCambios();
  });

  // alignment
  document.getElementById("align-left")?.addEventListener("click", () => setAlign("left"));
  document.getElementById("align-center")?.addEventListener("click", () => setAlign("center"));
  document.getElementById("align-right")?.addEventListener("click", () => setAlign("right"));

  // zoom
  document.getElementById("zoom-in")?.addEventListener("click", () => setZoom(zoomLevel + 0.1));
  document.getElementById("zoom-out")?.addEventListener("click", () => setZoom(zoomLevel - 0.1));
  document.getElementById("zoom-reset")?.addEventListener("click", () => setZoom(1));

  // preview modal
  document.getElementById("full-preview")?.addEventListener("click", abrirModal);
  document.getElementById("modal-close")?.addEventListener("click", cerrarModal);
  document.getElementById("modal-prev")?.addEventListener("click", () => cambiarSlideModal(-1));
  document.getElementById("modal-next")?.addEventListener("click", () => cambiarSlideModal(1));

  // add text box (canvas)
  document.getElementById("add-text-btn")?.addEventListener("click", () => {
    if (actual < 0) return;
    const t = {
      id: "t_" + Math.random().toString(36).slice(2, 10),
      texto: "",
      top: 80,
      left: 80,
      width: 260,
      height: 140,
      fontSize: 18,
      bold: false
    };
    ensureTextArray(actual).push(t);
    renderSlideTexts();
    seleccionarText(t.id);
    // focus for typing
    const ta = document.querySelector(`.canvas-textbox[data-id="${t.id}"] textarea`);
    ta?.focus();
    marcarCambios();
  });

  // per textbox controls
  document.getElementById("text-font-size")?.addEventListener("input", (e) => {
    const obj = getSelectedTextObj();
    if (!obj) return;
    const v = parseInt(e.target.value, 10);
    if (!Number.isFinite(v) || v < 8 || v > 200) return;
    obj.fontSize = v;
    applyTextStyle(obj.id);
    marcarCambios();
  });

  document.getElementById("text-bold-btn")?.addEventListener("click", () => {
    const obj = getSelectedTextObj();
    if (!obj) return;
    obj.bold = !obj.bold;
    applyTextStyle(obj.id);
    syncTextControls();
    marcarCambios();
  });

  // delete / copy / paste / duplicate
  window.addEventListener("keydown", (e) => {
    const active = document.activeElement;
    const typingInTextarea = active && active.tagName === "TEXTAREA" && active.closest(".canvas-textbox");

    if ((e.key === "Backspace" || e.key === "Delete") && selectedTextId && !typingInTextarea) {
      e.preventDefault();
      eliminarSeleccionado();
      return;
    }
    if (e.ctrlKey && !e.shiftKey && e.key.toLowerCase() === "c" && selectedTextId && !typingInTextarea) {
      e.preventDefault();
      const obj = getSelectedTextObj();
      clipboardTextObj = obj ? structuredClone(obj) : null;
      return;
    }
    if (e.ctrlKey && !e.shiftKey && e.key.toLowerCase() === "v" && !typingInTextarea) {
      e.preventDefault();
      pegarTextbox();
      return;
    }
    if (e.ctrlKey && e.key.toLowerCase() === "d" && selectedTextId && !typingInTextarea) {
      e.preventDefault();
      duplicarSeleccionado();
      return;
    }
  });

  // form save hook
  document.getElementById("editor-form")?.addEventListener("submit", () => {
    guardarJSONEnHidden();
  });

  // click outside to clear textbox selection
  document.getElementById("preview")?.addEventListener("mousedown", (e) => {
    if (e.target.id === "preview" || e.target.id === "preview-content" || e.target.id === "preview-title") {
      clearTextSelection();
    }
  });

  // close modal clicking backdrop
  window.addEventListener("click", (e) => {
    const modal = document.getElementById("preview-modal");
    if (modal && e.target === modal) cerrarModal();
  });
}

function setAlign(al) {
  if (actual < 0) return;
  slides[actual].alineacion = al;
  document.querySelectorAll(".align-btn").forEach(b => b.classList.remove("active"));
  document.getElementById(`align-${al}`)?.classList.add("active");
  renderPreview();
  marcarCambios();
}

function nuevoSlide() {
  return { titulo: "Nuevo Slide", contenido: "", color: "#111827", size: "34px", alineacion: "center", textos: [] };
}

function cargarListaSlides() {
  const cont = document.getElementById("lista-slides");
  if (!cont) return;
  cont.innerHTML = "";

  slides.forEach((s, i) => {
    const item = document.createElement("div");
    item.className = "slide-thumb" + (i === actual ? " active" : "");
    item.dataset.index = String(i);

    item.innerHTML = `
      <div class="thumb-num">${i + 1}</div>
      <div class="thumb-title">${escapeHtml(s.titulo || `Slide ${i + 1}`)}</div>
    `;
    item.addEventListener("click", () => seleccionar(i));
    cont.appendChild(item);
  });

  actualizarContadores();
}

function seleccionar(i) {
  if (i < 0 || i >= slides.length) return;
  actual = i;

  // UI active thumb
  document.querySelectorAll("#lista-slides .slide-thumb").forEach(el => el.classList.remove("active"));
  document.querySelector(`#lista-slides .slide-thumb[data-index="${i}"]`)?.classList.add("active");

  // Fill editor inputs
  const s = slides[i];
  const title = document.getElementById("slide-title");
  const content = document.getElementById("slide-content");
  if (title) title.value = s.titulo ?? "";
  if (content) content.value = s.contenido ?? "";

  // slide style controls sync
  const cc = document.getElementById("custom-color");
  if (cc) cc.value = s.color || "#111827";
  const ss = document.getElementById("slide-size");
  if (ss) ss.value = s.size || "34px";
  document.querySelectorAll(".align-btn").forEach(b => b.classList.remove("active"));
  document.getElementById(`align-${s.alineacion || "center"}`)?.classList.add("active");
  document.querySelectorAll("[data-bgcolor]").forEach(b => {
    b.classList.toggle("active", (b.getAttribute("data-bgcolor") || "").toLowerCase() === (s.color || "").toLowerCase());
  });

  // Update indicator
  const ind = document.getElementById("current-slide-indicator");
  if (ind) ind.textContent = `Slide ${i + 1}`;

  clearTextSelection();
  renderPreview();
  renderSlideTexts();
  actualizarContadores();
}

function cambiarSlide(delta) {
  const next = actual + delta;
  if (next < 0 || next >= slides.length) return;
  seleccionar(next);
}

function actualizarContadores() {
  const count = document.getElementById("slide-count");
  const pos = document.getElementById("slide-position");
  if (count) count.textContent = `${slides.length}`;
  if (pos) pos.textContent = `${Math.max(actual + 1, 1)}`;
}

function renderPreview() {
  const canvas = document.getElementById("preview");
  const titleEl = document.getElementById("preview-title");
  const contentEl = document.getElementById("preview-content");
  if (!canvas || actual < 0) return;
  const s = slides[actual];

  // background
  canvas.style.background = s.color || "#111827";

  if (titleEl) {
    titleEl.textContent = s.titulo || "";
    titleEl.style.fontSize = s.size || "34px";
    titleEl.style.textAlign = s.alineacion || "center";
  }
  if (contentEl) {
    contentEl.innerHTML = procesarContenido(s.contenido || "");
    contentEl.style.textAlign = s.alineacion || "center";
  }
}

function procesarContenido(text) {
  // Simple escape + newline -> <br>. (Mantén tu markdown si quieres luego.)
  return escapeHtml(text).replace(/\n/g, "<br>");
}

function escapeHtml(str) {
  return String(str ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

// ======= Formateo del textarea principal del slide =======
function formatearTexto(tipo) {
  const textarea = document.getElementById("slide-content");
  if (!textarea) return;

  const start = textarea.selectionStart;
  const end = textarea.selectionEnd;
  const selected = textarea.value.substring(start, end);

  let insert = "";
  if (tipo === "bold") insert = `**${selected}**`;
  if (tipo === "italic") insert = `_${selected}_`;

  textarea.setRangeText(insert, start, end, "end");
  textarea.dispatchEvent(new Event("input"));
  textarea.focus();
}

// ===== Zoom =====
function setZoom(val) {
  zoomLevel = Math.max(0.3, Math.min(2.5, Math.round(val * 10) / 10));
  const canvas = document.getElementById("preview");
  if (canvas) {
    canvas.style.transformOrigin = "top left";
    canvas.style.transform = `scale(${zoomLevel})`;
  }
  actualizarZoomUI();
}

function actualizarZoomUI() {
  const z = document.getElementById("zoom-level");
  if (z) z.textContent = `${Math.round(zoomLevel * 100)}%`;
}

// ===== Modal =====
let currentModalSlide = 0;

function abrirModal() {
  const modal = document.getElementById("preview-modal");
  if (!modal) return;
  currentModalSlide = Math.max(actual, 0);
  modal.style.display = "flex";
  renderModalSlide();
}

function cerrarModal() {
  const modal = document.getElementById("preview-modal");
  if (modal) modal.style.display = "none";
}

function cambiarSlideModal(delta) {
  const next = currentModalSlide + delta;
  if (next < 0 || next >= slides.length) return;
  currentModalSlide = next;
  renderModalSlide();
}

function renderModalSlide() {
  const modalTitle = document.getElementById("modal-slide-title");
  const modalBody = document.getElementById("modal-slide-body");
  const pos = document.getElementById("modal-slide-position");
  if (!modalBody || !slides[currentModalSlide]) return;

  const s = slides[currentModalSlide];
  if (modalTitle) modalTitle.textContent = s.titulo || "";
  modalBody.style.background = s.color || "#111827";
  modalBody.innerHTML = `
    <div class="modal-inner">
      <h2 style="margin:0 0 12px; font-size:${escapeHtml(s.size || "34px")}; text-align:${escapeHtml(s.alineacion || "center")};">${escapeHtml(s.titulo || "")}</h2>
      <div class="modal-content" style="text-align:${escapeHtml(s.alineacion || "center")};">${procesarContenido(s.contenido || "")}</div>
    </div>
  `;

  // Render textboxes into modal (read-only)
  if (Array.isArray(s.textos)) {
    s.textos.forEach(t => {
      const el = document.createElement("div");
      el.className = "modal-textbox";
      el.style.top = `${t.top}px`;
      el.style.left = `${t.left}px`;
      el.style.width = `${t.width}px`;
      el.style.height = `${t.height}px`;
      el.style.fontSize = `${t.fontSize || 18}px`;
      el.style.fontWeight = t.bold ? "700" : "400";
      el.textContent = t.texto || "";
      modalBody.appendChild(el);
    });
  }

  if (pos) pos.textContent = `Slide ${currentModalSlide + 1} de ${slides.length}`;
}

// ===== Guardado =====
function guardarJSONEnHidden() {
  const dataInput = document.getElementById("data");
  if (dataInput) dataInput.value = JSON.stringify(slides);
  const status = document.getElementById("save-status");
  if (status) status.textContent = "Guardado";
}

function marcarCambios() {
  const status = document.getElementById("save-status");
  if (status) status.textContent = "Cambios sin guardar";
}

// ===== Canvas de textos (draggable + resize + delete + copy/paste) =====
function ensureTextArray(slideIndex) {
  if (!slides[slideIndex]) return [];
  if (!Array.isArray(slides[slideIndex].textos)) slides[slideIndex].textos = [];
  return slides[slideIndex].textos;
}

function renderSlideTexts() {
  const canvas = document.getElementById("preview");
  if (!canvas || actual < 0) return;

  // limpia existentes
  canvas.querySelectorAll(".canvas-textbox").forEach(el => el.remove());

  const list = ensureTextArray(actual);
  list.forEach(obj => {
    // normaliza textbox por si viene viejo
    if (!obj.id) obj.id = "t_" + Math.random().toString(36).slice(2, 10);
    if (typeof obj.top !== "number") obj.top = 80;
    if (typeof obj.left !== "number") obj.left = 80;
    if (typeof obj.width !== "number") obj.width = 260;
    if (typeof obj.height !== "number") obj.height = 140;
    if (typeof obj.fontSize !== "number") obj.fontSize = 18;
    if (typeof obj.bold !== "boolean") obj.bold = false;
    if (typeof obj.texto !== "string") obj.texto = "";
    createTextboxElement(obj);
  });

  syncTextControls();
}

function createTextboxElement(obj) {
  const canvas = document.getElementById("preview");
  if (!canvas) return;

  const box = document.createElement("div");
  box.className = "canvas-textbox";
  box.dataset.id = obj.id;

  box.style.top = `${obj.top}px`;
  box.style.left = `${obj.left}px`;
  box.style.width = `${obj.width}px`;
  box.style.height = `${obj.height}px`;

  // Handle superior (para arrastrar)
  const handle = document.createElement("div");
  handle.className = "tb-handle";
  handle.innerHTML = `<span>Texto</span><button class="tb-x" title="Eliminar">×</button>`;
  box.appendChild(handle);

  // textarea (editable)
  const ta = document.createElement("textarea");
  ta.className = "tb-textarea";
  ta.value = obj.texto ?? "";
  box.appendChild(ta);

  // resize handle
  const rh = document.createElement("div");
  rh.className = "tb-resize";
  box.appendChild(rh);

  canvas.appendChild(box);
  applyTextStyle(obj.id);

  // select
  box.addEventListener("mousedown", (e) => {
    if (e.target.classList.contains("tb-resize")) return;
    seleccionarText(obj.id);
  });

  // click derecho: eliminar
  box.addEventListener("contextmenu", (e) => {
    e.preventDefault();
    seleccionarText(obj.id);
    eliminarSeleccionado();
  });

  // eliminar con X
  handle.querySelector(".tb-x")?.addEventListener("click", (e) => {
    e.stopPropagation();
    seleccionarText(obj.id);
    eliminarSeleccionado();
  });

  // escribir: actualizar modelo
  ta.addEventListener("input", () => {
    const model = getTextObjById(obj.id);
    if (!model) return;
    model.texto = ta.value;
    marcarCambios();
  });

  // Drag: SOLO desde handle
  handle.addEventListener("mousedown", (e) => {
    if (e.target.classList.contains("tb-x")) return;
    e.preventDefault();
    seleccionarText(obj.id);
    startDrag(box, obj.id, e);
  });

  // Resize
  rh.addEventListener("mousedown", (e) => {
    e.preventDefault();
    e.stopPropagation();
    seleccionarText(obj.id);
    startResize(box, obj.id, e);
  });
}

function seleccionarText(id) {
  selectedTextId = id;
  document.querySelectorAll(".canvas-textbox").forEach(el => el.classList.toggle("selected", el.dataset.id === id));
  syncTextControls();
}

function clearTextSelection() {
  selectedTextId = null;
  document.querySelectorAll(".canvas-textbox").forEach(el => el.classList.remove("selected"));
  syncTextControls();
}

function getTextObjById(id) {
  if (actual < 0) return null;
  return ensureTextArray(actual).find(t => t.id === id) || null;
}
function getSelectedTextObj() {
  return selectedTextId ? getTextObjById(selectedTextId) : null;
}

function syncTextControls() {
  const panel = document.getElementById("text-controls");
  const fs = document.getElementById("text-font-size");
  const bb = document.getElementById("text-bold-btn");
  const obj = getSelectedTextObj();

  if (panel) panel.style.display = obj ? "block" : "none";
  if (fs) fs.value = obj ? String(obj.fontSize || 18) : "18";
  if (bb) bb.classList.toggle("active", !!obj?.bold);
}

function applyTextStyle(id) {
  const obj = getTextObjById(id);
  const el = document.querySelector(`.canvas-textbox[data-id="${id}"] textarea`);
  if (!obj || !el) return;
  el.style.fontSize = `${obj.fontSize || 18}px`;
  el.style.fontWeight = obj.bold ? "700" : "400";
}

function eliminarSeleccionado() {
  if (!selectedTextId || actual < 0) return;
  const arr = ensureTextArray(actual);
  const idx = arr.findIndex(t => t.id === selectedTextId);
  if (idx >= 0) arr.splice(idx, 1);
  selectedTextId = null;
  renderSlideTexts();
  marcarCambios();
}

function duplicarSeleccionado() {
  const obj = getSelectedTextObj();
  if (!obj) return;
  const copy = structuredClone(obj);
  copy.id = "t_" + Math.random().toString(36).slice(2, 10);
  copy.top += 20;
  copy.left += 20;
  ensureTextArray(actual).push(copy);
  renderSlideTexts();
  seleccionarText(copy.id);
  marcarCambios();
}

function pegarTextbox() {
  if (!clipboardTextObj || actual < 0) return;
  const copy = structuredClone(clipboardTextObj);
  copy.id = "t_" + Math.random().toString(36).slice(2, 10);
  copy.top = (copy.top ?? 80) + 20;
  copy.left = (copy.left ?? 80) + 20;
  ensureTextArray(actual).push(copy);
  renderSlideTexts();
  seleccionarText(copy.id);
  marcarCambios();
}

function startDrag(box, id, e) {
  const obj = getTextObjById(id);
  if (!obj) return;

  const startX = e.clientX;
  const startY = e.clientY;
  const origLeft = obj.left;
  const origTop = obj.top;

  function onMove(ev) {
    const dx = (ev.clientX - startX) / zoomLevel;
    const dy = (ev.clientY - startY) / zoomLevel;

    obj.left = Math.max(0, Math.round(origLeft + dx));
    obj.top = Math.max(0, Math.round(origTop + dy));

    box.style.left = `${obj.left}px`;
    box.style.top = `${obj.top}px`;
  }
  function onUp() {
    window.removeEventListener("mousemove", onMove);
    window.removeEventListener("mouseup", onUp);
    marcarCambios();
  }
  window.addEventListener("mousemove", onMove);
  window.addEventListener("mouseup", onUp);
}

function startResize(box, id, e) {
  const obj = getTextObjById(id);
  if (!obj) return;

  const startX = e.clientX;
  const startY = e.clientY;
  const origW = obj.width;
  const origH = obj.height;

  function onMove(ev) {
    const dx = (ev.clientX - startX) / zoomLevel;
    const dy = (ev.clientY - startY) / zoomLevel;

    obj.width = Math.max(120, Math.round(origW + dx));
    obj.height = Math.max(60, Math.round(origH + dy));

    box.style.width = `${obj.width}px`;
    box.style.height = `${obj.height}px`;
  }
  function onUp() {
    window.removeEventListener("mousemove", onMove);
    window.removeEventListener("mouseup", onUp);
    marcarCambios();
  }
  window.addEventListener("mousemove", onMove);
  window.addEventListener("mouseup", onUp);
}