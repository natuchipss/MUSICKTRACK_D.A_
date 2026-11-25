<?php

// Cliente mínimo de la API de Spotify (flujo client credentials, solo en el servidor).
// El Client Secret nunca llega al navegador.

require_once __DIR__ . '/http.php';

const SPOTIFY_API       = 'https://api.spotify.com/v1';
const SPOTIFY_SEGUNDOS  = 10; // tiempo máximo de espera de cada petición
const SPOTIFY_RESULTADOS = 10; // máximo de resultados por búsqueda en Development Mode
const TOKEN_MARGEN      = 60; // el token se renueva un minuto antes de vencer

function spotify_token(): string
{
    // El token dura una hora: se guarda en un archivo temporal para no pedirlo en cada búsqueda
    $cacheFile = sys_get_temp_dir() . '/spotify_token.json';

    if (is_file($cacheFile)) {
        $cache = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($cache) && $cache['expires'] > time()) {
            return $cache['token'];
        }
    }

    $cfg = require __DIR__ . '/config.php';
    $credenciales = base64_encode(trim($cfg['spotify_client_id']) . ':' . trim($cfg['spotify_client_secret']));

    $r = http_solicitud(
        'https://accounts.spotify.com/api/token',
        ["Authorization: Basic $credenciales", 'Content-Type: application/x-www-form-urlencoded'],
        'grant_type=client_credentials',
        SPOTIFY_SEGUNDOS
    );

    $data = json_decode($r['cuerpo'], true);
    if (empty($data['access_token'])) {
        throw new RuntimeException("No se pudo obtener el token de Spotify (HTTP {$r['status']}): {$r['cuerpo']}");
    }

    file_put_contents($cacheFile, json_encode([
        'token'   => $data['access_token'],
        'expires' => time() + $data['expires_in'] - TOKEN_MARGEN,
    ]));

    return $data['access_token'];
}

/**
 * GET autenticado a la API de Spotify; lanza excepción si falla.
 *
 * @return array<string, mixed>
 */
function spotify_get(string $url): array
{
    $r = http_solicitud($url, ['Authorization: Bearer ' . spotify_token()], null, SPOTIFY_SEGUNDOS);

    if ($r['status'] !== 200) {
        // 401: token inválido o vencido; 403: revisar que el Premium del dueño siga activo
        throw new RuntimeException("Spotify respondió HTTP {$r['status']}: {$r['cuerpo']}");
    }
    return json_decode($r['cuerpo'], true) ?? [];
}

/**
 * Convierte una canción o un álbum de Spotify al formato que usa MusicTrack.
 *
 * @param array<string, mixed> $item
 * @return array{id: string, tipo: string, name: string, artist: string, image: ?string}
 */
function spotify_formatear(array $item, string $tipo): array
{
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

/**
 * Busca canciones (track) o álbumes (album).
 *
 * @return list<array{id: string, tipo: string, name: string, artist: string, image: ?string}>
 */
function spotify_search(string $consulta, string $tipo = 'track'): array
{
    $tipo = $tipo === 'album' ? 'album' : 'track';

    $data = spotify_get(SPOTIFY_API . '/search?' . http_build_query([
        'q'     => $consulta,
        'type'  => $tipo,
        'limit' => SPOTIFY_RESULTADOS,
    ]));

    $items = $data[$tipo . 's']['items'] ?? [];
    return array_values(array_map(fn($item) => spotify_formatear($item, $tipo), array_filter($items)));
}

/**
 * Una canción o álbum por su ID (para cuando se pega un link).
 *
 * @return array{id: string, tipo: string, name: string, artist: string, image: ?string}
 */
function spotify_item(string $tipo, string $spotifyId): array
{
    $tipo = $tipo === 'album' ? 'album' : 'track';
    return spotify_formatear(spotify_get(SPOTIFY_API . "/{$tipo}s/$spotifyId"), $tipo);
}

/**
 * Alternativa sin API: el endpoint público oEmbed da el título y la portada (no el artista).
 *
 * @return array{id: string, tipo: string, name: string, artist: string, image: ?string}|null
 */
function spotify_oembed(string $tipo, string $spotifyId): ?array
{
    $url  = "https://open.spotify.com/$tipo/$spotifyId";
    $data = http_json_o_null('https://open.spotify.com/oembed?url=' . urlencode($url), SPOTIFY_SEGUNDOS);

    if (empty($data['title'])) {
        return null;
    }
    return [
        'id'     => $spotifyId,
        'tipo'   => $tipo,
        'name'   => $data['title'],
        'artist' => '',
        'image'  => $data['thumbnail_url'] ?? null,
    ];
}

/**
 * Extrae tipo e ID de un link o URI de Spotify. Acepta:
 *   https://open.spotify.com/track/ID?si=...   https://open.spotify.com/intl-es/album/ID
 *   spotify:track:ID
 *
 * @return array{tipo: string, id: string}|null
 */
function spotify_parsear_link(string $texto): ?array
{
    $patronWeb = '#open\.spotify\.com/(?:intl-[a-z]{2}(?:-[a-z]{2})?/)?(track|album)/([A-Za-z0-9]{22})#i';
    $patronUri = '#spotify:(track|album):([A-Za-z0-9]{22})#';

    if (preg_match($patronWeb, $texto, $partes) || preg_match($patronUri, $texto, $partes)) {
        return ['tipo' => strtolower($partes[1]), 'id' => $partes[2]];
    }
    return null;
}
