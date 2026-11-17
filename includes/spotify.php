<?php
// Cliente mínimo de la API de Spotify (flujo client credentials, solo en el servidor).
// El Client Secret nunca llega al navegador.

function spotify_token(): string {
    // El token dura una hora: se guarda en un archivo temporal para no pedirlo en cada búsqueda
    $cacheFile = sys_get_temp_dir() . '/spotify_token.json';

    if (is_file($cacheFile)) {
        $cache = json_decode(file_get_contents($cacheFile), true);
        if ($cache && $cache['expires'] > time()) {
            return $cache['token'];
        }
    }

    $cfg = require __DIR__ . '/config.php';
    $ch = curl_init('https://accounts.spotify.com/api/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Basic ' . base64_encode(
                trim($cfg['spotify_client_id']) . ':' . trim($cfg['spotify_client_secret'])
            ),
            'Content-Type: application/x-www-form-urlencoded',
        ],
    ]);
    $respuesta = curl_exec($ch);
    $errorCurl = curl_error($ch);
    $status    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // curl_close() no hace falta desde PHP 8 (y es obsoleta en 8.5)

    if ($respuesta === false) {
        // Típico en XAMPP/Windows: error 60 de certificado (ver guía, sección 10)
        throw new RuntimeException('cURL: ' . $errorCurl);
    }

    $data = json_decode($respuesta, true);
    if (empty($data['access_token'])) {
        throw new RuntimeException("No se pudo obtener el token de Spotify (HTTP $status): $respuesta");
    }

    file_put_contents($cacheFile, json_encode([
        'token'   => $data['access_token'],
        'expires' => time() + $data['expires_in'] - 60,
    ]));

    return $data['access_token'];
}

// GET autenticado a la API de Spotify; lanza excepción si falla
function spotify_get(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . spotify_token()],
    ]);
    $respuesta = curl_exec($ch);
    $errorCurl = curl_error($ch);
    $status    = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($respuesta === false) {
        throw new RuntimeException('cURL: ' . $errorCurl);
    }
    if ($status !== 200) {
        // 401: token inválido o vencido; 403: revisar que el Premium del dueño siga activo
        throw new RuntimeException("Spotify respondió HTTP $status: $respuesta");
    }
    return json_decode($respuesta, true) ?? [];
}

// Convierte una canción o un álbum de Spotify al formato que usa MusicTrack
function spotify_formatear(array $item, string $tipo): array {
    // En una canción la portada está en album.images; en un álbum, en images
    $imagenes = $tipo === 'album' ? ($item['images'] ?? []) : ($item['album']['images'] ?? []);

    return [
        'id'     => $item['id'],
        'tipo'   => $tipo,
        'name'   => $item['name'],
        'artist' => implode(', ', array_column($item['artists'] ?? [], 'name')),
        'image'  => $imagenes[1]['url'] ?? ($imagenes[0]['url'] ?? null),
    ];
}

// Busca canciones (track) o álbumes (album)
function spotify_search(string $q, string $tipo = 'track'): array {
    $tipo = $tipo === 'album' ? 'album' : 'track';

    $data = spotify_get('https://api.spotify.com/v1/search?' . http_build_query([
        'q'     => $q,
        'type'  => $tipo,
        'limit' => 10, // máximo actual en Development Mode
    ]));

    $items = $data[$tipo . 's']['items'] ?? [];
    return array_map(fn($item) => spotify_formatear($item, $tipo), array_filter($items));
}

// Una canción o álbum por su ID (para cuando se pega un link)
function spotify_item(string $tipo, string $id): array {
    $tipo = $tipo === 'album' ? 'album' : 'track';
    return spotify_formatear(spotify_get("https://api.spotify.com/v1/{$tipo}s/$id"), $tipo);
}

// Alternativa sin API: el endpoint público oEmbed da el título y la portada (no el artista)
function spotify_oembed(string $tipo, string $id): ?array {
    $url = "https://open.spotify.com/$tipo/$id";
    $ch = curl_init('https://open.spotify.com/oembed?url=' . urlencode($url));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    $data = json_decode((string) curl_exec($ch), true);

    if (empty($data['title'])) {
        return null;
    }
    return [
        'id'     => $id,
        'tipo'   => $tipo,
        'name'   => $data['title'],
        'artist' => '',
        'image'  => $data['thumbnail_url'] ?? null,
    ];
}

// Extrae tipo e ID de un link o URI de Spotify. Acepta:
//   https://open.spotify.com/track/ID?si=...   https://open.spotify.com/intl-es/album/ID
//   spotify:track:ID
function spotify_parsear_link(string $texto): ?array {
    if (preg_match('#open\.spotify\.com/(?:intl-[a-z]{2}(?:-[a-z]{2})?/)?(track|album)/([A-Za-z0-9]{22})#i', $texto, $m)
        || preg_match('#spotify:(track|album):([A-Za-z0-9]{22})#', $texto, $m)) {
        return ['tipo' => strtolower($m[1]), 'id' => $m[2]];
    }
    return null;
}
