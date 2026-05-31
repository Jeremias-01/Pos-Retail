<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio_compra = (float)($_POST['precio_compra'] ?? 0);
    $precio_venta = (float)($_POST['precio_venta'] ?? 0);
    $stock_actual = (int)($_POST['stock_actual'] ?? 0);
    $stock_minimo = (int)($_POST['stock_minimo'] ?? 0);

    if ($codigo === '' || $nombre === '') {
        $mensaje = 'Código y nombre son obligatorios.';
    } else {
        $sql = "INSERT INTO productos (
                    codigo,
                    nombre,
                    descripcion,
                    precio_compra,
                    precio_venta,
                    stock_actual,
                    stock_minimo
                ) VALUES (
                    :codigo,
                    :nombre,
                    :descripcion,
                    :precio_compra,
                    :precio_venta,
                    :stock_actual,
                    :stock_minimo
                )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'precio_compra' => $precio_compra,
            'precio_venta' => $precio_venta,
            'stock_actual' => $stock_actual,
            'stock_minimo' => $stock_minimo
        ]);

        $mensaje = 'Producto registrado correctamente.';
    }
}

$stmt = $pdo->query("SELECT * FROM productos ORDER BY id DESC");
$productos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <meta charset="UTF-8">
    <title>Productos - POS Retail</title>
</head>
<body>
    <div class="container">
        <h1>Productos</h1>

    <p><a class="top-link" href="../dashboard.php">Volver al dashboard</a></p>

    <?php if ($mensaje): ?>
        <p class="message"><?php echo htmlspecialchars($mensaje); ?></p>
    <?php endif; ?>

    <h2>Registrar producto</h2>

    <form method="POST">
        <label>Código</label><br>
        <input type="text" name="codigo" required><br><br>

        <label>Nombre</label><br>
        <input type="text" name="nombre" required><br><br>

        <label>Descripción</label><br>
        <textarea name="descripcion"></textarea><br><br>

        <label>Precio compra</label><br>
        <input type="number" name="precio_compra" step="0.01" min="0" required><br><br>

        <label>Precio venta</label><br>
        <input type="number" name="precio_venta" step="0.01" min="0" required><br><br>

        <label>Stock actual</label><br>
        <input type="number" name="stock_actual" min="0" required><br><br>

        <label>Stock mínimo</label><br>
        <input type="number" name="stock_minimo" min="0" required><br><br>

        <button type="submit">Guardar producto</button>
    </form>

    <h2>Listado de productos</h2>

    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>ID</th>
                <th>Código</th>
                <th>Nombre</th>
                <th>Precio compra</th>
                <th>Precio venta</th>
                <th>Stock</th>
                <th>Stock mínimo</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($productos as $producto): ?>
                <tr>
                    <td><?php echo $producto['id']; ?></td>
                    <td><?php echo htmlspecialchars($producto['codigo']); ?></td>
                    <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                    <td><?php echo number_format($producto['precio_compra'], 2); ?></td>
                    <td><?php echo number_format($producto['precio_venta'], 2); ?></td>
                    <td><?php echo $producto['stock_actual']; ?></td>
                    <td><?php echo $producto['stock_minimo']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</body>
</html>
