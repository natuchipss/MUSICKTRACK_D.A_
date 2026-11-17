        <!-- ========================= BIBLIOTECA ========================= -->
        <section id="biblioteca" class="section library">

            <div class="section-heading">
                <span class="section-kicker">COLECCIÓN</span>
                <h2>Tu biblioteca <small class="count-pill"><?= $total ?></small></h2>
            </div>

            <!-- PLAYLISTS: filtro por playlist + crear nueva -->
            <div class="playlist-bar">

                <div class="playlist-chips">
                    <a href="<?= e(url_biblioteca($filtros, ['playlist' => 0, 'pagina' => 1])) ?>"
                        class="chip <?= $filtros['playlist'] === 0 ? 'active' : '' ?>">Todas</a>
                    <?php foreach ($playlists as $pl): ?>
                        <a href="<?= e(url_biblioteca($filtros, ['playlist' => (int) $pl['id'], 'pagina' => 1])) ?>"
                            class="chip <?= $filtros['playlist'] === (int) $pl['id'] ? 'active' : '' ?>">
                            ♫ <?= e($pl['nombre']) ?> <small><?= (int) $pl['total'] ?></small>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="playlist-actions">
                    <?php if ($playlistActiva): ?>
                        <form action="acciones/playlist_eliminar.php" method="POST"
                            onsubmit="return confirm('¿Eliminar la playlist? Las canciones seguirán en tu biblioteca.');">
                            <?= $camposPost ?>
                            <input type="hidden" name="id" value="<?= (int) $playlistActiva['id'] ?>">
                            <button type="submit" class="link-danger">✕ Eliminar playlist</button>
                        </form>
                    <?php endif; ?>

                    <form action="acciones/playlist_crear.php" method="POST" class="playlist-new">
                        <?= $camposPost ?>
                        <input type="text" name="nombre" maxlength="80" placeholder="Nueva playlist..." required aria-label="Nombre de la nueva playlist">
                        <button type="submit">＋ Crear</button>
                    </form>
                </div>

            </div>

            <!-- BUSCADOR, FILTRO Y ORDEN (se envían por GET al servidor) -->
            <form class="library-tools" id="filtros" method="GET" action="index.php#biblioteca">

                <?php if ($filtros['playlist'] > 0): ?>
                    <input type="hidden" name="playlist" value="<?= $filtros['playlist'] ?>">
                <?php endif; ?>

                <div class="search-box">
                    <span class="search-icon">⌕</span>
                    <input type="search" id="buscar" name="q" value="<?= e($filtros['q']) ?>"
                        placeholder="Buscar por título, artista o género...">
                </div>

                <div class="filter-box">
                    <select name="estado" aria-label="Filtrar por estado" data-autosubmit>
                        <option value="">Todos los estados</option>
                        <?php foreach (ESTADOS as $valor => $nombre): ?>
                            <option value="<?= e($valor) ?>" <?= $filtros['estado'] === $valor ? 'selected' : '' ?>><?= e($nombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="filter-arrow">⌄</span>
                </div>

                <div class="filter-box">
                    <select name="orden" aria-label="Ordenar" data-autosubmit>
                        <?php foreach (ORDENES as $valor => $nombre): ?>
                            <option value="<?= e($valor) ?>" <?= $filtros['orden'] === $valor ? 'selected' : '' ?>><?= e($nombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="filter-arrow">⌄</span>
                </div>

                <noscript><button type="submit">Aplicar</button></noscript>
            </form>

            <div class="library-grid">

                <?php if ($total === 0): ?>

                    <div class="empty-state">
                        <div class="empty-icon">♫</div>
                        <h3>Tu biblioteca está vacía</h3>
                        <p>Busca una canción en Spotify o regístrala a mano para empezar.</p>
                        <a href="#registrar" class="btn btn-primary">＋ Agregar música</a>
                    </div>

                <?php elseif (!$biblioteca['items']): ?>

                    <div class="empty-state">
                        <h3>No hay resultados</h3>
                        <p>
                            <?= $playlistActiva && $playlistActiva['total'] == 0
                                ? 'Esta playlist aún no tiene canciones. Agrégalas desde cada tarjeta.'
                                : 'Ninguna canción coincide con la búsqueda o el filtro.' ?>
                        </p>
                        <a href="<?= e(url_biblioteca([])) ?>" class="btn btn-light">Ver toda la biblioteca</a>
                    </div>

                <?php else: ?>

                    <?php foreach ($biblioteca['items'] as $i => $item):
                        $id = (int) $item['id'];
                        $enPlaylists = $plPorCancion[$id] ?? [];
                        // Datos para el modal "Editar"
                        $datosEditar = json_encode(array_intersect_key($item, array_flip(
                            ['id', 'titulo', 'artista', 'tipo', 'genero', 'prioridad', 'estado', 'notas', 'calificacion']
                        )));
                    ?>
                        <article class="album-card" id="cancion-<?= $id ?>">

                            <?php if (!empty($item['portada_url'])): ?>
                                <div class="cover cover-photo">
                                    <img src="<?= e($item['portada_url']) ?>" alt="Portada de <?= e($item['titulo']) ?>" loading="lazy">
                                </div>
                            <?php else: ?>
                                <!-- Sin portada: degradado (se alternan 3 colores) -->
                                <div class="cover cover-<?= ['one', 'two', 'three'][$i % 3] ?>">
                                    <span><?= e($item['titulo']) ?></span>
                                    <small><?= e($item['artista']) ?></small>
                                </div>
                            <?php endif; ?>

                            <div class="album-info">

                                <div class="album-title">
                                    <div>
                                        <h3><?= e($item['titulo']) ?></h3>
                                        <p class="artist"><?= e($item['artista']) ?></p>
                                    </div>
                                    <?php if (!empty($item['spotify_id'])): ?>
                                        <span class="music-dot spotify-dot" title="Disponible en Spotify">●</span>
                                    <?php else: ?>
                                        <span class="music-dot">♪</span>
                                    <?php endif; ?>
                                </div>

                                <p class="genre"><?= e(etiqueta(GENEROS, $item['genero'])) ?> · <?= e(etiqueta(TIPOS, $item['tipo'])) ?></p>

                                <!-- Calificación: clic en la estrella actual la quita -->
                                <form action="acciones/calificar.php" method="POST" class="stars" aria-label="Calificación">
                                    <?= $camposPost ?>
                                    <input type="hidden" name="id" value="<?= $id ?>">
                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                        <button type="submit" name="calificacion"
                                            value="<?= (int) $item['calificacion'] === $s ? 0 : $s ?>"
                                            class="<?= $s <= (int) $item['calificacion'] ? 'on' : '' ?>"
                                            title="<?= (int) $item['calificacion'] === $s ? 'Quitar calificación' : "$s de 5" ?>">★</button>
                                    <?php endfor; ?>
                                </form>

                                <div class="tags">
                                    <!-- Estado: se cambia directo desde la tarjeta -->
                                    <form action="acciones/estado.php" method="POST">
                                        <?= $camposPost ?>
                                        <input type="hidden" name="id" value="<?= $id ?>">
                                        <select name="estado" class="badge badge-select <?= CLASE_ESTADO[$item['estado']] ?? '' ?>"
                                            aria-label="Estado de escucha" data-autosubmit>
                                            <?php foreach (ESTADOS as $valor => $nombre): ?>
                                                <option value="<?= e($valor) ?>" <?= $item['estado'] === $valor ? 'selected' : '' ?>><?= e($nombre) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                    <span class="badge <?= CLASE_PRIORIDAD[$item['prioridad']] ?? '' ?>">
                                        Prioridad <?= e(mb_strtolower(etiqueta(PRIORIDADES, $item['prioridad']))) ?>
                                    </span>
                                </div>

                                <?php if (!empty($item['notas'])): ?>
                                    <p class="card-notes" title="<?= e($item['notas']) ?>">“<?= e($item['notas']) ?>”</p>
                                <?php endif; ?>

                                <!-- Playlists de esta canción -->
                                <div class="card-playlists">
                                    <?php foreach ($enPlaylists as $plId => $plNombre): ?>
                                        <form action="acciones/playlist_cancion.php" method="POST" class="chip chip-small">
                                            <?= $camposPost ?>
                                            <input type="hidden" name="cancion_id" value="<?= $id ?>">
                                            <input type="hidden" name="playlist_id" value="<?= (int) $plId ?>">
                                            <input type="hidden" name="accion" value="quitar">
                                            ♫ <?= e($plNombre) ?>
                                            <button type="submit" title="Quitar de <?= e($plNombre) ?>" aria-label="Quitar de <?= e($plNombre) ?>">✕</button>
                                        </form>
                                    <?php endforeach; ?>

                                    <?php $disponibles = array_filter($playlists, fn($pl) => !isset($enPlaylists[$pl['id']])); ?>
                                    <?php if ($disponibles): ?>
                                        <form action="acciones/playlist_cancion.php" method="POST">
                                            <?= $camposPost ?>
                                            <input type="hidden" name="cancion_id" value="<?= $id ?>">
                                            <input type="hidden" name="accion" value="agregar">
                                            <select name="playlist_id" class="playlist-add" aria-label="Agregar a playlist" data-autosubmit>
                                                <option value="">＋ Playlist</option>
                                                <?php foreach ($disponibles as $pl): ?>
                                                    <option value="<?= (int) $pl['id'] ?>"><?= e($pl['nombre']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($item['spotify_id'])): ?>
                                    <!-- Reproductor embebido de Spotify: solo necesita el ID (canción o álbum) -->
                                    <iframe class="spotify-player"
                                        src="https://open.spotify.com/embed/<?= $item['spotify_tipo'] === 'album' ? 'album' : 'track' ?>/<?= e($item['spotify_id']) ?>?theme=0"
                                        width="100%" height="80" frameborder="0"
                                        allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"
                                        loading="lazy"
                                        title="Reproducir <?= e($item['titulo']) ?> en Spotify"></iframe>
                                <?php endif; ?>

                                <div class="card-actions">
                                    <small class="card-date">Agregado el <?= date('d/m/Y', strtotime($item['fecha_registro'])) ?></small>
                                    <div class="card-buttons">
                                        <button type="button" class="card-action" data-editar="<?= e($datosEditar) ?>">✎ Editar</button>
                                        <form action="acciones/eliminar.php" method="POST" onsubmit="return confirm('¿Deseas eliminar este elemento?');">
                                            <?= $camposPost ?>
                                            <input type="hidden" name="id" value="<?= $id ?>">
                                            <button type="submit" class="delete-action" aria-label="Eliminar <?= e($item['titulo']) ?>">✕</button>
                                        </form>
                                    </div>
                                </div>

                            </div>
                        </article>
                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

            <!-- PAGINACIÓN -->
            <?php if ($biblioteca['paginas'] > 1): ?>
                <nav class="pagination" aria-label="Páginas de la biblioteca">
                    <?php if ($biblioteca['pagina'] > 1): ?>
                        <a href="<?= e(url_biblioteca($filtros, ['pagina' => $biblioteca['pagina'] - 1])) ?>">‹ Anterior</a>
                    <?php endif; ?>

                    <?php for ($p = 1; $p <= $biblioteca['paginas']; $p++): ?>
                        <a href="<?= e(url_biblioteca($filtros, ['pagina' => $p])) ?>"
                            class="<?= $p === $biblioteca['pagina'] ? 'active' : '' ?>"
                            <?= $p === $biblioteca['pagina'] ? 'aria-current="page"' : '' ?>><?= $p ?></a>
                    <?php endfor; ?>

                    <?php if ($biblioteca['pagina'] < $biblioteca['paginas']): ?>
                        <a href="<?= e(url_biblioteca($filtros, ['pagina' => $biblioteca['pagina'] + 1])) ?>">Siguiente ›</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>

            <?php if ($biblioteca['total'] > 0): ?>
                <p class="results-info">
                    Mostrando <?= count($biblioteca['items']) ?> de <?= $biblioteca['total'] ?>
                    <?= $biblioteca['total'] === 1 ? 'elemento' : 'elementos' ?>
                </p>
            <?php endif; ?>

        </section>
