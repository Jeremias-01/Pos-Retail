<?php
session_start();

require_once __DIR__ . '/config/database.php';

$usuario = trim($_POST['usuario'] ?? '');
$password = $_POST['password'] ?? '';

if ($usuario === '' || $password === '') {
    header('Location: login.php?error=Debe ingresar usuario y contraseña');
    exit;
}

$sql = "SELECT id, rol_id, nombre, usuario, password_hash, intentos_fallidos, bloqueado, estado
        FROM usuarios
        WHERE usuario = :usuario
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute(['usuario' => $usuario]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: login.php?error=Usuario o contraseña incorrectos');
    exit;
}

if ((int)$user['estado'] !== 1) {
    header('Location: login.php?error=Usuario inactivo');
    exit;
}

if ((int)$user['bloqueado'] === 1) {
    header('Location: login.php?error=Usuario bloqueado');
    exit;
}

if (!password_verify($password, $user['password_hash'])) {
    $intentos = (int)$user['intentos_fallidos'] + 1;
    $bloqueado = $intentos > 3 ? 1 : 0;

    $update = $pdo->prepare("UPDATE usuarios
                             SET intentos_fallidos = :intentos,
                                 bloqueado = :bloqueado
                             WHERE id = :id");

    $update->execute([
        'intentos' => $intentos,
        'bloqueado' => $bloqueado,
        'id' => $user['id']
    ]);

    if ($bloqueado) {
        header('Location: login.php?error=Usuario bloqueado por superar los intentos permitidos');
        exit;
    }

    header('Location: login.php?error=Usuario o contraseña incorrectos');
    exit;
}

$update = $pdo->prepare("UPDATE usuarios
                         SET intentos_fallidos = 0
                         WHERE id = :id");
$update->execute(['id' => $user['id']]);

$_SESSION['usuario_id'] = $user['id'];
$_SESSION['usuario_nombre'] = $user['nombre'];
$_SESSION['rol_id'] = $user['rol_id'];

header('Location: dashboard.php');
exit;