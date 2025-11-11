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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UPIICSA Login Page</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="login-container">           
        <form class="form-container" method="post">      
            <div class="logo-login">
                <img src="../img/upiicsa.png" alt="Logo UPIICSA">
                <label for="signin">Iniciar Sesión</label>
            </div>
            <div class="username-login">    
                <input type="text" id="username" name="username" required>
                <label for="username">No.Boleta</label>
            </div>
            <div class="password-login">
                <input type="password" id="password" name="password" required>
                <label for="password">Contraseña</label>
            </div>
            <button class="submit-acept" type="submit">Iniciar Sesión</button>
            <a href="#" class="link-forgot-password">Olvide mi contraseña</a>
            <a href="register.php" class="link-register">¿No tienes una cuenta? Regístrate</a>
        </form>
    </div>
</body>
</html>