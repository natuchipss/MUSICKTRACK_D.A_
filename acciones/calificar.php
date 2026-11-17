<?php
// Calificar de 1 a 5 estrellas (0 = quitar la calificación)
require_once __DIR__ . '/../includes/auth.php';
accion_protegida();

$id           = (int) ($_POST['id'] ?? 0);
$calificacion = (int) ($_POST['calificacion'] ?? -1);

if ($id > 0 && $calificacion >= 0 && $calificacion <= 5) {
    db()->prepare('UPDATE canciones SET calificacion = :c WHERE id = :id AND usuario_id = :uid')
        ->execute([':c' => $calificacion > 0 ? $calificacion : null, ':id' => $id, ':uid' => usuario_id()]);
}

volver('', "cancion-$id");
