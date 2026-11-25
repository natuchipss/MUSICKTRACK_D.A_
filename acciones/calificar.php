<?php

// Calificar de 1 a 5 estrellas (0 = quitar la calificación)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/validacion.php';
accion_protegida();

$id           = (int) ($_POST['id'] ?? 0);
// Si falta el campo o no es un entero (ej. "abc" o un arreglo) se rechaza: no se toma como 0
$calificacion = filter_var($_POST['calificacion'] ?? null, FILTER_VALIDATE_INT);

if ($id <= 0 || $calificacion === false || !calificacion_valida($calificacion)) {
    volver('incompleto', "cancion-$id");
}

db()->prepare('UPDATE canciones SET calificacion = :c WHERE id = :id AND usuario_id = :uid')
    ->execute([':c' => $calificacion > 0 ? $calificacion : null, ':id' => $id, ':uid' => usuario_id()]);

volver('', "cancion-$id");
