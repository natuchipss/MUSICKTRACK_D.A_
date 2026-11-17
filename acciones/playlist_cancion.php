<?php
// Agregar o quitar una canción de una playlist
require_once __DIR__ . '/../includes/auth.php';
accion_protegida();

$cancionId  = (int) ($_POST['cancion_id'] ?? 0);
$playlistId = (int) ($_POST['playlist_id'] ?? 0);
$accion     = $_POST['accion'] ?? '';
$pdo        = db();

// La canción y la playlist deben ser del usuario
$stmt = $pdo->prepare('SELECT
        (SELECT COUNT(*) FROM canciones WHERE id = :c AND usuario_id = :u1) +
        (SELECT COUNT(*) FROM playlists WHERE id = :p AND usuario_id = :u2)');
$stmt->execute([':c' => $cancionId, ':p' => $playlistId, ':u1' => usuario_id(), ':u2' => usuario_id()]);

if ((int) $stmt->fetchColumn() === 2) {
    if ($accion === 'agregar') {
        $pdo->prepare('INSERT IGNORE INTO playlist_canciones (playlist_id, cancion_id) VALUES (:p, :c)')
            ->execute([':p' => $playlistId, ':c' => $cancionId]);
    } elseif ($accion === 'quitar') {
        $pdo->prepare('DELETE FROM playlist_canciones WHERE playlist_id = :p AND cancion_id = :c')
            ->execute([':p' => $playlistId, ':c' => $cancionId]);
    }
}

volver($accion === 'agregar' ? 'agregada_playlist' : '', "cancion-$cancionId");
