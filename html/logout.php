<?php

// Destruye todas las variables de sesión
session_unset(); 

// Destruye la sesión por completo
session_destroy(); 


// Verifica si el usuario ha iniciado sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Si no ha iniciado sesión, redirige a login.php
    header('Location: login.php');
    exit; // Detiene la ejecución del script
}
?>