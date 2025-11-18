<?php
$id = $_GET["id"];

if (file_exists("presentaciones/$id")) {
    unlink("presentaciones/$id");
}

header("Location: index.php");
exit;