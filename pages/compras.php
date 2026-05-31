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
    $tipo_compra = $_POST['tipo_compra'] ?? 'contado';
    $cantidad = (int)($_POST['cantidad'] ?? 0);
    $costo_unitario = (float)($_POST['costo_unitario'] ?? 0);
    $usuario_id = $_SESSION['usuario_id'];

    if ($proveedor_id <= 0 || $producto_id <= 0 || $cantidad <= 0 || $costo_unitario <= 0) {
        $mensaje = 'Debe completar todos los campos correctamente.';
    } else {
        try {
            $pdo->beginTransaction();

            $subtotal = $cantidad * $costo_unitario;
            $total = $subtotal;

            $stmt = $pdo->prepare("INSERT INTO compras (
                proveedor_id,
                usuario_id,
                tipo_compra,
                subtotal,
                total
            ) VALUES (
                :proveedor_id,
                :usuario_id,
                :tipo_compra,
                :subtotal,
                :total
            )");

            $stmt->execute([
                'proveedor_id' => $proveedor_id,
                'usuario_id' => $usuario_id,
                'tipo_compra' => $tipo_compra,
                'subtotal' => $subtotal,
                'total' => $total
            ]);

            $compra_id = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO detalle_compras (
                compra_id,
                producto_id,
                cantidad,
                costo_unitario,
                subtotal
            ) VALUES (
                :compra_id,
                :producto_id,
                :cantidad,
                :costo_unitario,
                :subtotal
            )");

            $stmt->execute([
                'compra_id' => $compra_id,
                'producto_id' => $producto_id,
                'cantidad' => $cantidad,
                'costo_unitario' => $costo_unitario,
                'subtotal' => $subtotal
            ]);

            $stmt = $pdo->prepare("SELECT stock_actual FROM productos WHERE id = :id");
            $stmt->execute(['id' => $producto_id]);
            $producto = $stmt->fetch();

            $stock_anterior = (int)$producto['stock_actual'];
            $stock_nuevo = $stock_anterior + $cantidad;

            $stmt = $pdo->prepare("UPDATE productos
                                   SET stock_actual = :stock_nuevo,
                                       precio_compra = :costo_unitario
                                   WHERE id = :id");

            $stmt->execute([
                'stock_nuevo' => $stock_nuevo,
                'costo_unitario' => $costo_unitario,
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
                'entrada',
                'compra',
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
                'referencia_id' => $compra_id,
                'cantidad' => $cantidad,
                'stock_anterior' => $stock_anterior,
                'stock_nuevo' => $stock_nuevo,
                'costo_unitario' => $costo_unitario,
                'observacion' => 'Compra registrada'
            ]);

            if ($tipo_compra === 'credito') {
                $stmt = $pdo->prepare("INSERT INTO cuentas_por_pagar (
                    compra_id,
                    monto_total,
                    saldo_pendiente
                ) VALUES (
                    :compra_id,
                    :monto_total,
                    :saldo_pendiente
                )");

                $stmt->execute([
                    'compra_id' => $compra_id,
                    'monto_total' => $total,
                    'saldo_pendiente' => $total
                ]);
            }

            $pdo->commit();
            $mensaje = 'Compra registrada correctamente. Stock actualizado.';
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensaje = 'Error al registrar la compra.';
        }
    }
}

$compras = $pdo->query("
    SELECT c.id, c.fecha, p.nombre AS proveedor, c.tipo_compra, c.total, c.estado
    FROM compras c
    INNER JOIN proveedores p ON c.proveedor_id = p.id
    ORDER BY c.id DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <meta charset="UTF-8">
    <title>Compras - POS Retail</title>
</head>
<body>
    <div class="container">
        <h1>Compras</h1>

    <p><a class="top-link" href="../dashboard.php">Volver al dashboard</a></p>

    <?php if ($mensaje): ?>
        <p class="message"><?php echo htmlspecialchars($mensaje); ?></p>
    <?php endif; ?>

    <h2>Registrar compra</h2>

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

        <label>Tipo de compra</label><br>
        <select name="tipo_compra" required>
            <option value="contado">Contado</option>
            <option value="credito">Crédito</option>
        </select><br><br>

        <label>Cantidad</label><br>
        <input type="number" name="cantidad" min="1" required><br><br>

        <label>Costo unitario</label><br>
        <input type="number" name="costo_unitario" step="0.01" min="0.01" required><br><br>

        <button type="submit">Guardar compra</button>
    </form>

    <h2>Compras registradas</h2>

    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Proveedor</th>
                <th>Tipo</th>
                <th>Total</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($compras as $compra): ?>
                <tr>
                    <td><?php echo $compra['id']; ?></td>
                    <td><?php echo $compra['fecha']; ?></td>
                    <td><?php echo htmlspecialchars($compra['proveedor']); ?></td>
                    <td><?php echo htmlspecialchars($compra['tipo_compra']); ?></td>
                    <td><?php echo number_format($compra['total'], 2); ?></td>
                    <td><?php echo htmlspecialchars($compra['estado']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</body>
</html>
