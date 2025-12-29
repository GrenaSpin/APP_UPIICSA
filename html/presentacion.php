<?php
// presentacion.php — visor de presentación (con textos flotantes)
// Seguridad básica: sanitizar salida
function esc($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$id = $_GET["id"] ?? "";
$baseDir = __DIR__ . "/presentaciones/";
$real = realpath($baseDir . $id);

// Evita path traversal
if (!$id || !$real || strpos($real, realpath($baseDir)) !== 0 || !is_file($real)) {
    http_response_code(404);
    echo "Presentación no encontrada.";
    exit;
}

$data = json_decode(file_get_contents($real), true);
if (!is_array($data)) {
    http_response_code(500);
    echo "JSON inválido.";
    exit;
}

$diapositivas = $data["diapositivas"] ?? [];
if (!is_array($diapositivas)) $diapositivas = [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?= esc($data["titulo"] ?? "Presentación") ?></title>

<style>
    :root{
        --bg: #0b1220;
        --panel: #111827;
        --stroke: rgba(255,255,255,.12);
    }
    body{
        margin:0;
        background:#0b0f16;
        color:#fff;
        font-family: Arial, system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
        text-align:center;
        padding:28px 16px 48px;
    }
    h1{ margin: 0 0 18px; font-size: 26px; font-weight: 700; opacity:.95; }
    .stage{
        display:flex;
        justify-content:center;
        align-items:center;
    }
    .slide{
        display:none;
        position:relative;
        width:min(960px, 92vw);
        aspect-ratio: 16 / 9;
        background: var(--panel);
        border: 1px solid var(--stroke);
        border-radius: 16px;
        overflow:hidden;
        box-shadow: 0 18px 60px rgba(0,0,0,.45);
    }
    .slide.active{ display:block; }
    .slide-inner{
        position:absolute;
        inset:0;
        padding: 42px 54px;
        box-sizing:border-box;
    }
    .slide-title{
        margin:0 0 14px;
        line-height:1.05;
        font-weight:800;
        letter-spacing:-0.02em;
        text-shadow: 0 2px 0 rgba(0,0,0,.15);
        word-break: break-word;
    }
    .slide-content{
        margin:0;
        font-size: 24px;
        line-height:1.35;
        opacity:.96;
        white-space: pre-wrap;
        word-break: break-word;
    }
    /* textos flotantes (del "canvas") */
    .float-text{
        position:absolute;
        box-sizing:border-box;
        padding: 10px 12px;
        border-radius: 10px;
        background: rgba(0,0,0,.18);
        border: 1px solid rgba(255,255,255,.10);
        backdrop-filter: blur(2px);
        color: #fff;
        text-align:left;
        overflow:hidden;
        white-space: pre-wrap;
        word-break: break-word;
        pointer-events:none; /* visor: no interactivo */
    }

    .controls{
        margin-top:16px;
        display:flex;
        justify-content:center;
        gap:12px;
        flex-wrap:wrap;
        align-items:center;
    }
    .btn{
        cursor:pointer;
        border: 1px solid rgba(255,255,255,.18);
        background: rgba(255,255,255,.06);
        color:#fff;
        padding:10px 14px;
        border-radius: 12px;
        font-weight:700;
        transition: transform .06s ease, background .15s ease;
        user-select:none;
    }
    .btn:active{ transform: translateY(1px); }
    .pill{
        opacity:.85;
        font-weight:700;
        font-size: 13px;
        padding: 8px 10px;
        border-radius: 999px;
        border: 1px solid rgba(255,255,255,.12);
        background: rgba(255,255,255,.04);
    }
    .hint{
        margin-top:10px;
        font-size: 12px;
        opacity:.65;
    }
</style>
</head>
<body>

<h1><?= esc($data["titulo"] ?? "") ?></h1>

<div class="stage">
<?php foreach ($diapositivas as $i => $d): 
    $bg = $d["color"] ?? "#111827";
    $align = $d["alineacion"] ?? "center";
    $size = $d["size"] ?? "40px";
    $title = $d["titulo"] ?? "";
    $contenido = $d["contenido"] ?? "";
    $textos = $d["textos"] ?? [];
    if (!is_array($textos)) $textos = [];
?>
    <div class="slide<?= $i === 0 ? " active" : "" ?>" style="background: <?= esc($bg) ?>; text-align: <?= esc($align) ?>;">
        <div class="slide-inner">
            <h2 class="slide-title" style="font-size: <?= esc($size) ?>;"><?= esc($title) ?></h2>
            <p class="slide-content"><?= esc($contenido) ?></p>
        </div>

        <?php foreach ($textos as $t):
            if (!is_array($t)) continue;
            $left = (float)($t["left"] ?? 0);
            $top  = (float)($t["top"] ?? 0);
            $w    = (float)($t["width"] ?? 220);
            $h    = (float)($t["height"] ?? 120);
            $fs   = (int)($t["fontSize"] ?? 18);
            $bold = !empty($t["bold"]);
            $txt  = $t["texto"] ?? "";
            $colorText = $t["color"] ?? "#ffffff";
            $tAlign = $t["align"] ?? "left";
        ?>
            <div class="float-text"
                 style="
                    left: <?= esc($left) ?>px;
                    top: <?= esc($top) ?>px;
                    width: <?= esc($w) ?>px;
                    height: <?= esc($h) ?>px;
                    font-size: <?= esc($fs) ?>px;
                    font-weight: <?= $bold ? "800" : "400" ?>;
                    color: <?= esc($colorText) ?>;
                    text-align: <?= esc($tAlign) ?>;
                 "><?= esc($txt) ?></div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
</div>

<div class="controls">
    <div class="btn" onclick="prev()">◀ Anterior</div>
    <div class="pill" id="counter"></div>
    <div class="btn" onclick="next()">Siguiente ▶</div>
</div>
<div class="hint">Atajos: ← → (flechas) · Espacio (siguiente) · Backspace (anterior)</div>

<script>
    const slides = Array.from(document.querySelectorAll('.slide'));
    let idx = 0;

    function updateCounter(){
        const el = document.getElementById('counter');
        if (!el) return;
        el.textContent = `${idx+1} / ${slides.length}`;
    }

    function show(i){
        if (!slides.length) return;
        idx = (i + slides.length) % slides.length;
        slides.forEach((s, k) => s.classList.toggle('active', k === idx));
        updateCounter();
    }

    function next(){ show(idx + 1); }
    function prev(){ show(idx - 1); }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowRight') next();
        else if (e.key === 'ArrowLeft') prev();
        else if (e.key === ' '){ e.preventDefault(); next(); }
        else if (e.key === 'Backspace'){ e.preventDefault(); prev(); }
    });

    updateCounter();
</script>

</body>
</html>
