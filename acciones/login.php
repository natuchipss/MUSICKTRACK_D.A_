<?php
require_once __DIR__ . '/../includes/auth.php';
accion_protegida(false);

$email    = mb_strtolower(trim($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

$stmt = db()->prepare('SELECT id, nombre, password_hash FROM usuarios WHERE email = :email');
$stmt->execute([':email' => $email]);
$usuario = $stmt->fetch();

// Mismo mensaje si el correo no existe o la contraseña está mal (no revela qué falló)
if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
    $_SESSION['viejo_email'] = $email;
    header('Location: ../login.php?error=credenciales');
    exit;
}

iniciar_sesion($usuario);
header('Location: ../index.php?ok=bienvenido');
exit;
