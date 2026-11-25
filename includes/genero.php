<?php

// Detección del género musical.
// Spotify ya no devuelve géneros a las apps en modo desarrollo (cambios de febrero de 2026),
// así que se consulta la API pública de Deezer (gratis y sin clave): se busca la canción,
// se toma su álbum y se leen los géneros del álbum.

require_once __DIR__ . '/http.php';

const DEEZER_API      = 'https://api.deezer.com';
const DEEZER_SEGUNDOS = 6; // el género es opcional: no se hace esperar mucho al usuario
const DEEZER_RESULTADOS = 5;

const PATRON_LATINO = '/latin|salsa|tropical|cumbia|vallenato|bachata|merengue|regional/';

// Reglas para traducir los géneros de Deezer a los de MusicTrack, en orden de prioridad:
// gana la primera que coincide. [género de MusicTrack, patrón, ¿exige que también sea latino?]
// "Latino" junto a rap, dance o R&B suele ser reggaetón / urbano.
const REGLAS_GENERO = [
    ['reggaeton',   '/reggaet/', false],
    ['reggaeton',   '/rap|hip hop|dance|electro|r&b/', true],
    ['latina',      PATRON_LATINO, false],
    ['hiphop',      '/rap|hip hop|r&b/', false],
    ['rock',        '/rock|metal|alternativ|punk|indie/', false],
    ['electronica', '/electro|dance|house|techno/', false],
    ['jazz',        '/jazz|blues|soul/', false],
    ['pop',         '/pop/', false],
];

/**
 * GET a la API de Deezer; devuelve el JSON decodificado o null si falla.
 *
 * @return array<mixed>|null
 */
function deezer_get(string $url): ?array
{
    $data = http_json_o_null($url, DEEZER_SEGUNDOS);
    // Deezer responde 200 con {"error": {...}} cuando la consulta no es válida
    return $data !== null && !isset($data['error']) ? $data : null;
}

// Pasa a minúsculas y quita tildes para comparar nombres
function normalizar(string $texto): string
{
    $texto = mb_strtolower(trim($texto));
    return strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
}

/**
 * Convierte los géneros de Deezer (ej. ["Rap/Hip Hop", "Latino"]) en una clave de GENEROS.
 *
 * @param list<string> $generosDeezer
 */
function mapear_genero(array $generosDeezer): ?string
{
    $texto    = normalizar(implode(' | ', $generosDeezer));
    $esLatino = (bool) preg_match(PATRON_LATINO, $texto);

    foreach (REGLAS_GENERO as [$genero, $patron, $exigeLatino]) {
        if ((!$exigeLatino || $esLatino) && preg_match($patron, $texto)) {
            return $genero;
        }
    }
    return null;
}

// true si dos nombres de artista coinciden (uno contiene al otro, sin tildes ni mayúsculas)
function mismo_artista(string $nombre, string $buscado): bool
{
    $nombre  = normalizar($nombre);
    $buscado = normalizar($buscado);
    return $nombre !== '' && (str_contains($nombre, $buscado) || str_contains($buscado, $nombre));
}

/**
 * Busca en Deezer y devuelve el ID del álbum del primer resultado cuyo artista coincide
 * (para no tomar el género de otra canción con el mismo nombre).
 * $tipo: 'track' busca la canción y usa su álbum; 'album' busca el álbum directamente.
 */
function buscar_album_deezer(string $titulo, string $artista, string $tipo): ?int
{
    $endpoint = $tipo === 'album' ? 'search/album' : 'search';
    $busqueda = deezer_get(DEEZER_API . "/$endpoint?" . http_build_query([
        'q'     => "$artista $titulo",
        'limit' => DEEZER_RESULTADOS,
    ]));

    foreach ($busqueda['data'] ?? [] as $resultado) {
        if (mismo_artista($resultado['artist']['name'] ?? '', $artista)) {
            $albumId = $tipo === 'album' ? ($resultado['id'] ?? null) : ($resultado['album']['id'] ?? null);
            return $albumId !== null ? (int) $albumId : null;
        }
    }
    return null;
}

/**
 * Devuelve ['genero' => clave|null, 'fuente' => 'Rap/Hip Hop, Latino'] o null si no se encontró.
 *
 * @return array{genero: ?string, fuente: string}|null
 */
function detectar_genero(string $titulo, string $artista, string $tipo = 'track'): ?array
{
    // Spotify une varios artistas con coma: se usa el principal
    $artistaPrincipal = trim(explode(',', $artista)[0]);
    if ($titulo === '' || $artistaPrincipal === '') {
        return null;
    }

    $albumId = buscar_album_deezer($titulo, $artistaPrincipal, $tipo);
    if (!$albumId) {
        return null;
    }

    $album   = deezer_get(DEEZER_API . '/album/' . $albumId);
    $generos = array_column($album['genres']['data'] ?? [], 'name');
    if (!$generos) {
        return null;
    }

    return [
        'genero' => mapear_genero($generos),
        'fuente' => implode(', ', $generos),
    ];
}
