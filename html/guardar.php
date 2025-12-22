<?php
$titulo = $_POST["titulo"] ?? "";
$descripcion = $_POST["descripcion"] ?? "";
$diapositivas = json_decode($_POST["data"] ?? "[]", true);
$id = $_POST["id"] ?? "";

// ID nuevo si no existe
if (!$id) {
    $id = uniqid() . ".json";
}

$data = [
    "id" => $id,
    "titulo" => $titulo,
    "descripcion" => $descripcion,
    // Aquí YA viaja todo: contenido + estilos + textos (textarea) por diapositiva
    "diapositivas" => $diapositivas
];

if (!is_dir("presentaciones")) {
    mkdir("presentaciones", 0777, true);
}

file_put_contents("presentaciones/" . $id, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

header("Location: editor.php?id=" . $id);
exit;
?>