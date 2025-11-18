<?php
if (!is_dir("uploads")) mkdir("uploads");

$archivo = $_FILES["imagen"];
$nombre = "img_" . time() . "_" . rand(1000,9999) . ".png";
$ruta = "uploads/" . $nombre;

move_uploaded_file($archivo["tmp_name"], $ruta);

echo $ruta;
?>