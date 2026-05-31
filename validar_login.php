<?php
session_start();

require_once __DIR__ . '/config/database.php';

function redirectLoginError(string $message): void
{
    session_unset();
    session_destroy();
    header('Location: login.php?error=' . urlencode($message));
    exit;
}

$usuario = trim($_POST['usuario'] ?? '');
$password = $_POST['password'] ?? '';

if ($usuario === '' || $password === '') {
    redirectLoginError('Debe ingresar usuario y contrasena');
}

$sql = "SELECT id, rol_id, nombre, usuario, password_hash, intentos_fallidos, bloqueado, estado
        FROM usuarios
        WHERE usuario = :usuario
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute(['usuario' => $usuario]);
$user = $stmt->fetch();

if (!$user) {
    redirectLoginError('Usuario o contrasena incorrectos');
}

if ((int)$user['estado'] !== 1) {
    redirectLoginError('Usuario inactivo');
}

if ((int)$user['bloqueado'] === 1) {
    redirectLoginError('Usuario bloqueado');
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
        redirectLoginError('Usuario bloqueado por superar los intentos permitidos');
    }

    redirectLoginError('Usuario o contrasena incorrectos');
}

$update = $pdo->prepare("UPDATE usuarios
                         SET intentos_fallidos = 0
                         WHERE id = :id");
$update->execute(['id' => $user['id']]);

session_regenerate_id(true);
$_SESSION['usuario_id'] = $user['id'];
$_SESSION['usuario_nombre'] = $user['nombre'];
$_SESSION['rol_id'] = $user['rol_id'];

header('Location: dashboard.php');
exit;
