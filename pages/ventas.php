<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$mensaje = '';
$comprobante_id = null;

$clientes = $pdo->query("SELECT id, nombre FROM clientes WHERE estado = 1 ORDER BY nombre")->fetchAll();
$productos = $pdo->query("SELECT id, codigo, nombre, precio_venta, stock_actual FROM productos WHERE estado = 1 ORDER BY nombre")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = (int)($_POST['cliente_id'] ?? 0);
    $producto_id = (int)($_POST['producto_id'] ?? 0);
    $tipo_venta = $_POST['tipo_venta'] ?? 'contado';
    $cantidad = (int)($_POST['cantidad'] ?? 0);
    $precio_unitario = (float)($_POST['precio_unitario'] ?? 0);
    $usuario_id = $_SESSION['usuario_id'];

    if ($cliente_id <= 0 || $producto_id <= 0 || $cantidad <= 0 || $precio_unitario <= 0) {
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
                throw new Exception('No hay stock suficiente para realizar la venta.');
            }

            $subtotal = $cantidad * $precio_unitario;
            $total = $subtotal;

            $stmt = $pdo->prepare("INSERT INTO ventas (
                cliente_id,
                usuario_id,
                tipo_venta,
                subtotal,
                total
            ) VALUES (
                :cliente_id,
                :usuario_id,
                :tipo_venta,
                :subtotal,
                :total
            )");

            $stmt->execute([
                'cliente_id' => $cliente_id,
                'usuario_id' => $usuario_id,
                'tipo_venta' => $tipo_venta,
                'subtotal' => $subtotal,
                'total' => $total
            ]);

            $venta_id = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO detalle_ventas (
                venta_id,
                producto_id,
                cantidad,
                precio_unitario,
                subtotal
            ) VALUES (
                :venta_id,
                :producto_id,
                :cantidad,
                :precio_unitario,
                :subtotal
            )");

            $stmt->execute([
                'venta_id' => $venta_id,
                'producto_id' => $producto_id,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio_unitario,
                'subtotal' => $subtotal
            ]);

            $stock_nuevo = $stock_anterior - $cantidad;

            $stmt = $pdo->prepare("UPDATE productos
                                   SET stock_actual = :stock_nuevo,
                                       precio_venta = :precio_unitario
                                   WHERE id = :id");

            $stmt->execute([
                'stock_nuevo' => $stock_nuevo,
                'precio_unitario' => $precio_unitario,
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
                'venta',
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
                'referencia_id' => $venta_id,
                'cantidad' => $cantidad,
                'stock_anterior' => $stock_anterior,
                'stock_nuevo' => $stock_nuevo,
                'costo_unitario' => $precio_unitario,
                'observacion' => 'Venta registrada'
            ]);

            if ($tipo_venta === 'contado') {
                $numero_comprobante = 'PAGO-' . str_pad($venta_id, 6, '0', STR_PAD_LEFT);

                $stmt = $pdo->prepare("INSERT INTO pagos (
                    venta_id,
                    usuario_id,
                    monto,
                    metodo_pago,
                    numero_comprobante
                ) VALUES (
                    :venta_id,
                    :usuario_id,
                    :monto,
                    :metodo_pago,
                    :numero_comprobante
                )");

                $stmt->execute([
                    'venta_id' => $venta_id,
                    'usuario_id' => $usuario_id,
                    'monto' => $total,
                    'metodo_pago' => 'Efectivo',
                    'numero_comprobante' => $numero_comprobante
                ]);
            }

            if ($tipo_venta === 'credito') {
                $stmt = $pdo->prepare("INSERT INTO cuentas_por_cobrar (
                    venta_id,
                    monto_total,
                    saldo_pendiente
                ) VALUES (
                    :venta_id,
                    :monto_total,
                    :saldo_pendiente
                )");

                $stmt->execute([
                    'venta_id' => $venta_id,
                    'monto_total' => $total,
                    'saldo_pendiente' => $total
                ]);
            }

            $pdo->commit();
            $comprobante_id = $venta_id;
            $mensaje = 'Venta registrada correctamente. Stock actualizado.';
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensaje = $e->getMessage();
        }
    }
}

$ventas = $pdo->query("
    SELECT v.id, v.fecha, c.nombre AS cliente, v.tipo_venta, v.total, v.estado
    FROM ventas v
    INNER JOIN clientes c ON v.cliente_id = c.id
    ORDER BY v.id DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <meta charset="UTF-8">
    <title>Ventas - POS Retail</title>
</head>
<body>
    <div class="container">
        <h1>Ventas</h1>

    <p><a class="top-link" href="../dashboard.php">Volver al dashboard</a></p>

    <?php if ($mensaje): ?>
        <p class="message"><?php echo htmlspecialchars($mensaje); ?></p>
    <?php endif; ?>

    <?php if ($comprobante_id): ?>
        <p>
            <a href="comprobante_venta.php?id=<?php echo $comprobante_id; ?>">
                Ver comprobante de venta
            </a>
        </p>
    <?php endif; ?>

    <h2>Registrar venta</h2>

    <form method="POST">
        <label>Cliente</label><br>
        <select name="cliente_id" required>
            <option value="">Seleccione...</option>
            <?php foreach ($clientes as $cliente): ?>
                <option value="<?php echo $cliente['id']; ?>">
                    <?php echo htmlspecialchars($cliente['nombre']); ?>
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

        <label>Tipo de venta</label><br>
        <select name="tipo_venta" required>
            <option value="contado">Contado</option>
            <option value="credito">Crédito</option>
        </select><br><br>

        <label>Cantidad</label><br>
        <input type="number" name="cantidad" min="1" required><br><br>

        <label>Precio unitario</label><br>
        <input type="number" name="precio_unitario" step="0.01" min="0.01" required><br><br>

        <button type="submit">Guardar venta</button>
    </form>

    <h2>Ventas registradas</h2>

    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Tipo</th>
                <th>Total</th>
                <th>Estado</th>
                <th>Comprobante</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ventas as $venta): ?>
                <tr>
                    <td><?php echo $venta['id']; ?></td>
                    <td><?php echo $venta['fecha']; ?></td>
                    <td><?php echo htmlspecialchars($venta['cliente']); ?></td>
                    <td><?php echo htmlspecialchars($venta['tipo_venta']); ?></td>
                    <td><?php echo number_format($venta['total'], 2); ?></td>
                    <td><?php echo htmlspecialchars($venta['estado']); ?></td>
                    <td>
                        <a href="comprobante_venta.php?id=<?php echo $venta['id']; ?>">
                            Ver
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</body>
</html>
