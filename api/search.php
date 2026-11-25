<?php

// Endpoint: busca canciones (tipo=track) o álbumes (tipo=album) en Spotify y devuelve JSON
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/spotify.php';
api_protegida(); // solo usuarios con sesión (protege la cuota de la app de Spotify)

$q    = campo_texto($_GET, 'q');
$tipo = campo_texto($_GET, 'tipo') === 'album' ? 'album' : 'track';

if (mb_strlen($q) < 2) {
    echo '[]';
    exit;
}

try {
    echo json_encode(spotify_search($q, $tipo));
} catch (Throwable $e) {
    // El detalle (status HTTP, error de cURL) queda en el log del servidor
    error_log('Búsqueda en Spotify: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error al buscar en Spotify']);
}
