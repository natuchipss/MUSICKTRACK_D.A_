<?php

// Cambiar el estado de escucha desde la tarjeta
require_once __DIR__ . '/../includes/auth.php';
accion_protegida();

$id     = (int) ($_POST['id'] ?? 0);
$estado = campo_texto($_POST, 'estado');

// Cláusula de guarda: si el dato no es válido se avisa en lugar de decir "Cambios guardados"
if ($id <= 0 || !isset(ESTADOS[$estado])) {
    volver('incompleto', "cancion-$id");
}

db()->prepare('UPDATE canciones SET estado = :estado WHERE id = :id AND usuario_id = :uid')
    ->execute([':estado' => $estado, ':id' => $id, ':uid' => usuario_id()]);

volver('actualizado', "cancion-$id");
