<?php

// Endpoint: recibe un link de Spotify pegado por el usuario y devuelve la canción o el álbum.
// Primero usa la API; si falla (por ejemplo, venció el Premium), usa el oEmbed público.
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/spotify.php';
api_protegida();

$link = spotify_parsear_link(campo_texto($_GET, 'url'));

if (!$link) {
    http_response_code(400);
    echo json_encode(['error' => 'El link no es de una canción o álbum de Spotify']);
    exit;
}

try {
    echo json_encode(spotify_item($link['tipo'], $link['id']));
} catch (Throwable $e) {
    error_log('Link de Spotify (API): ' . $e->getMessage());

    $item = spotify_oembed($link['tipo'], $link['id']);
    if ($item) {
        echo json_encode($item); // sin artista: el usuario lo escribe
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'No se encontró en Spotify']);
    }
}
