<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$movimientos = $pdo->query("
    SELECT 
        k.id,
        k.fecha,
        p.codigo,
        p.nombre AS producto,
        k.tipo_movimiento,
        k.origen,
        k.referencia_id,
        k.cantidad,
        k.stock_anterior,
        k.stock_nuevo,
        k.costo_unitario,
        k.observacion,
        u.nombre AS usuario
    FROM kardex k
    INNER JOIN productos p ON k.producto_id = p.id
    INNER JOIN usuarios u ON k.usuario_id = u.id
    ORDER BY k.id DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <meta charset="UTF-8">
    <title>Kardex - POS Retail</title>
</head>
<body>
    <div class="container">
        <h1>Kardex de productos</h1>

    <p><a class="top-link" href="../dashboard.php">Volver al dashboard</a></p>

    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Producto</th>
                <th>Movimiento</th>
                <th>Origen</th>
                <th>Referencia</th>
                <th>Cantidad</th>
                <th>Stock anterior</th>
                <th>Stock nuevo</th>
                <th>Costo</th>
                <th>Usuario</th>
                <th>Observación</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($movimientos as $movimiento): ?>
                <tr>
                    <td><?php echo $movimiento['id']; ?></td>
                    <td><?php echo $movimiento['fecha']; ?></td>
                    <td>
                        <?php echo htmlspecialchars($movimiento['codigo'] . ' - ' . $movimiento['producto']); ?>
                    </td>
                    <td><?php echo htmlspecialchars($movimiento['tipo_movimiento']); ?></td>
                    <td><?php echo htmlspecialchars($movimiento['origen']); ?></td>
                    <td><?php echo $movimiento['referencia_id']; ?></td>
                    <td><?php echo $movimiento['cantidad']; ?></td>
                    <td><?php echo $movimiento['stock_anterior']; ?></td>
                    <td><?php echo $movimiento['stock_nuevo']; ?></td>
                    <td><?php echo number_format($movimiento['costo_unitario'], 2); ?></td>
                    <td><?php echo htmlspecialchars($movimiento['usuario']); ?></td>
                    <td><?php echo htmlspecialchars($movimiento['observacion']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</body>
</html>
