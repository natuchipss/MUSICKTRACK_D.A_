<?php
require_once __DIR__ . '/../includes/auth.php';
accion_protegida(); // solo por POST, con token CSRF y sesión

$id = (int) ($_POST['id'] ?? 0);

if ($id > 0) {
    // También se borra de las playlists (ON DELETE CASCADE)
    db()->prepare('DELETE FROM canciones WHERE id = :id AND usuario_id = :uid')
        ->execute([':id' => $id, ':uid' => usuario_id()]);
}

volver('eliminado');
