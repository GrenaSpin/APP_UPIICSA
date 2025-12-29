<?php
$titulo = $_POST["titulo"] ?? "Mi Presentación";
$descripcion = $_POST["descripcion"] ?? "";
$diapositivas = json_decode($_POST["data"] ?? "[]", true);
$id = $_POST["id"] ?? "";

if (!is_array($diapositivas)) $diapositivas = [];

// ID nuevo si no existe
if (!$id) {
  $id = uniqid() . ".json";
}

$data = [
  "id" => $id,
  "titulo" => $titulo,
  "descripcion" => $descripcion,
  "diapositivas" => $diapositivas
];

if (!is_dir("presentaciones")) mkdir("presentaciones", 0777, true);

file_put_contents($ruta, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

header("Location: index.php");
exit;