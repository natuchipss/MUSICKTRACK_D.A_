<?php
require_once __DIR__ . '/../includes/auth.php';
accion_protegida();

$nombre = trim($_POST['nombre'] ?? '');

if ($nombre === '' || mb_strlen($nombre) > 80) {
    volver('playlist_invalida');
}

try {
    db()->prepare('INSERT INTO playlists (usuario_id, nombre) VALUES (:uid, :nombre)')
        ->execute([':uid' => usuario_id(), ':nombre' => $nombre]);
} catch (PDOException $e) {
    // 23000 = el usuario ya tiene una playlist con ese nombre (UNIQUE)
    if ($e->getCode() === '23000') {
        volver('playlist_existe');
    }
    throw $e;
}

volver('playlist_creada');
