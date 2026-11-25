<?php

// Catálogos y funciones de apoyo que usan las vistas y las acciones.

// Valores permitidos (deben coincidir con los ENUM de la tabla `canciones`)
const TIPOS = [
    'cancion' => 'Canción',
    'album'   => 'Álbum',
];

const ESTADOS = [
    'pendiente'  => 'Pendiente',
    'escuchando' => 'Escuchando',
    'escuchado'  => 'Escuchado',
];

const PRIORIDADES = [
    'alta'  => 'Alta',
    'media' => 'Media',
    'baja'  => 'Baja',
];

// El género es VARCHAR(50): se pueden agregar más sin tocar la base de datos
const GENEROS = [
    'reggaeton'   => 'Reggaetón / Urbano',
    'pop'         => 'Pop',
    'rock'        => 'Rock',
    'hiphop'      => 'Hip Hop / Rap',
    'electronica' => 'Electrónica',
    'latina'      => 'Latina / Tropical',
    'jazz'        => 'Jazz / Blues',
    'otro'        => 'Otro',
];

// Orden de la biblioteca: etiqueta visible y su ORDER BY (nunca se arma SQL con texto del usuario)
const ORDENES = [
    'recientes'    => 'Más recientes',
    'antiguos'     => 'Más antiguos',
    'titulo'       => 'Título (A-Z)',
    'artista'      => 'Artista (A-Z)',
    'prioridad'    => 'Prioridad',
    'calificacion' => 'Mejor calificadas',
];

const ORDEN_SQL = [
    'recientes'    => 'c.id DESC',
    'antiguos'     => 'c.id ASC',
    'titulo'       => 'c.titulo ASC',
    'artista'      => 'c.artista ASC, c.titulo ASC',
    'prioridad'    => "FIELD(c.prioridad, 'alta', 'media', 'baja'), c.id DESC",
    'calificacion' => 'c.calificacion IS NULL, c.calificacion DESC, c.id DESC',
];

// Canciones por página en la biblioteca
const POR_PAGINA = 9;

// Límites de los datos de una canción (coinciden con la tabla `canciones`)
const LARGO_TITULO       = 150; // VARCHAR(150) de titulo y artista
const LARGO_URL_PORTADA  = 500; // VARCHAR(500) de portada_url
const CALIFICACION_MAXIMA = 5;
const LARGO_BUSQUEDA      = 100; // caracteres del buscador de la biblioteca

// Clase CSS de cada estado y prioridad (badges)
const CLASE_ESTADO    = ['pendiente' => 'pending', 'escuchando' => 'listening', 'escuchado' => 'finished'];
const CLASE_PRIORIDAD = ['alta' => 'high', 'media' => 'medium', 'baja' => 'low'];

// Parámetros de la biblioteca que se conservan al volver de una acción
const PARAMS_BIBLIOTECA = ['q', 'estado', 'orden', 'playlist', 'pagina'];

/**
 * Texto de un campo de $_POST o $_GET, sin espacios a los lados.
 * Si llega un arreglo (ej. estado[]=x) se trata como vacío en vez de provocar un error.
 *
 * @param array<string, mixed> $datos
 */
function campo_texto(array $datos, string $campo): string
{
    $valor = $datos[$campo] ?? '';
    return is_scalar($valor) ? trim((string) $valor) : '';
}

/**
 * Igual que campo_texto() pero sin quitar espacios (contraseñas, consultas ya armadas).
 *
 * @param array<string, mixed> $datos
 */
function campo_crudo(array $datos, string $campo): string
{
    $valor = $datos[$campo] ?? '';
    return is_scalar($valor) ? (string) $valor : '';
}

// Escapa texto para imprimirlo en HTML (evita XSS)
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Devuelve la etiqueta bonita de un catálogo, o el valor tal cual si no existe.
 *
 * @param array<string, string> $catalogo
 */
function etiqueta(array $catalogo, ?string $valor): string
{
    return $catalogo[$valor] ?? ucfirst((string) $valor);
}

// Redirige a la página principal con un mensaje (?ok=...) conservando filtros y página.
// Los formularios envían el campo "volver" con la consulta actual (ej. "estado=pendiente&pagina=2").
function volver(string $mensaje = '', string $ancla = 'biblioteca'): never
{
    parse_str(campo_crudo($_POST, 'volver'), $params);
    $params = array_intersect_key($params, array_flip(PARAMS_BIBLIOTECA));
    if ($mensaje !== '') {
        $params['ok'] = $mensaje;
    }
    $ancla = preg_replace('/[^a-z0-9-]/', '', $ancla);
    $consulta = http_build_query($params);

    header('Location: ../index.php' . ($consulta !== '' ? "?$consulta" : '') . "#$ancla");
    exit;
}

/**
 * URL de la biblioteca con los filtros actuales, cambiando algunos (para paginación y enlaces).
 *
 * @param array<string, int|string> $filtros
 * @param array<string, int|string> $cambios
 */
function url_biblioteca(array $filtros, array $cambios = []): string
{
    $params = array_merge($filtros, $cambios);
    $params = array_filter(
        $params,
        fn($v, $k) => in_array($k, PARAMS_BIBLIOTECA, true)
        && $v !== '' && $v !== 0 && !($k === 'pagina' && $v == 1) && !($k === 'orden' && $v === 'recientes'),
        ARRAY_FILTER_USE_BOTH
    );
    $consulta = http_build_query($params);
    return 'index.php' . ($consulta !== '' ? "?$consulta" : '') . '#biblioteca';
}
