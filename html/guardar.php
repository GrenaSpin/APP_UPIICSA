<?php
$titulo = $_POST["titulo"];
$descripcion = $_POST["descripcion"] ?? "";
$diapositivas = json_decode($_POST["data"], true);
$id = $_POST["id"];

// ID nuevo si no existe
if (!$id) {
    $id = uniqid() . ".json";
}

$data = [
    "id" => $id,
    "titulo" => $titulo,
    "descripcion" => $descripcion,
    // Las cajas de texto arrastrables se guardan dentro de cada diapositiva
    // (diapositivas[i].textos) desde editor.js.
    "diapositivas" => $diapositivas
];

if (!is_dir("presentaciones")) mkdir("presentaciones");

file_put_contents("presentaciones/$id", json_encode($data, JSON_PRETTY_PRINT));

header("Location: index.php");
