<?php
require_once __DIR__ . '/../includes/auth.php';
accion_protegida();

$titulo    = trim($_POST['titulo'] ?? '');
$artista   = trim($_POST['artista'] ?? '');
$tipo      = $_POST['tipo'] ?? '';
$genero    = $_POST['genero'] ?? '';
$prioridad = $_POST['prioridad'] ?? '';
$estado    = $_POST['estado'] ?? '';
$notas     = trim($_POST['notas'] ?? '');

// Datos opcionales de Spotify (se llenan al elegir una canción o álbum en el buscador)
$spotifyId   = trim($_POST['spotify_id'] ?? '');
$spotifyTipo = ($_POST['spotify_tipo'] ?? '') === 'album' ? 'album' : 'track';
$portada     = trim($_POST['portada_url'] ?? '');

if (!preg_match('/^[A-Za-z0-9]{22}$/', $spotifyId)) {
    $spotifyId = null; // registro manual, sin Spotify
}
if (!preg_match('#^https://#', $portada)) {
    $portada = null;
}

$valido = $titulo !== '' && $artista !== ''
    && isset(TIPOS[$tipo], GENEROS[$genero], PRIORIDADES[$prioridad], ESTADOS[$estado]);

if (!$valido) {
    volver('incompleto', 'registrar');
}

// Si la canción de Spotify ya estaba en la biblioteca del usuario, se actualiza en vez de duplicarla
$sql = "INSERT INTO canciones (usuario_id, spotify_id, spotify_tipo, titulo, artista, tipo, genero, prioridad, estado, notas, portada_url)
        VALUES (:uid, :spotify_id, :spotify_tipo, :titulo, :artista, :tipo, :genero, :prioridad, :estado, :notas, :portada)
        ON DUPLICATE KEY UPDATE
            titulo = VALUES(titulo), artista = VALUES(artista), tipo = VALUES(tipo),
            genero = VALUES(genero), prioridad = VALUES(prioridad), estado = VALUES(estado),
            notas = VALUES(notas), portada_url = VALUES(portada_url)";

db()->prepare($sql)->execute([
    ':uid'          => usuario_id(),
    ':spotify_id'   => $spotifyId,
    ':spotify_tipo' => $spotifyTipo,
    ':titulo'       => mb_substr($titulo, 0, 150),
    ':artista'      => mb_substr($artista, 0, 150),
    ':tipo'         => $tipo,
    ':genero'       => $genero,
    ':prioridad'    => $prioridad,
    ':estado'       => $estado,
    ':notas'        => $notas !== '' ? $notas : null,
    ':portada'      => $portada !== null ? mb_substr($portada, 0, 500) : null,
]);

volver('registrado');
