<?php
// Consultas de la página principal. Todas filtran por usuario_id: cada quien ve solo lo suyo.

// Lee y valida los filtros de la biblioteca que llegan por GET
function leer_filtros(array $get): array {
    return [
        'q'        => mb_substr(trim((string) ($get['q'] ?? '')), 0, 100),
        'estado'   => isset(ESTADOS[$get['estado'] ?? '']) ? $get['estado'] : '',
        'orden'    => isset(ORDENES[$get['orden'] ?? '']) ? $get['orden'] : 'recientes',
        'playlist' => max(0, (int) ($get['playlist'] ?? 0)),
        'pagina'   => max(1, (int) ($get['pagina'] ?? 1)),
    ];
}

// Devuelve ['items' => [...], 'total' => n, 'paginas' => n, 'pagina' => n]
function consultar_biblioteca(PDO $pdo, int $uid, array $f): array {
    $where  = ['c.usuario_id = :uid'];
    $params = [':uid' => $uid];

    if ($f['q'] !== '') {
        $like = '%' . addcslashes($f['q'], '%_\\') . '%';
        $condicion = 'c.titulo LIKE :q1 OR c.artista LIKE :q2';
        $params[':q1'] = $like;
        $params[':q2'] = $like;

        // También busca por el nombre visible del género (ej. "urbano" encuentra reggaeton)
        $claves = array_keys(array_filter(GENEROS, fn($n) => mb_stripos($n, $f['q']) !== false));
        foreach ($claves as $i => $clave) {
            $params[":g$i"] = $clave;
        }
        if ($claves) {
            $condicion .= ' OR c.genero IN (' . implode(', ', array_map(fn($i) => ":g$i", array_keys($claves))) . ')';
        }
        $where[] = "($condicion)";
    }

    if ($f['estado'] !== '') {
        $where[] = 'c.estado = :estado';
        $params[':estado'] = $f['estado'];
    }

    if ($f['playlist'] > 0) {
        $where[] = 'c.id IN (SELECT pc.cancion_id FROM playlist_canciones pc
                             JOIN playlists p ON p.id = pc.playlist_id
                             WHERE p.id = :pl AND p.usuario_id = :uid2)';
        $params[':pl']   = $f['playlist'];
        $params[':uid2'] = $uid;
    }

    $sqlWhere = implode(' AND ', $where);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM canciones c WHERE $sqlWhere");
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    $paginas = max(1, (int) ceil($total / POR_PAGINA));
    $pagina  = min($f['pagina'], $paginas);
    $offset  = ($pagina - 1) * POR_PAGINA;
    $orden   = ORDEN_SQL[$f['orden']];

    // LIMIT/OFFSET son enteros calculados aquí, no texto del usuario
    $stmt = $pdo->prepare("SELECT c.* FROM canciones c WHERE $sqlWhere ORDER BY $orden LIMIT " . POR_PAGINA . " OFFSET $offset");
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    return ['items' => $items, 'total' => $total, 'paginas' => $paginas, 'pagina' => $pagina];
}

// Playlists del usuario con su cantidad de canciones
function consultar_playlists(PDO $pdo, int $uid): array {
    $stmt = $pdo->prepare('SELECT p.id, p.nombre, COUNT(pc.cancion_id) AS total
                           FROM playlists p
                           LEFT JOIN playlist_canciones pc ON pc.playlist_id = p.id
                           WHERE p.usuario_id = :uid
                           GROUP BY p.id, p.nombre
                           ORDER BY p.nombre');
    $stmt->execute([':uid' => $uid]);
    return $stmt->fetchAll();
}

// Para cada canción mostrada, sus playlists: [cancion_id => [playlist_id => nombre]]
function playlists_por_cancion(PDO $pdo, int $uid, array $idsCanciones): array {
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

// Números para "Mi progreso" y las estadísticas
function estadisticas(PDO $pdo, int $uid): array {
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
