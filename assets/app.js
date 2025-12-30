// assets/app.js

const $ = (s) => document.querySelector(s);
const slidesListEl = $("#slides-list");
const slideEl = $("#slide");
const statusEl = $("#status");

const btnNewSlide = $("#btn-new-slide");
const btnAddText = $("#btn-add-text");
const btnAddRect = $("#btn-add-rect");
const btnAddImage = $("#btn-add-image");
const imagePicker = $("#image-picker");

const btnSave = $("#btn-save");
const btnLoad = $("#btn-load");

const slideTitleInput = $("#slide-title");
const slideBgInput = $("#slide-bg");

const selNone = $("#sel-none");
const selPropsWrap = $("#sel-props");
const propX = $("#prop-x");
const propY = $("#prop-y");
const propW = $("#prop-w");
const propH = $("#prop-h");
const propColor = $("#prop-color");
const propText = $("#prop-text");
const propSrc = $("#prop-src");
const btnDelete = $("#btn-delete");

const SLIDE_W = 960;
const SLIDE_H = 540;

let state = {
  slides: [],
  activeSlideId: null,
  selectedElId: null,
  dirty: false,
};

function uid(prefix="id"){
  return prefix + "_" + Math.random().toString(16).slice(2) + Date.now().toString(16);
}

function setStatus(msg){
  statusEl.textContent = msg || "";
}

function markDirty(on=true){
  state.dirty = on;
  setStatus(on ? "Cambios sin guardar" : "Guardado / Sin cambios");
}

function defaultSlide(){
  return {
    id: uid("slide"),
    title: "Slide 1",
    bg: "#ffffff",
    elements: []
  };
}

function defaultTextEl(){
  return {
    id: uid("el"),
    type: "text",
    x: 80, y: 80, w: 260, h: 70,
    color: "#111111",
    text: "Doble click para editar"
  };
}

function defaultRectEl(){
  return {
    id: uid("el"),
    type: "rect",
    x: 120, y: 140, w: 260, h: 160,
    color: "#6ea8fe",
    text: ""
  };
}

function defaultImageEl(src){
  return {
    id: uid("el"),
    type: "image",
    x: 140, y: 90, w: 320, h: 200,
    color: "#000000",
    text: "",
    src
  };
}

function activeSlide(){
  return state.slides.find(s => s.id === state.activeSlideId) || null;
}

function activeEl(){
  const s = activeSlide();
  if(!s) return null;
  return s.elements.find(e => e.id === state.selectedElId) || null;
}

/* ---------- Rendering ---------- */

function renderSlidesList(){
  slidesListEl.innerHTML = "";
  state.slides.forEach((s, idx) => {
    const item = document.createElement("div");
    item.className = "slide-item" + (s.id === state.activeSlideId ? " active" : "");
    item.dataset.id = s.id;

    const thumb = document.createElement("div");
    thumb.className = "thumb";
    thumb.style.background = s.bg || "#ffffff";

    const meta = document.createElement("div");
    meta.className = "meta";
    const t = document.createElement("div");
    t.className = "t";
    t.textContent = s.title || `Slide ${idx+1}`;
    const sub = document.createElement("div");
    sub.className = "s";
    sub.textContent = `${s.elements.length} elemento(s)`;
    meta.appendChild(t);
    meta.appendChild(sub);

    item.appendChild(thumb);
    item.appendChild(meta);

    item.addEventListener("click", () => selectSlide(s.id));

    slidesListEl.appendChild(item);
  });
}

function renderSlide(){
  const s = activeSlide();
  if(!s){
    slideEl.innerHTML = "";
    slideEl.style.background = "#fff";
    return;
  }

  slideTitleInput.value = s.title || "";
  slideBgInput.value = s.bg || "#ffffff";
  slideEl.style.background = s.bg || "#ffffff";

  slideEl.innerHTML = "";
  s.elements.forEach(el => {
    const node = document.createElement("div");
    node.className = `el ${el.type}` + (el.id === state.selectedElId ? " selected" : "");
    node.dataset.id = el.id;
    applyRect(node, el);

    if(el.type === "text"){
      node.textContent = el.text || "";
      node.style.color = el.color || "#111111";
    } else if(el.type === "rect"){
      node.style.background = hexToRgba(el.color || "#6ea8fe", 0.28);
    } else if(el.type === "image"){
      const img = document.createElement("img");
      img.src = el.src || "";
      img.alt = "Imagen";
      node.appendChild(img);
    }

    // select + drag (pero NO si es handle)
    node.addEventListener("mousedown", (ev) => {
      const isHandle = ev.target && ev.target.classList && ev.target.classList.contains("handle");
      if(isHandle) return;
      ev.stopPropagation();
      selectElement(el.id);
      startDrag(ev, node);
    });

    // edit text
    if(el.type === "text"){
      node.addEventListener("dblclick", (ev) => {
        ev.stopPropagation();
        enableInlineEdit(node);
      });
    }

    // handles (solo si seleccionado)
    if(el.id === state.selectedElId){
      addResizeHandles(node);
    }

    slideEl.appendChild(node);
  });

  renderSelectionPanel();
}

function applyRect(node, el){
  node.style.left = el.x + "px";
  node.style.top = el.y + "px";
  node.style.width = el.w + "px";
  node.style.height = el.h + "px";
}

function addResizeHandles(node){
  const positions = ["nw","n","ne","e","se","s","sw","w"];
  positions.forEach(pos => {
    const h = document.createElement("div");
    h.className = `handle ${pos}`;
    h.dataset.handle = pos;
    h.addEventListener("mousedown", (ev) => {
      ev.stopPropagation();
      startResize(ev, node, pos);
    });
    node.appendChild(h);
  });
}

function renderSelectionPanel(){
  const el = activeEl();
  if(!el){
    selNone.classList.remove("hidden");
    selPropsWrap.classList.add("hidden");
    return;
  }

  selNone.classList.add("hidden");
  selPropsWrap.classList.remove("hidden");

  propX.value = el.x;
  propY.value = el.y;
  propW.value = el.w;
  propH.value = el.h;

  propColor.value = (el.color || "#111111");
  propText.value = (el.type === "text" ? (el.text || "") : "");
  propText.disabled = (el.type !== "text");

  propSrc.value = (el.type === "image" ? (el.src || "") : "");
}

/* ---------- Selection ---------- */

function selectSlide(slideId){
  state.activeSlideId = slideId;
  state.selectedElId = null;
  renderSlidesList();
  renderSlide();
}

function selectElement(elId){
  state.selectedElId = elId;
  renderSlide();
}

/* ---------- Interactions ---------- */

slideEl.addEventListener("mousedown", () => {
  state.selectedElId = null;
  renderSlide();
});

btnNewSlide.addEventListener("click", () => {
  const s = defaultSlide();
  s.title = `Slide ${state.slides.length + 1}`;
  state.slides.push(s);
  selectSlide(s.id);
  markDirty(true);
});

btnAddText.addEventListener("click", () => {
  const s = activeSlide();
  if(!s) return;
  const el = defaultTextEl();
  s.elements.push(el);
  state.selectedElId = el.id;
  renderSlidesList();
  renderSlide();
  markDirty(true);
});

btnAddRect.addEventListener("click", () => {
  const s = activeSlide();
  if(!s) return;
  const el = defaultRectEl();
  s.elements.push(el);
  state.selectedElId = el.id;
  renderSlidesList();
  renderSlide();
  markDirty(true);
});

// ---- IMÁGENES (upload + insert) ----
btnAddImage.addEventListener("click", () => {
  const s = activeSlide();
  if(!s) return;
  imagePicker.value = "";
  imagePicker.click();
});

imagePicker.addEventListener("change", async () => {
  const s = activeSlide();
  if(!s) return;
  const file = imagePicker.files && imagePicker.files[0];
  if(!file) return;

  try{
    setStatus("Subiendo imagen...");
    const fd = new FormData();
    fd.append("image", file);

    const res = await fetch("api.php?action=upload_image", { method:"POST", body: fd });
    const data = await res.json();
    if(!data.ok) throw new Error(data.error || "No se pudo subir");

    const el = defaultImageEl(data.url);
    s.elements.push(el);
    state.selectedElId = el.id;
    renderSlidesList();
    renderSlide();
    markDirty(true);
    setStatus("Imagen insertada");
  }catch(err){
    setStatus("Error: " + err.message);
  }
});

// Slide props
slideTitleInput.addEventListener("input", (e) => {
  const s = activeSlide();
  if(!s) return;
  s.title = e.target.value;
  renderSlidesList();
  markDirty(true);
});

slideBgInput.addEventListener("input", (e) => {
  const s = activeSlide();
  if(!s) return;
  s.bg = e.target.value;
  slideEl.style.background = s.bg;
  renderSlidesList();
  markDirty(true);
});

// Element props
[propX, propY, propW, propH].forEach(inp => {
  inp.addEventListener("input", () => {
    const el = activeEl();
    if(!el) return;

    const min = getMinSize(el);

    el.x = clampInt(propX.value, 0, SLIDE_W - min.w);
    el.y = clampInt(propY.value, 0, SLIDE_H - min.h);
    el.w = clampInt(propW.value, min.w, SLIDE_W);
    el.h = clampInt(propH.value, min.h, SLIDE_H);

    // keep within bounds after w/h change
    el.x = clampInt(el.x, 0, SLIDE_W - el.w);
    el.y = clampInt(el.y, 0, SLIDE_H - el.h);

    renderSlide();
    markDirty(true);
  });
});

propColor.addEventListener("input", () => {
  const el = activeEl();
  if(!el) return;
  el.color = propColor.value;
  renderSlide();
  markDirty(true);
});

propText.addEventListener("input", () => {
  const el = activeEl();
  if(!el || el.type !== "text") return;
  el.text = propText.value;
  renderSlide();
  markDirty(true);
});

btnDelete.addEventListener("click", () => deleteSelected());

document.addEventListener("keydown", (e) => {
  if(e.key === "Delete" || e.key === "Backspace"){
    const t = e.target;
    const isEditing = t && (t.tagName === "INPUT" || t.tagName === "TEXTAREA" || t.isContentEditable);
    if(!isEditing) deleteSelected();
  }
});

function deleteSelected(){
  const s = activeSlide();
  if(!s || !state.selectedElId) return;
  s.elements = s.elements.filter(x => x.id !== state.selectedElId);
  state.selectedElId = null;
  renderSlidesList();
  renderSlide();
  markDirty(true);
}

/* ---------- Dragging ---------- */

function startDrag(ev, node){
  const s = activeSlide();
  if(!s) return;

  const id = node.dataset.id;
  const el = s.elements.find(x => x.id === id);
  if(!el) return;

  const startX = ev.clientX;
  const startY = ev.clientY;
  const origX = el.x;
  const origY = el.y;

  function onMove(e){
    const dx = e.clientX - startX;
    const dy = e.clientY - startY;

    el.x = clampInt(origX + dx, 0, Math.max(0, SLIDE_W - el.w));
    el.y = clampInt(origY + dy, 0, Math.max(0, SLIDE_H - el.h));

    node.style.left = el.x + "px";
    node.style.top = el.y + "px";

    if(state.selectedElId === el.id){
      propX.value = el.x;
      propY.value = el.y;
    }
  }

  function onUp(){
    document.removeEventListener("mousemove", onMove);
    document.removeEventListener("mouseup", onUp);
    markDirty(true);
  }

  document.addEventListener("mousemove", onMove);
  document.addEventListener("mouseup", onUp);
}

/* ---------- Resize ---------- */

function startResize(ev, node, handle){
  const s = activeSlide();
  if(!s) return;

  const id = node.dataset.id;
  const el = s.elements.find(x => x.id === id);
  if(!el) return;

  const startX = ev.clientX;
  const startY = ev.clientY;

  const o = { x: el.x, y: el.y, w: el.w, h: el.h };
  const min = getMinSize(el);

  function onMove(e){
    const dx = e.clientX - startX;
    const dy = e.clientY - startY;

    let nx = o.x, ny = o.y, nw = o.w, nh = o.h;

    const hasN = handle.includes("n");
    const hasS = handle.includes("s");
    const hasW = handle.includes("w");
    const hasE = handle.includes("e");

    if(hasE) nw = o.w + dx;
    if(hasS) nh = o.h + dy;

    if(hasW){
      nw = o.w - dx;
      nx = o.x + dx;
    }
    if(hasN){
      nh = o.h - dy;
      ny = o.y + dy;
    }

    // clamp sizes
    nw = Math.max(min.w, nw);
    nh = Math.max(min.h, nh);

    // keep within bounds (adjust x/y if resizing from west/north)
    nx = clampInt(nx, 0, SLIDE_W - nw);
    ny = clampInt(ny, 0, SLIDE_H - nh);

    // additional clamp for east/south edges
    if(nx + nw > SLIDE_W) nw = SLIDE_W - nx;
    if(ny + nh > SLIDE_H) nh = SLIDE_H - ny;

    // apply
    el.x = Math.round(nx);
    el.y = Math.round(ny);
    el.w = Math.round(nw);
    el.h = Math.round(nh);

    applyRect(node, el);

    // sync panel live
    propX.value = el.x;
    propY.value = el.y;
    propW.value = el.w;
    propH.value = el.h;
  }

  function onUp(){
    document.removeEventListener("mousemove", onMove);
    document.removeEventListener("mouseup", onUp);
    markDirty(true);
    renderSlide(); // para re-colocar handles bien
  }

  document.addEventListener("mousemove", onMove);
  document.addEventListener("mouseup", onUp);
}

function getMinSize(el){
  if(el.type === "text") return { w: 60, h: 30 };
  if(el.type === "image") return { w: 80, h: 60 };
  return { w: 40, h: 40 };
}

/* ---------- Inline text edit ---------- */

function enableInlineEdit(node){
  const s = activeSlide();
  if(!s) return;
  const id = node.dataset.id;
  const el = s.elements.find(x => x.id === id);
  if(!el || el.type !== "text") return;

  node.contentEditable = "true";
  node.style.cursor = "text";
  node.focus();

  const range = document.createRange();
  range.selectNodeContents(node);
  range.collapse(false);
  const sel = window.getSelection();
  sel.removeAllRanges();
  sel.addRange(range);

  function finish(save=true){
    node.contentEditable = "false";
    node.style.cursor = "grab";
    if(save){
      el.text = node.textContent || "";
    }else{
      node.textContent = el.text || "";
    }
    propText.value = el.text || "";
    markDirty(true);
    node.removeEventListener("blur", onBlur);
    node.removeEventListener("keydown", onKey);
  }

  function onBlur(){ finish(true); }
  function onKey(e){
    if(e.key === "Enter"){
      e.preventDefault();
      finish(true);
    }
    if(e.key === "Escape"){
      e.preventDefault();
      finish(false);
    }
  }

  node.addEventListener("blur", onBlur);
  node.addEventListener("keydown", onKey);
}

/* ---------- Save / Load ---------- */

btnSave.addEventListener("click", async () => {
  try{
    setStatus("Guardando...");
    const res = await fetch("api.php?action=save", {
      method: "POST",
      headers: {"Content-Type":"application/json"},
      body: JSON.stringify({ slides: state.slides })
    });
    const data = await res.json();
    if(!data.ok) throw new Error(data.error || "Error guardando");
    markDirty(false);
  }catch(err){
    setStatus("Error: " + err.message);
  }
});

btnLoad.addEventListener("click", async () => {
  try{
    setStatus("Cargando...");
    const res = await fetch("api.php?action=load");
    const data = await res.json();
    if(!data.ok) throw new Error(data.error || "Error cargando");
    state.slides = data.slides || [];
    if(state.slides.length === 0) state.slides = [defaultSlide()];
    state.activeSlideId = state.slides[0].id;
    state.selectedElId = null;
    renderSlidesList();
    renderSlide();
    markDirty(false);
    setStatus("Cargado");
  }catch(err){
    setStatus("Error: " + err.message);
  }
});

/* ---------- Utils ---------- */

function clampInt(v, min, max){
  const n = Math.round(Number(v) || 0);
  return Math.max(min, Math.min(max, n));
}

function hexToRgba(hex, a=1){
  const h = (hex || "#000000").replace("#","");
  const full = h.length === 3 ? h.split("").map(c=>c+c).join("") : h.padEnd(6,"0");
  const r = parseInt(full.slice(0,2),16);
  const g = parseInt(full.slice(2,4),16);
  const b = parseInt(full.slice(4,6),16);
  return `rgba(${r},${g},${b},${a})`;
}

/* ---------- Init ---------- */

(function init(){
  state.slides = [defaultSlide()];
  state.activeSlideId = state.slides[0].id;
  renderSlidesList();
  renderSlide();
  setStatus("Listo");
})();
