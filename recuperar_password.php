<?php
require_once __DIR__ . '/config/database.php';

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $nueva_password = $_POST['nueva_password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';

    if ($correo === '' || $nueva_password === '' || $confirmar_password === '') {
        $error = 'Debe completar todos los campos.';
    } elseif ($nueva_password !== $confirmar_password) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($nueva_password) < 8 || strlen($nueva_password) > 11) {
        $error = 'La contraseña debe tener entre 8 y 11 caracteres.';
    } elseif (!preg_match('/[A-Z]/', $nueva_password)) {
        $error = 'La contraseña debe incluir al menos una mayúscula.';
    } elseif (!preg_match('/[a-z]/', $nueva_password)) {
        $error = 'La contraseña debe incluir al menos una minúscula.';
    } elseif (!preg_match('/[0-9]/', $nueva_password)) {
        $error = 'La contraseña debe incluir al menos un número.';
    } elseif (!preg_match('/[^A-Za-z0-9]/', $nueva_password)) {
        $error = 'La contraseña debe incluir al menos un carácter especial.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = :correo LIMIT 1");
        $stmt->execute(['correo' => $correo]);
        $usuario = $stmt->fetch();

        if (!$usuario) {
            $error = 'No existe un usuario con ese correo.';
        } else {
            $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("UPDATE usuarios
                                   SET password_hash = :password_hash,
                                       intentos_fallidos = 0,
                                       bloqueado = 0
                                   WHERE id = :id");

            $stmt->execute([
                'password_hash' => $password_hash,
                'id' => $usuario['id']
            ]);

            $mensaje = 'Contraseña actualizada correctamente. Ya puede iniciar sesión.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar contraseña - POS Retail</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="container login-container">
        <h1>POS Retail</h1>
        <h2>Recuperar contraseña</h2>

        <?php if ($mensaje): ?>
            <p class="message"><?php echo htmlspecialchars($mensaje); ?></p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="POST">
            <label>Correo</label><br>
            <input type="email" name="correo" required><br><br>

            <label>Nueva contraseña</label><br>
            <input type="password" name="nueva_password" required><br><br>

            <label>Confirmar contraseña</label><br>
            <input type="password" name="confirmar_password" required><br><br>

            <button type="submit">Actualizar contraseña</button>
        </form>

        <p>
            <a href="login.php">Volver al login</a>
        </p>
    </div>
</body>
</html>