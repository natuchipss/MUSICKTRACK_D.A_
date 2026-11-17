<?php
require_once __DIR__ . '/includes/auth.php';

if (usuario_actual()) {
    header('Location: index.php');
    exit;
}

$errores = [
    'nombre'    => 'El nombre debe tener entre 2 y 80 caracteres.',
    'email'     => 'Escribe un correo válido.',
    'password'  => 'La contraseña debe tener al menos 8 caracteres.',
    'confirmar' => 'Las contraseñas no coinciden.',
    'existe'    => 'Ya hay una cuenta con ese correo.',
];

$tituloPagina = 'Crear cuenta';
$modo         = 'registro';
$error        = $errores[$_GET['error'] ?? ''] ?? null;
$aviso        = null;
$viejoNombre  = $_SESSION['viejo_nombre'] ?? '';
$viejoEmail   = $_SESSION['viejo_email'] ?? '';
unset($_SESSION['viejo_nombre'], $_SESSION['viejo_email']);

require __DIR__ . '/vistas/auth.php';
