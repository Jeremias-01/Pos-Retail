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
        $mensaje = 'El nombre del cliente es obligatorio.';
    } else {
        $sql = "INSERT INTO clientes (
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

        $mensaje = 'Cliente registrado correctamente.';
    }
}

$stmt = $pdo->query("SELECT * FROM clientes ORDER BY id DESC");
$clientes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clientes - POS Retail</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="container">
        <h1>Clientes</h1>

    <p><a class="top-link" href="../dashboard.php">Volver al dashboard</a></p>

    <?php if ($mensaje): ?>
        <p class="message"><?php echo htmlspecialchars($mensaje); ?></p>
    <?php endif; ?>

    <h2>Registrar cliente</h2>

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

        <button type="submit">Guardar cliente</button>
    </form>

    <h2>Listado de clientes</h2>

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
            <?php foreach ($clientes as $cliente): ?>
                <tr>
                    <td><?php echo $cliente['id']; ?></td>
                    <td><?php echo htmlspecialchars($cliente['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($cliente['nit']); ?></td>
                    <td><?php echo htmlspecialchars($cliente['telefono']); ?></td>
                    <td><?php echo htmlspecialchars($cliente['correo']); ?></td>
                    <td><?php echo htmlspecialchars($cliente['direccion']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</body>
</html>
