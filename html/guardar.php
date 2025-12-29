<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
  header('Location: login.php');
  exit;
}

$titulo = $_POST["titulo"] ?? "";
$descripcion = $_POST["descripcion"] ?? "";
$id = $_POST["id"] ?? "";

$raw = $_POST["data"] ?? "[]";
$diapositivas = json_decode($raw, true);
if (!is_array($diapositivas)) $diapositivas = [];

# Normaliza: cada diapositiva debe ser array y tener textos dentro
$diapositivas_norm = [];
foreach ($diapositivas as $s) {
  if (!is_array($s)) continue;
  $diapositivas_norm[] = [
    "titulo" => $s["titulo"] ?? "Nuevo Slide",
    "contenido" => $s["contenido"] ?? "",
    "color" => $s["color"] ?? "#111827",
    "size" => $s["size"] ?? "34px",
    "alineacion" => $s["alineacion"] ?? "center",
    "textos" => (isset($s["textos"]) && is_array($s["textos"])) ? $s["textos"] : []
  ];
}

if (!$id) {
  $id = uniqid() . ".json";
}

// seguridad: evita rutas
$id = basename($id);

$data = [
  "id" => $id,
  "titulo" => $titulo,
  "descripcion" => $descripcion,
  "diapositivas" => $diapositivas_norm
];

if (!is_dir("presentaciones")) mkdir("presentaciones", 0777, true);

file_put_contents("presentaciones/$id", json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

header("Location: index.php");
exit;
