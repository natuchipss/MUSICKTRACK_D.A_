<?php

// Endpoint: guarda en MySQL una canción o álbum elegido de los resultados de Spotify (guardado rápido)
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/genero.php';
api_protegida(); // sesión + token CSRF en la cabecera X-CSRF-Token

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$in = json_decode(file_get_contents('php://input'), true);

// El ID de Spotify son 22 caracteres alfanuméricos
if (!isset($in['id']) || !is_string($in['id']) || !preg_match('/^[A-Za-z0-9]{22}$/', $in['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

$titulo  = trim((string) ($in['name'] ?? ''));
$artista = trim((string) ($in['artist'] ?? ''));
if ($titulo === '' || $artista === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan título o artista']);
    exit;
}

$spotifyTipo = ($in['tipo'] ?? '') === 'album' ? 'album' : 'track';

// Solo se aceptan portadas https (las de Spotify vienen de i.scdn.co)
$portada = $in['image'] ?? null;
if (!is_string($portada) || !preg_match('#^https://#', $portada)) {
    $portada = null;
}

// El género se detecta con Deezer (Spotify ya no lo da); si no se encuentra queda 'otro'.
// Prioridad y estado toman valores por defecto; se pueden cambiar con "Editar".
$genero = detectar_genero($titulo, $artista, $spotifyTipo)['genero'] ?? null;

$stmt = db()->prepare(
    "INSERT INTO canciones (usuario_id, spotify_id, spotify_tipo, titulo, artista, tipo,
                            genero, prioridad, estado, portada_url)
     VALUES (:uid, :id, :stipo, :titulo, :artista, :tipo, :genero, 'media', 'pendiente', :portada)
     ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), artista = VALUES(artista), portada_url = VALUES(portada_url)"
);
$stmt->execute([
    ':uid'     => usuario_id(),
    ':id'      => $in['id'],
    ':stipo'   => $spotifyTipo,
    ':titulo'  => mb_substr($titulo, 0, 150),
    ':artista' => mb_substr($artista, 0, 150),
    ':tipo'    => $spotifyTipo === 'album' ? 'album' : 'cancion',
    ':genero'  => $genero ?? 'otro',
    ':portada' => $portada !== null ? mb_substr($portada, 0, 500) : null,
]);

echo json_encode(['ok' => true]);
