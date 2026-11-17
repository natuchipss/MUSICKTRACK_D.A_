<?php
// Endpoint: sugiere el género de una canción o álbum (según Deezer) y devuelve JSON
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/genero.php';
api_protegida();

$titulo  = trim($_GET['titulo'] ?? '');
$artista = trim($_GET['artista'] ?? '');
$tipo    = ($_GET['tipo'] ?? '') === 'album' ? 'album' : 'track';

if ($titulo === '' || $artista === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan título o artista']);
    exit;
}

// Si no se encuentra, responde genero = null y el usuario lo elige a mano
echo json_encode(detectar_genero($titulo, $artista, $tipo) ?? ['genero' => null, 'fuente' => null]);
