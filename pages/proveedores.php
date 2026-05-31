<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $nit = trim($_POST['nit'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if ($nombre === '') {
        $mensaje = 'El nombre del proveedor es obligatorio.';
    } else {
        $sql = "INSERT INTO proveedores (
                    nombre,
                    nit,
                    telefono,
                    correo,
                    direccion
                ) VALUES (
                    :nombre,
                    :nit,
                    :telefono,
                    :correo,
                    :direccion
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'nombre' => $nombre,
            'nit' => $nit,
            'telefono' => $telefono,
            'correo' => $correo,
            'direccion' => $direccion
        ]);

        $mensaje = 'Proveedor registrado correctamente.';
    }
}

$stmt = $pdo->query("SELECT * FROM proveedores ORDER BY id DESC");
$proveedores = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <meta charset="UTF-8">
    <title>Proveedores - POS Retail</title>
</head>
<body>
    <div class="container">
        <h1>Proveedores</h1>

    <p><a class="top-link" href="../dashboard.php">Volver al dashboard</a></p>

    <?php if ($mensaje): ?>
        <p class="message"><?php echo htmlspecialchars($mensaje); ?></p>
    <?php endif; ?>

    <h2>Registrar proveedor</h2>

    <form method="POST">
        <label>Nombre</label><br>
        <input type="text" name="nombre" required><br><br>

        <label>NIT</label><br>
        <input type="text" name="nit"><br><br>

        <label>Teléfono</label><br>
        <input type="text" name="telefono"><br><br>

        <label>Correo</label><br>
        <input type="email" name="correo"><br><br>

        <label>Dirección</label><br>
        <textarea name="direccion"></textarea><br><br>

        <button type="submit">Guardar proveedor</button>
    </form>

    <h2>Listado de proveedores</h2>

    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>NIT</th>
                <th>Teléfono</th>
                <th>Correo</th>
                <th>Dirección</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($proveedores as $proveedor): ?>
                <tr>
                    <td><?php echo $proveedor['id']; ?></td>
                    <td><?php echo htmlspecialchars($proveedor['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($proveedor['nit']); ?></td>
                    <td><?php echo htmlspecialchars($proveedor['telefono']); ?></td>
                    <td><?php echo htmlspecialchars($proveedor['correo']); ?></td>
                    <td><?php echo htmlspecialchars($proveedor['direccion']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</body>
</html>
