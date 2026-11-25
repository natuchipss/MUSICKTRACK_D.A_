<?php

/**
 * Vista formulario: la incluye index.php, que define estas variables.
 *
 * @var string $camposPost
 */

?>
        <!-- ========================= REGISTRAR MÚSICA ========================= -->
        <section id="registrar" class="section register">

            <div class="register-box">

                <div class="register-intro">
                    <span class="section-kicker">NUEVA MÚSICA</span>
                    <h2>Agrega algo nuevo a tu biblioteca</h2>
                    <p>
                        Búscala en Spotify para traer la portada y el reproductor,
                        o escríbela a mano si no está.
                    </p>
                    <ol class="register-steps">
                        <li>Busca una canción o álbum en Spotify, o pega su link</li>
                        <li>Pulsa <b>Usar</b> y completa los datos, o <b>Guardar</b> para agregarla ya</li>
                        <li>Escúchala, califícala y organízala en playlists</li>
                    </ol>
                </div>

                <form action="acciones/registrar.php" method="POST" class="music-form">
                    <?= $camposPost ?>

                    <!-- BUSCADOR DE SPOTIFY: la búsqueda la hace api/search.php en el servidor -->
                    <div class="form-group full spotify-search">

                        <div class="spotify-search-head">
                            <label for="spotifyQ">
                                <span class="spotify-tag">● Spotify</span> Buscar
                            </label>
                            <div class="segmented" role="group" aria-label="Qué buscar">
                                <button type="button" class="active" data-tipo-busqueda="track">Canciones</button>
                                <button type="button" data-tipo-busqueda="album">Álbumes</button>
                            </div>
                        </div>

                        <input type="search" id="spotifyQ" placeholder="Escribe un nombre o pega un link de Spotify..." autocomplete="off">

                        <p id="spotifyEstado" class="spotify-status" hidden></p>

                        <ul id="spotifyResultados" class="spotify-results"></ul>

                        <div id="spotifyElegida" class="spotify-selected" hidden>
                            <img id="spotifyElegidaImg" src="" alt="">
                            <span id="spotifyElegidaTxt"></span>
                            <button type="button" id="spotifyQuitar">✕ Quitar</button>
                        </div>

                        <input type="hidden" id="spotify_id" name="spotify_id">
                        <input type="hidden" id="spotify_tipo" name="spotify_tipo">
                        <input type="hidden" id="portada_url" name="portada_url">

                    </div>

                    <div class="form-group">
                        <label for="titulo">Título</label>
                        <input type="text" id="titulo" name="titulo" maxlength="150" placeholder="Ej. Abbey Road" required>
                    </div>

                    <div class="form-group">
                        <label for="artista">Artista o banda</label>
                        <input type="text" id="artista" name="artista" maxlength="150" placeholder="Ej. The Beatles" required>
                    </div>

                    <div class="form-group">
                        <label for="tipo">Tipo de música</label>
                        <select id="tipo" name="tipo" required>
                            <option value="">Seleccionar tipo...</option>
                            <?php foreach (TIPOS as $valor => $nombre): ?>
                                <option value="<?= e($valor) ?>"><?= e($nombre) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="genero">Género musical</label>
                        <select id="genero" name="genero" required>
                            <option value="">Seleccionar género...</option>
                            <?php foreach (GENEROS as $valor => $nombre): ?>
                                <option value="<?= e($valor) ?>"><?= e($nombre) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="prioridad">Prioridad de escucha</label>
                        <select id="prioridad" name="prioridad" required>
                            <option value="">Seleccionar prioridad...</option>
                            <option value="alta">Alta (Próximamente)</option>
                            <option value="media">Media (Sin afán)</option>
                            <option value="baja">Baja (Posterior)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="estado">Estado de escucha</label>
                        <select id="estado" name="estado" required>
                            <?php foreach (ESTADOS as $valor => $nombre): ?>
                                <option value="<?= e($valor) ?>"><?= e($nombre) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group full">
                        <label for="notas">Notas adicionales <span>(opcional)</span></label>
                        <textarea id="notas" name="notas" rows="3" placeholder="¿Quién te lo recomendó o por qué quieres escucharlo?"></textarea>
                    </div>

                    <button type="submit" class="form-submit">♡ Guardar en mi biblioteca</button>

                </form>

            </div>

        </section>
