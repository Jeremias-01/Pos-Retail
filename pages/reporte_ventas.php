<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT 
        v.id,
        v.fecha,
        c.nombre AS cliente,
        v.tipo_venta,
        v.total,
        v.estado,
        u.nombre AS usuario
    FROM ventas v
    INNER JOIN clientes c ON v.cliente_id = c.id
    INNER JOIN usuarios u ON v.usuario_id = u.id
    WHERE DATE(v.fecha) BETWEEN :fecha_inicio AND :fecha_fin
    ORDER BY v.fecha DESC
");

$stmt->execute([
    'fecha_inicio' => $fecha_inicio,
    'fecha_fin' => $fecha_fin
]);

$ventas = $stmt->fetchAll();

$total_vendido = 0;

foreach ($ventas as $venta) {
    $total_vendido += (float)$venta['total'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <meta charset="UTF-8">
    <title>Reporte de ventas - POS Retail</title>
</head>
<body>
    <div class="container">
        <h1>Reporte de ventas</h1>

    <p><a class="top-link" href="../dashboard.php">Volver al dashboard</a></p>

    <form method="GET">
        <label>Fecha inicio</label><br>
        <input type="date" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>" required><br><br>

        <label>Fecha fin</label><br>
        <input type="date" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>" required><br><br>

        <button type="submit">Filtrar</button>
    </form>

    <h2>Resumen</h2>

    <p><strong>Total de ventas:</strong> <?php echo count($ventas); ?></p>
    <p><strong>Total vendido:</strong> <?php echo number_format($total_vendido, 2); ?></p>

    <h2>Detalle de ventas</h2>

    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Tipo</th>
                <th>Total</th>
                <th>Usuario</th>
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
                    <td><?php echo htmlspecialchars($venta['usuario']); ?></td>
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

    <br>

    <button onclick="window.print()">Imprimir reporte</button>
    </div>
</body>
</html>
