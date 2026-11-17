<?php
// Borra la playlist (las canciones siguen en la biblioteca)
require_once __DIR__ . '/../includes/auth.php';
accion_protegida();

$id = (int) ($_POST['id'] ?? 0);

db()->prepare('DELETE FROM playlists WHERE id = :id AND usuario_id = :uid')
    ->execute([':id' => $id, ':uid' => usuario_id()]);

// Si se estaba viendo esa playlist, se vuelve a "todas"
$_POST['volver'] = preg_replace('/(^|&)playlist=\d+/', '', (string) ($_POST['volver'] ?? ''));
volver('playlist_eliminada');
