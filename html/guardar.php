<?php
$titulo = $_POST["titulo"];
$descripcion = $_POST["descripcion"] ?? "";
$diapositivas = json_decode($_POST["data"], true);
$id = $_POST["id"];

// --- NUEVO: recibir los objetos de texto arrastrables ---
$textos = $_POST["textos"] ?? [];   // si no hay, queda vacío

// ID nuevo si no existe
if (!$id) {
    $id = uniqid() . ".json";
}

$data = [
    "id" => $id,
    "titulo" => $titulo,
    "descripcion" => $descripcion,
    "diapositivas" => $diapositivas,

    // --- NUEVO: guardar los textos creados en el editor ---
    "textos" => $textos
];

if (!is_dir("presentaciones")) mkdir("presentaciones");

file_put_contents("presentaciones/$id", json_encode($data, JSON_PRETTY_PRINT));

header("Location: index.php");
