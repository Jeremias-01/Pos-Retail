<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$mensaje = '';

$proveedores = $pdo->query("SELECT id, nombre FROM proveedores WHERE estado = 1 ORDER BY nombre")->fetchAll();
$productos = $pdo->query("SELECT id, codigo, nombre, precio_compra, stock_actual FROM productos WHERE estado = 1 ORDER BY nombre")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $proveedor_id = (int)($_POST['proveedor_id'] ?? 0);
    $producto_id = (int)($_POST['producto_id'] ?? 0);
    $cantidad = (int)($_POST['cantidad'] ?? 0);
    $costo_unitario = (float)($_POST['costo_unitario'] ?? 0);
    $motivo = trim($_POST['motivo'] ?? '');
    $usuario_id = $_SESSION['usuario_id'];

    if ($proveedor_id <= 0 || $producto_id <= 0 || $cantidad <= 0 || $costo_unitario <= 0 || $motivo === '') {
        $mensaje = 'Debe completar todos los campos correctamente.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT stock_actual FROM productos WHERE id = :id");
            $stmt->execute(['id' => $producto_id]);
            $producto = $stmt->fetch();

            if (!$producto) {
                throw new Exception('Producto no encontrado.');
            }

            $stock_anterior = (int)$producto['stock_actual'];

            if ($stock_anterior < $cantidad) {
                throw new Exception('No hay stock suficiente para realizar la devolución.');
            }

            $subtotal = $cantidad * $costo_unitario;
            $total = $subtotal;

            $stmt = $pdo->prepare("INSERT INTO devoluciones_proveedor (
                proveedor_id,
                usuario_id,
                motivo,
                total
            ) VALUES (
                :proveedor_id,
                :usuario_id,
                :motivo,
                :total
            )");

            $stmt->execute([
                'proveedor_id' => $proveedor_id,
                'usuario_id' => $usuario_id,
                'motivo' => $motivo,
                'total' => $total
            ]);

            $devolucion_id = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO detalle_devoluciones_proveedor (
                devolucion_id,
                producto_id,
                cantidad,
                costo_unitario,
                subtotal
            ) VALUES (
                :devolucion_id,
                :producto_id,
                :cantidad,
                :costo_unitario,
                :subtotal
            )");

            $stmt->execute([
                'devolucion_id' => $devolucion_id,
                'producto_id' => $producto_id,
                'cantidad' => $cantidad,
                'costo_unitario' => $costo_unitario,
                'subtotal' => $subtotal
            ]);

            $stock_nuevo = $stock_anterior - $cantidad;

            $stmt = $pdo->prepare("UPDATE productos
                                   SET stock_actual = :stock_nuevo
                                   WHERE id = :id");

            $stmt->execute([
                'stock_nuevo' => $stock_nuevo,
                'id' => $producto_id
            ]);

            $stmt = $pdo->prepare("INSERT INTO kardex (
                producto_id,
                usuario_id,
                tipo_movimiento,
                origen,
                referencia_id,
                cantidad,
                stock_anterior,
                stock_nuevo,
                costo_unitario,
                observacion
            ) VALUES (
                :producto_id,
                :usuario_id,
                'salida',
                'devolucion_proveedor',
                :referencia_id,
                :cantidad,
                :stock_anterior,
                :stock_nuevo,
                :costo_unitario,
                :observacion
            )");

            $stmt->execute([
                'producto_id' => $producto_id,
                'usuario_id' => $usuario_id,
                'referencia_id' => $devolucion_id,
                'cantidad' => $cantidad,
                'stock_anterior' => $stock_anterior,
                'stock_nuevo' => $stock_nuevo,
                'costo_unitario' => $costo_unitario,
                'observacion' => 'Devolución al proveedor: ' . $motivo
            ]);

            $pdo->commit();
            $mensaje = 'Devolución registrada correctamente. Stock actualizado.';
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensaje = $e->getMessage();
        }
    }
}

$devoluciones = $pdo->query("
    SELECT d.id, d.fecha, p.nombre AS proveedor, d.motivo, d.total, d.estado
    FROM devoluciones_proveedor d
    INNER JOIN proveedores p ON d.proveedor_id = p.id
    ORDER BY d.id DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <meta charset="UTF-8">
    <title>Devoluciones al proveedor - POS Retail</title>
</head>
<body>
    <div class="container">
        <h1>Devoluciones al proveedor</h1>

    <p><a class="top-link" href="../dashboard.php">Volver al dashboard</a></p>

    <?php if ($mensaje): ?>
        <p class="message"><?php echo htmlspecialchars($mensaje); ?></p>
    <?php endif; ?>

    <h2>Registrar devolución</h2>

    <form method="POST">
        <label>Proveedor</label><br>
        <select name="proveedor_id" required>
            <option value="">Seleccione...</option>
            <?php foreach ($proveedores as $proveedor): ?>
                <option value="<?php echo $proveedor['id']; ?>">
                    <?php echo htmlspecialchars($proveedor['nombre']); ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Producto</label><br>
        <select name="producto_id" required>
            <option value="">Seleccione...</option>
            <?php foreach ($productos as $producto): ?>
                <option value="<?php echo $producto['id']; ?>">
                    <?php echo htmlspecialchars($producto['codigo'] . ' - ' . $producto['nombre'] . ' | Stock: ' . $producto['stock_actual']); ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Cantidad</label><br>
        <input type="number" name="cantidad" min="1" required><br><br>

        <label>Costo unitario</label><br>
        <input type="number" name="costo_unitario" step="0.01" min="0.01" required><br><br>

        <label>Motivo</label><br>
        <textarea name="motivo" required></textarea><br><br>

        <button type="submit">Guardar devolución</button>
    </form>

    <h2>Devoluciones registradas</h2>

    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Proveedor</th>
                <th>Motivo</th>
                <th>Total</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($devoluciones as $devolucion): ?>
                <tr>
                    <td><?php echo $devolucion['id']; ?></td>
                    <td><?php echo $devolucion['fecha']; ?></td>
                    <td><?php echo htmlspecialchars($devolucion['proveedor']); ?></td>
                    <td><?php echo htmlspecialchars($devolucion['motivo']); ?></td>
                    <td><?php echo number_format($devolucion['total'], 2); ?></td>
                    <td><?php echo htmlspecialchars($devolucion['estado']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</body>
</html>
