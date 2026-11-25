<?php
// Detección del género musical.
// Spotify ya no devuelve géneros a las apps en modo desarrollo (cambios de febrero de 2026),
// así que se consulta la API pública de Deezer (gratis y sin clave): se busca la canción,
// se toma su álbum y se leen los géneros del álbum.

require_once __DIR__ . '/http.php';

const DEEZER_SEGUNDOS = 6; // el género es opcional: no se hace esperar mucho al usuario

// GET a la API de Deezer; devuelve el JSON decodificado o null si falla
function deezer_get(string $url): ?array {
    $data = http_json_o_null($url, DEEZER_SEGUNDOS);
    // Deezer responde 200 con {"error": {...}} cuando la consulta no es válida
    return $data !== null && !isset($data['error']) ? $data : null;
}

// Pasa a minúsculas y quita tildes para comparar nombres
function normalizar(string $texto): string {
    $texto = mb_strtolower(trim($texto));
    return strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
}

// Convierte los géneros de Deezer (ej. ["Rap/Hip Hop", "Latino"]) en una clave de GENEROS
function mapear_genero(array $generosDeezer): ?string {
    $texto = normalizar(implode(' | ', $generosDeezer));

    $esLatino = preg_match('/latin|salsa|tropical|cumbia|vallenato|bachata|merengue|regional/', $texto);

    if (str_contains($texto, 'reggaet')) return 'reggaeton';
    // "Latino" junto a rap, dance o R&B suele ser reggaetón / urbano
    if ($esLatino && preg_match('/rap|hip hop|dance|electro|r&b/', $texto)) return 'reggaeton';
    if ($esLatino) return 'latina';
    if (preg_match('/rap|hip hop|r&b/', $texto)) return 'hiphop';
    if (preg_match('/rock|metal|alternativ|punk|indie/', $texto)) return 'rock';
    if (preg_match('/electro|dance|house|techno/', $texto)) return 'electronica';
    if (preg_match('/jazz|blues|soul/', $texto)) return 'jazz';
    if (str_contains($texto, 'pop')) return 'pop';

    return null;
}

// Devuelve ['genero' => clave|null, 'fuente' => 'Rap/Hip Hop, Latino'] o null si no se encontró
// $tipo: 'track' busca la canción y usa su álbum; 'album' busca el álbum directamente
function detectar_genero(string $titulo, string $artista, string $tipo = 'track'): ?array {
    // Spotify une varios artistas con coma: se usa el principal
    $artistaPrincipal = trim(explode(',', $artista)[0]);
    if ($titulo === '' || $artistaPrincipal === '') {
        return null;
    }

    $endpoint = $tipo === 'album' ? 'search/album' : 'search';
    $busqueda = deezer_get("https://api.deezer.com/$endpoint?" . http_build_query([
        'q'     => "$artistaPrincipal $titulo",
        'limit' => 5,
    ]));

    // Se toma el primer resultado cuyo artista coincide, para no tomar el género de otra canción
    $albumId = null;
    foreach ($busqueda['data'] ?? [] as $resultado) {
        $nombre = normalizar($resultado['artist']['name'] ?? '');
        $buscado = normalizar($artistaPrincipal);
        if ($nombre !== '' && (str_contains($nombre, $buscado) || str_contains($buscado, $nombre))) {
            $albumId = $tipo === 'album' ? ($resultado['id'] ?? null) : ($resultado['album']['id'] ?? null);
            break;
        }
    }
    if (!$albumId) {
        return null;
    }

    $album = deezer_get('https://api.deezer.com/album/' . (int) $albumId);
    $generos = array_column($album['genres']['data'] ?? [], 'name');
    if (!$generos) {
        return null;
    }

    return [
        'genero' => mapear_genero($generos),
        'fuente' => implode(', ', $generos),
    ];
}
