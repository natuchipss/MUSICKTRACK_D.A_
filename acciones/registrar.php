<?php

// Agregar una canción o álbum desde el formulario "Agregar"
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/validacion.php';
accion_protegida();

$cancion = validar_cancion($_POST) ?? volver('incompleto', 'registrar');

// Datos opcionales de Spotify (se llenan al elegir una canción o álbum en el buscador)
$spotifyId   = campo_texto($_POST, 'spotify_id');
$spotifyTipo = campo_texto($_POST, 'spotify_tipo') === 'album' ? 'album' : 'track';
$portada     = campo_texto($_POST, 'portada_url');

if (!preg_match('/^[A-Za-z0-9]{22}$/', $spotifyId)) {
    $spotifyId = null; // registro manual, sin Spotify
}
if (!preg_match('#^https://#', $portada)) {
    $portada = null;
}

// Si la canción de Spotify ya estaba en la biblioteca del usuario, se actualiza en vez de duplicarla
$sql = "INSERT INTO canciones (usuario_id, spotify_id, spotify_tipo, titulo, artista, tipo,
                               genero, prioridad, estado, notas, portada_url)
        VALUES (:uid, :spotify_id, :spotify_tipo, :titulo, :artista, :tipo,
                :genero, :prioridad, :estado, :notas, :portada)
        ON DUPLICATE KEY UPDATE
            titulo = VALUES(titulo), artista = VALUES(artista), tipo = VALUES(tipo),
            genero = VALUES(genero), prioridad = VALUES(prioridad), estado = VALUES(estado),
            notas = VALUES(notas), portada_url = VALUES(portada_url)";

db()->prepare($sql)->execute([
    ':uid'          => usuario_id(),
    ':spotify_id'   => $spotifyId,
    ':spotify_tipo' => $spotifyTipo,
    ':titulo'       => $cancion['titulo'],
    ':artista'      => $cancion['artista'],
    ':tipo'         => $cancion['tipo'],
    ':genero'       => $cancion['genero'],
    ':prioridad'    => $cancion['prioridad'],
    ':estado'       => $cancion['estado'],
    ':notas'        => $cancion['notas'],
    ':portada'      => $portada !== null ? mb_substr($portada, 0, LARGO_URL_PORTADA) : null,
]);

volver('registrado');
