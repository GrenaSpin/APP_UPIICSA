<?php
// Inicia la sesión PHP
session_start();

// Verifica si el usuario ha iniciado sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Si no ha iniciado sesión, redirige a login.php
    header('Location: login.php');
    exit; // Detiene la ejecución del script
}
?>

<?php
// Cargar presentaciones
$presentaciones = glob("presentaciones/*.json");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UPIICSA - Inicio</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include ("../includes/navbar.php"); ?>
    <?php include ("../includes/sidebar.php"); ?>
    <main class="main-content" id="mainContent">

        <div class="presentation-container-material-clase">

            <div class="content-title-presentation">
                <h1>Material de Clase</h1>
                <p>Encuentra aquí todo el material necesario para tus clases en UPIICSA.</p>
            </div>

            <div class="button-add-presentation">
                <button type="button" onclick="window.location='editor.php'">
                    + Agregar nueva presentación
                </button>
            </div>

            <div class="cards-container">

                <?php
                if (count($presentaciones) == 0) {
                    echo "<p>No hay presentaciones creadas aún.</p>";
                }

                foreach ($presentaciones as $archivo) {
                    $data = json_decode(file_get_contents($archivo), true);
                    $id = basename($archivo);
                ?>

                <div class="card-material">

                    <h2 class="title-card">
                        <?= htmlspecialchars($data["titulo"]) ?>
                    </h2>

                    <p class="description-card">
                        <?= isset($data["descripcion"]) ? htmlspecialchars($data["descripcion"]) : "Sin descripción" ?>
                    </p>

                    <p class="updated-date">
                        Actualizado: <?= date("d/m/Y", filemtime($archivo)) ?>
                    </p>

                    <div class="card-material-buttons">
                        <button class="button-presentar" 
                                type="button"
                                onclick="window.location='presentacion.php?id=<?= $id ?>'">
                            Presentar
                        </button>

                        <button class="button-editar"
                                type="button"
                                onclick="window.location='editor.php?id=<?= $id ?>'">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>

                        <button class="button-eliminar"
                                type="button"
                                onclick="eliminarPresentacion('<?= $id ?>')">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>

                <?php } ?>

            </div>
        </div>
    </main>

    <script src="../js/sidebar.js"></script>

    <!-- Script eliminar -->
    <script>
        function eliminarPresentacion(id) {
            if (confirm("¿Seguro que deseas eliminar esta presentación?")) {
                window.location = "eliminar.php?id=" + id;
            }
        }
    </script>

</body>
</html>