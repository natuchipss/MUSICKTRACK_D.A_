<?php

require_once __DIR__ . '/../includes/auth.php';
accion_protegida(false);

$nombre    = trim($_POST['nombre'] ?? '');
$email     = mb_strtolower(trim($_POST['email'] ?? ''));
$password  = (string) ($_POST['password'] ?? '');
$confirmar = (string) ($_POST['confirmar'] ?? '');

function error_registro(string $codigo, string $nombre, string $email): never
{
    $_SESSION['viejo_nombre'] = $nombre;
    $_SESSION['viejo_email']  = $email;
    header('Location: ../registro.php?error=' . $codigo);
    exit;
}

if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 80) {
    error_registro('nombre', $nombre, $email);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
    error_registro('email', $nombre, $email);
}
if (mb_strlen($password) < 8) {
    error_registro('password', $nombre, $email);
}
if ($password !== $confirmar) {
    error_registro('confirmar', $nombre, $email);
}

$pdo = db();

$stmt = $pdo->prepare('SELECT 1 FROM usuarios WHERE email = :email');
$stmt->execute([':email' => $email]);
if ($stmt->fetchColumn()) {
    error_registro('existe', $nombre, $email);
}

$esPrimero = (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() === 0;

$stmt = $pdo->prepare('INSERT INTO usuarios (nombre, email, password_hash) VALUES (:nombre, :email, :hash)');
$stmt->execute([
    ':nombre' => $nombre,
    ':email'  => $email,
    ':hash'   => password_hash($password, PASSWORD_DEFAULT),
]);
$id = (int) $pdo->lastInsertId();

// El primer usuario se queda con las canciones que existían antes de haber usuarios
if ($esPrimero) {
    $pdo->prepare('UPDATE canciones SET usuario_id = :id WHERE usuario_id IS NULL')->execute([':id' => $id]);
}

iniciar_sesion(['id' => $id, 'nombre' => $nombre]);
header('Location: ../index.php?ok=bienvenido');
exit;
