<?php
session_start();

if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" href="assets/css/styles.css">
    <meta charset="UTF-8">
    <title>Login - POS Retail</title>
</head>
<body>
    <div class="container login-container">
        <h1>POS Retail</h1>
        <h2>Iniciar sesión</h2>

        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form action="validar_login.php" method="POST">
            <label for="usuario">Usuario</label><br>
            <input type="text" id="usuario" name="usuario" required><br><br>

            <label for="password">Contraseña</label><br>
            <input type="password" id="password" name="password" required><br><br>

            <button type="submit">Ingresar</button>
            <br><br>
            <a href="recuperar_password.php">¿Olvidó su contraseña?</a>
        </form>
    </div>
</body>
</html>