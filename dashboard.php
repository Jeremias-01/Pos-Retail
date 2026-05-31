<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" href="assets/css/styles.css">
    <meta charset="UTF-8">
    <title>Dashboard - POS Retail</title>
</head>
<body>
    <div class="container">
        <h1>POS Retail</h1>

    <p>Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></p>

    <h2>Panel principal</h2>

    <ul class="nav-list">
        <li><a href="pages/productos.php">Productos</a></li>
        <li><a href="pages/proveedores.php">Proveedores</a></li>
        <li><a href="pages/clientes.php">Clientes</a></li>
        <li><a href="pages/compras.php">Compras</a></li>
        <li><a href="pages/devoluciones_proveedor.php">Devoluciones al proveedor</a></li>
        <li><a href="pages/ventas.php">Ventas</a></li>  
        <li><a href="pages/kardex.php">Kardex</a></li>
        <li><a href="pages/reporte_ventas.php">Reporte de ventas</a></li>
    </ul>
    <a href="logout.php">Cerrar sesión</a>
    </div>
</body>
</html>
