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
    <?php include '../includes/navbar.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content" id="mainContent">
        <div class="presentation-container-material-clase">
            <div class="content-title-presentation">
                <h1>Material de Clase</h1>
                <p>Encuentra aquí todo el material necesario para tus clases en UPIICSA.</p>
            </div>
            <div class="button-add-presentation">
                <button type="button">+ Agregar nueva presentacion</button>
            </div>
            <div class="cards-container">
                <div class="card-material">
                    <h2 class="title-card">Programación</h2>
                    <p class="description-card">Recursos y apuntes para tus clases de programación.</p>
                    <p class="updated-date">Actualizado: 10/09/2025</p>
                    <div class="card-material-buttons">
                        <button class="button-presentar" type="button">Presentar</button>
                        <button class="button-editar" type="button"><i class="fa-solid fa-pen-to-square"></i></button>
                        <button class="button-eliminar" type="button"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </div>
                <div class="card-material">
                    <h2 class="title-card">Simuladores virtuales</h2>
                    <p class="description-card">Guías y ejemplos para mejorar tus habilidades de simuladores virtuales.</p>
                    <p class="updated-date">Actualizado: 10/09/2025</p>
                    <div class="card-material-buttons">
                        <button class="button-presentar" type="button">Presentar</button>
                        <button class="button-editar" type="button"><i class="fa-solid fa-pen-to-square"></i></button>
                        <button class="button-eliminar" type="button"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </div>
                <div class="card-material">
                    <h2 class="title-card">Electrónica</h2>
                    <p class="description-card">Documentos y proyectos relacionados con electrónica.</p>
                    <p class="updated-date">Actualizado: 10/09/2025</p>
                    <div class="card-material-buttons">
                        <button class="button-presentar" type="button">Presentar</button>
                        <button class="button-editar" type="button"><i class="fa-solid fa-pen-to-square"></i></button>
                        <button class="button-eliminar" type="button"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </main>


    <script src="../js/sidebar.js"></script>
</body>
</html>