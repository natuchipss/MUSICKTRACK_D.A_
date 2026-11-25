<?php

// Consultas de la página principal. Todas filtran por usuario_id: cada quien ve solo lo suyo.

/**
 * Lee y valida los filtros de la biblioteca que llegan por GET.
 *
 * @param array<string, mixed> $get
 * @return array{q: string, estado: string, orden: string, playlist: int, pagina: int}
 */
function leer_filtros(array $get): array
{
    $estado = campo_texto($get, 'estado');
    $orden  = campo_texto($get, 'orden');

    return [
        'q'        => mb_substr(campo_texto($get, 'q'), 0, LARGO_BUSQUEDA),
        'estado'   => isset(ESTADOS[$estado]) ? $estado : '',
        'orden'    => isset(ORDENES[$orden]) ? $orden : 'recientes',
        'playlist' => max(0, (int) campo_texto($get, 'playlist')),
        'pagina'   => max(1, (int) campo_texto($get, 'pagina')),
    ];
}

/**
 * Condición de búsqueda por título, artista o nombre visible del género
 * (ej. "urbano" encuentra reggaeton). Los comodines de LIKE se escapan.
 *
 * @return array{0: string, 1: array<string, string>} [SQL, parámetros]
 */
function condicion_busqueda(string $texto): array
{
    $like        = '%' . addcslashes($texto, '%_\\') . '%';
    $params      = [':q1' => $like, ':q2' => $like];
    $condiciones = ['c.titulo LIKE :q1', 'c.artista LIKE :q2'];

    $claves = array_keys(array_filter(GENEROS, fn($nombre) => mb_stripos($nombre, $texto) !== false));
    if ($claves) {
        $marcas = [];
        foreach ($claves as $i => $clave) {
            $marcas[]         = ":g$i";
            $params[":g$i"] = $clave;
        }
        $condiciones[] = 'c.genero IN (' . implode(', ', $marcas) . ')';
    }

    return ['(' . implode(' OR ', $condiciones) . ')', $params];
}

/**
 * WHERE de la biblioteca según los filtros. Todo valor del usuario va como parámetro.
 *
 * @param array{q: string, estado: string, playlist: int} $filtros
 * @return array{0: string, 1: array<string, int|string>} [SQL, parámetros]
 */
function filtro_biblioteca(int $uid, array $filtros): array
{
    $where  = ['c.usuario_id = :uid'];
    $params = [':uid' => $uid];

    if ($filtros['q'] !== '') {
        [$where[], $paramsBusqueda] = condicion_busqueda($filtros['q']);
        $params += $paramsBusqueda;
    }

    if ($filtros['estado'] !== '') {
        $where[]           = 'c.estado = :estado';
        $params[':estado'] = $filtros['estado'];
    }

    if ($filtros['playlist'] > 0) {
        $where[] = 'c.id IN (SELECT pc.cancion_id FROM playlist_canciones pc
                             JOIN playlists p ON p.id = pc.playlist_id
                             WHERE p.id = :pl AND p.usuario_id = :uid2)';
        $params[':pl']   = $filtros['playlist'];
        $params[':uid2'] = $uid;
    }

    return [implode(' AND ', $where), $params];
}

/**
 * Página que se muestra: si la pedida no existe se usa la última.
 *
 * @return array{pagina: int, paginas: int, offset: int}
 */
function paginar(int $total, int $paginaPedida): array
{
    $paginas = max(1, (int) ceil($total / POR_PAGINA));
    $pagina  = min(max(1, $paginaPedida), $paginas);

    return ['pagina' => $pagina, 'paginas' => $paginas, 'offset' => ($pagina - 1) * POR_PAGINA];
}

/**
 * Canciones de la página pedida, con los filtros y el orden aplicados en el servidor.
 *
 * @param array{q: string, estado: string, orden: string, playlist: int, pagina: int} $filtros
 * @return array{items: list<array<string, mixed>>, total: int, paginas: int, pagina: int}
 */
function consultar_biblioteca(PDO $pdo, int $uid, array $filtros): array
{
    [$sqlWhere, $params] = filtro_biblioteca($uid, $filtros);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM canciones c WHERE $sqlWhere");
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    $pag   = paginar($total, $filtros['pagina']);
    $orden = ORDEN_SQL[$filtros['orden']];

    // ORDER BY sale de una lista fija y LIMIT/OFFSET son enteros calculados aquí, no texto del usuario
    $stmt = $pdo->prepare("SELECT c.* FROM canciones c WHERE $sqlWhere ORDER BY $orden
                           LIMIT " . POR_PAGINA . " OFFSET {$pag['offset']}");
    $stmt->execute($params);

    return ['items' => $stmt->fetchAll(), 'total' => $total, 'paginas' => $pag['paginas'], 'pagina' => $pag['pagina']];
}

/**
 * Playlists del usuario con su cantidad de canciones.
 *
 * @return list<array<string, mixed>> Filas con id, nombre y total
 */
function consultar_playlists(PDO $pdo, int $uid): array
{
    $stmt = $pdo->prepare('SELECT p.id, p.nombre, COUNT(pc.cancion_id) AS total
                           FROM playlists p
                           LEFT JOIN playlist_canciones pc ON pc.playlist_id = p.id
                           WHERE p.usuario_id = :uid
                           GROUP BY p.id, p.nombre
                           ORDER BY p.nombre');
    $stmt->execute([':uid' => $uid]);
    return $stmt->fetchAll();
}

/**
 * Para cada canción mostrada, sus playlists: [cancion_id => [playlist_id => nombre]].
 *
 * @param list<int|string> $idsCanciones
 * @return array<int, array<int, string>>
 */
function playlists_por_cancion(PDO $pdo, int $uid, array $idsCanciones): array
{
    if (!$idsCanciones) {
        return [];
    }
    $marcas = implode(', ', array_fill(0, count($idsCanciones), '?'));
    $stmt = $pdo->prepare("SELECT pc.cancion_id, p.id, p.nombre
                           FROM playlist_canciones pc
                           JOIN playlists p ON p.id = pc.playlist_id
                           WHERE p.usuario_id = ? AND pc.cancion_id IN ($marcas)
                           ORDER BY p.nombre");
    $stmt->execute(array_merge([$uid], array_map('intval', $idsCanciones)));

    $mapa = [];
    foreach ($stmt as $fila) {
        $mapa[$fila['cancion_id']][$fila['id']] = $fila['nombre'];
    }
    return $mapa;
}

/**
 * Números para "Mi progreso" y las estadísticas.
 *
 * @return array{conteos: array<string, int>, total: int, porcentaje: int,
 *               porGenero: list<array<string, mixed>>, topArtistas: list<array<string, mixed>>,
 *               promedio: ?float, calificadas: int}
 */
function estadisticas(PDO $pdo, int $uid): array
{
    $conteos = array_fill_keys(array_keys(ESTADOS), 0);
    $stmt = $pdo->prepare('SELECT estado, COUNT(*) AS total FROM canciones WHERE usuario_id = :uid GROUP BY estado');
    $stmt->execute([':uid' => $uid]);
    foreach ($stmt as $fila) {
        $conteos[$fila['estado']] = (int) $fila['total'];
    }
    $total = array_sum($conteos);

    $stmt = $pdo->prepare('SELECT genero, COUNT(*) AS total FROM canciones WHERE usuario_id = :uid
                           GROUP BY genero ORDER BY total DESC');
    $stmt->execute([':uid' => $uid]);
    $porGenero = $stmt->fetchAll();

    // Se agrupa por el artista principal ("Feid, ATL Jacob" cuenta para "Feid")
    $stmt = $pdo->prepare("SELECT TRIM(SUBSTRING_INDEX(artista, ',', 1)) AS artista, COUNT(*) AS total
                           FROM canciones WHERE usuario_id = :uid
                           GROUP BY TRIM(SUBSTRING_INDEX(artista, ',', 1))
                           ORDER BY total DESC, artista LIMIT 5");
    $stmt->execute([':uid' => $uid]);
    $topArtistas = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT AVG(calificacion) AS promedio, COUNT(calificacion) AS calificadas
                           FROM canciones WHERE usuario_id = :uid');
    $stmt->execute([':uid' => $uid]);
    $calif = $stmt->fetch();

    return [
        'conteos'     => $conteos,
        'total'       => $total,
        'porcentaje'  => $total > 0 ? (int) round($conteos['escuchado'] * 100 / $total) : 0,
        'porGenero'   => $porGenero,
        'topArtistas' => $topArtistas,
        'promedio'    => $calif['promedio'] !== null ? round((float) $calif['promedio'], 1) : null,
        'calificadas' => (int) $calif['calificadas'],
    ];
}
