<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Comprobante no válido.');
}

$stmt = $pdo->prepare("
    SELECT 
        v.id,
        v.fecha,
        v.tipo_venta,
        v.subtotal,
        v.total,
        v.estado,
        c.nombre AS cliente,
        c.nit,
        u.nombre AS usuario
    FROM ventas v
    INNER JOIN clientes c ON v.cliente_id = c.id
    INNER JOIN usuarios u ON v.usuario_id = u.id
    WHERE v.id = :id
");

$stmt->execute(['id' => $id]);
$venta = $stmt->fetch();

if (!$venta) {
    die('Venta no encontrada.');
}

$stmt = $pdo->prepare("
    SELECT 
        dv.cantidad,
        dv.precio_unitario,
        dv.subtotal,
        p.codigo,
        p.nombre
    FROM detalle_ventas dv
    INNER JOIN productos p ON dv.producto_id = p.id
    WHERE dv.venta_id = :id
");

$stmt->execute(['id' => $id]);
$detalles = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT numero_comprobante, monto, metodo_pago, fecha
    FROM pagos
    WHERE venta_id = :id
    ORDER BY id DESC
    LIMIT 1
");

$stmt->execute(['id' => $id]);
$pago = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <meta charset="UTF-8">
    <title>Comprobante de venta</title>
</head>
<body>
    <div class="container">
        <h1>Comprobante de venta</h1>

    <p>
        <a href="ventas.php">Volver a ventas</a>
    </p>

    <hr>

    <p><strong>No. venta:</strong> <?php echo $venta['id']; ?></p>
    <p><strong>Fecha:</strong> <?php echo $venta['fecha']; ?></p>
    <p><strong>Cliente:</strong> <?php echo htmlspecialchars($venta['cliente']); ?></p>
    <p><strong>NIT:</strong> <?php echo htmlspecialchars($venta['nit']); ?></p>
    <p><strong>Atendido por:</strong> <?php echo htmlspecialchars($venta['usuario']); ?></p>
    <p><strong>Tipo de venta:</strong> <?php echo htmlspecialchars($venta['tipo_venta']); ?></p>

    <h2>Detalle</h2>

    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>Código</th>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($detalles as $detalle): ?>
                <tr>
                    <td><?php echo htmlspecialchars($detalle['codigo']); ?></td>
                    <td><?php echo htmlspecialchars($detalle['nombre']); ?></td>
                    <td><?php echo $detalle['cantidad']; ?></td>
                    <td><?php echo number_format($detalle['precio_unitario'], 2); ?></td>
                    <td><?php echo number_format($detalle['subtotal'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Totales</h2>

    <p><strong>Subtotal:</strong> <?php echo number_format($venta['subtotal'], 2); ?></p>
    <p><strong>Total:</strong> <?php echo number_format($venta['total'], 2); ?></p>

    <h2>Pago</h2>

    <?php if ($pago): ?>
        <p><strong>No. comprobante de pago:</strong> <?php echo htmlspecialchars($pago['numero_comprobante']); ?></p>
        <p><strong>Método:</strong> <?php echo htmlspecialchars($pago['metodo_pago']); ?></p>
        <p><strong>Monto:</strong> <?php echo number_format($pago['monto'], 2); ?></p>
        <p><strong>Fecha de pago:</strong> <?php echo $pago['fecha']; ?></p>
    <?php else: ?>
        <p>Venta al crédito. Pago pendiente.</p>
    <?php endif; ?>

    <br>

    <button onclick="window.print()">Imprimir comprobante</button>
    </div>
</body>
</html>
