<?php

require_once __DIR__ . '/includes/auth.php';

if (usuario_actual()) {
    header('Location: index.php');
    exit;
}

$errores = ['credenciales' => 'Correo o contraseña incorrectos.'];
$avisos  = ['salir' => 'Cerraste sesión. ¡Vuelve pronto!'];

$tituloPagina = 'Iniciar sesión';
$modo         = 'login';
$error        = $errores[$_GET['error'] ?? ''] ?? null;
$aviso        = $avisos[$_GET['ok'] ?? ''] ?? null;
$viejoEmail   = $_SESSION['viejo_email'] ?? '';
unset($_SESSION['viejo_email']);

require __DIR__ . '/vistas/auth.php';
