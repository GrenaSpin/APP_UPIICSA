<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UPIICSA Register Page</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <content class="register-container">
        <form method="post" class="form-container">      
            <label for="registro">REGISTRO</label>
            <div class="name">
                <input placeholder="Nombre completo" type="text" id="nombre_completo" name="nombre_completo" required>
            </div>
            <div class="username-register">
                <input placeholder="No.Boleta" type="text" id="username" name="username" required>
            </div>
            <div class="genero">
                <label for="genero-opciones">Género: </label>
                <select name="genero-opciones">
                    <option value="masculino">Masculino</option>
                    <option value="femenino">Femenino</option>
                </select>
            </div>
            <div class="correo">
                <input placeholder="Correo Electrónico" type="email" id="email" name="email" required>
            </div>
            <div class="password">
                <input placeholder="Contraseña" type="password" id="password" name="password" required>
            </div>
            <div class="confirm-password">
                <input placeholder="Confirmar Contraseña" type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="buttons-register">
                <input class="submit-acept" type="submit" value="Guardar">
            </div>
            <div class="regresar-login">
                <p>Ya tienes una cuenta? </p><a href="login.php" class="link-login">Iniciar sesión</a>
            </div>
        </form>
    </content>
</body>
</html>