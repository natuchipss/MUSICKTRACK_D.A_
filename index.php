<?php
// Página principal: consulta los datos del usuario y arma la página con las vistas de /vistas
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/consultas.php';

requerir_login();

$pdo     = db();
$uid     = usuario_id();
$usuario = usuario_actual();

// Filtros, orden y página de la biblioteca (?q=&estado=&orden=&playlist=&pagina=)
$filtros    = leer_filtros($_GET);
$biblioteca = consultar_biblioteca($pdo, $uid, $filtros);
$filtros['pagina'] = $biblioteca['pagina'];

$playlists    = consultar_playlists($pdo, $uid);
$plPorCancion = playlists_por_cancion($pdo, $uid, array_column($biblioteca['items'], 'id'));
$playlistActiva = null;
foreach ($playlists as $pl) {
    if ((int) $pl['id'] === $filtros['playlist']) {
        $playlistActiva = $pl;
    }
}

// Progreso y estadísticas
$stats      = estadisticas($pdo, $uid);
$conteos    = $stats['conteos'];
$total      = $stats['total'];
$porcentaje = $stats['porcentaje'];

// Campos ocultos de todos los formularios POST: token CSRF + filtros actuales (para volver igual)
$consultaActual = parse_url(url_biblioteca($filtros), PHP_URL_QUERY) ?? '';
$camposPost     = csrf_campo() . '<input type="hidden" name="volver" value="' . e($consultaActual) . '">';

// Mensaje después de una acción (?ok=...)
$avisos = [
    'bienvenido'         => ['ok', 'Hola, ' . $usuario['nombre']],
    'registrado'         => ['ok', 'Guardado en tu biblioteca'],
    'actualizado'        => ['ok', 'Cambios guardados'],
    'eliminado'          => ['ok', 'Eliminado de tu biblioteca'],
    'incompleto'         => ['error', 'Revisa los datos: falta un campo o hay un valor no válido'],
    'playlist_creada'    => ['ok', 'Playlist creada'],
    'playlist_eliminada' => ['ok', 'Playlist eliminada'],
    'playlist_existe'    => ['error', 'Ya tienes una playlist con ese nombre'],
    'playlist_invalida'  => ['error', 'Escribe un nombre de hasta 80 caracteres'],
    'agregada_playlist'  => ['ok', 'Agregada a la playlist'],
];
$aviso = $avisos[campo_texto($_GET, 'ok')] ?? null;

require __DIR__ . '/vistas/encabezado.php';
?>
    <main>
        <?php
        require __DIR__ . '/vistas/inicio.php';
        require __DIR__ . '/vistas/progreso.php';
        require __DIR__ . '/vistas/biblioteca.php';
        require __DIR__ . '/vistas/formulario.php';
        ?>
    </main>
<?php
require __DIR__ . '/vistas/modal_editar.php';
require __DIR__ . '/vistas/pie.php';
