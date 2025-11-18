<?php
// Inicia la sesión PHP
session_start();

if (isset($_POST['username']) && isset($_POST['password'])) {
     $username = $_POST['username'];
     $password = $_POST['password'];

    // Ejemplo: si las credenciales son correctas
    if ($username === 'usuario' && $password === 'contrasena') {
        // Establece la variable de sesión para el usuario
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;

        // Redirige al usuario a la página principal (index.php)
        header('Location: index.php');
        exit; // Es importante usar exit() después de una redirección
    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>