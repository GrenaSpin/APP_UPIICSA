<?php
$id = $_GET["id"];
$data = json_decode(file_get_contents("presentaciones/$id"), true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?= $data["titulo"] ?></title>

<style>
    body { background: #111; color: #fff; font-family: Arial; text-align: center; padding: 40px; }
    .slide { display: none; }
    .active { display: block; }
    h2 { font-size: 40px; }
    p { font-size: 24px; }
</style>

<script>
let index = 0;

function mostrar(i) {
    let slides = document.getElementsByClassName("slide");
    for (let s of slides) s.classList.remove("active");
    slides[i].classList.add("active");
}

function next() {
    index = (index + 1) % <?= count($data["diapositivas"]) ?>;
    mostrar(index);
}
function prev() {
    index = (index - 1 + <?= count($data["diapositivas"]) ?>) % <?= count($data["diapositivas"]) ?>;
    mostrar(index);
}

window.onload = () => mostrar(0);
</script>
</head>

<body>

<h1><?= $data["titulo"] ?></h1>

<?php foreach ($data["diapositivas"] as $d): ?>
    <div class="slide">
        <h2><?= $d["titulo"] ?></h2>
        <p><?= nl2br($d["contenido"]) ?></p>
    </div>
<?php endforeach; ?>

<button onclick="prev()">◀</button>
<button onclick="next()">▶</button>

</body>
</html>
