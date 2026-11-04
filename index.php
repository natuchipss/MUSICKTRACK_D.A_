<?php
require_once 'config.php';

// Conteo para las tarjetas de progreso
$totalPendientes = $pdo->query("SELECT COUNT(*) FROM canciones WHERE estado = 'pendiente'")->fetchColumn();
$totalEscuchando = $pdo->query("SELECT COUNT(*) FROM canciones WHERE estado = 'escuchando'")->fetchColumn();
$totalEscuchados = $pdo->query("SELECT COUNT(*) FROM canciones WHERE estado = 'escuchado'")->fetchColumn();

// Traer la lista de la base de datos
$stmt = $pdo->query("SELECT * FROM canciones ORDER BY id DESC");
$canciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description"
        content="MusicTrack - Sistema de gestión musical para organizar tus álbumes y canciones pendientes, en proceso y escuchados.">

    <title>MusicTrack | Sistema de Gestión Musical</title>

    <link rel="stylesheet" href="styles.css">
</head>


<body>

    <!-- =========================
         HEADER
    ========================== -->

    <header class="main-header">

        <a class="brand" href="#inicio">

            <div class="brand-icon">
                ♫
            </div>

            <div>
                <h1>MUSICTRACK</h1>
                <p>SISTEMA DE SEGUIMIENTO MUSICAL</p>
            </div>

        </a>


        <nav class="main-nav" aria-label="Navegación principal">

            <a href="#inicio">
                Inicio
            </a>

            <a href="#progreso">
                Mi progreso
            </a>

            <a href="#biblioteca">
                Biblioteca
            </a>

            <a href="#registrar">
                Registrar música
            </a>

        </nav>


        <div class="header-actions">

            <a href="#biblioteca"
                class="icon-btn"
                aria-label="Buscar">
                ⌕
            </a>

            <a href="#perfil"
                class="profile-btn"
                aria-label="Mi perfil">
                ●
            </a>

        </div>

    </header>


    <main>


        <!-- =========================
             INICIO
        ========================== -->

        <section id="inicio" class="hero">

            <div class="hero-content">

                <span class="eyebrow">
                    TU MÚSICA · TU ESPACIO
                </span>


                <h2>
                    Organiza tu música.<br>
                    <span>Disfruta tu progreso.</span>
                </h2>


                <p>
                    Guarda tus álbumes, define tus prioridades y lleva
                    un registro de todo lo que quieres escuchar.
                </p>


                <div class="hero-buttons">

                    <a href="#biblioteca"
                        class="btn btn-primary">
                        ♪ Ver mi biblioteca
                    </a>

                    <a href="#registrar"
                        class="btn btn-light">
                        ＋ Registrar música
                    </a>

                </div>


                <div class="hero-mini">
                    <span>✦</span>
                    Todo tu universo musical en un solo lugar
                </div>

            </div>


            <div class="hero-art" aria-hidden="true">

                <div class="glow"></div>


                <div class="vinyl">

                    <div class="vinyl-label">
                        <strong>MT</strong>
                        <small>♫</small>
                    </div>

                    <div class="vinyl-hole"></div>

                </div>


                <div class="floating-card card-one">
                    ♫
                    <b>45</b>
                    <small>escuchados</small>
                </div>


                <div class="floating-card card-two">
                    ♡
                    <b>12</b>
                    <small>pendientes</small>
                </div>

            </div>

        </section>



        <!-- =========================
             PROGRESO
        ========================== -->

        <section id="progreso" class="section">

            <div class="section-heading">

                <div>

                    <span class="section-kicker">
                        RESUMEN
                    </span>

                    <h2>
                        Mi progreso musical
                    </h2>

                </div>

                <p>
                    Un vistazo rápido a tu colección.
                </p>

            </div>


            <div class="stats-grid">


                <!-- PENDIENTES -->
<article class="stat-card purple">
    <div class="stat-icon">◷</div>
    <div>
        <h3>Pendientes</h3>
        <strong><?php echo $totalPendientes; ?></strong>
        <p>Álbumes por explorar</p>
    </div>
</article>

<!-- ESCUCHANDO -->
<article class="stat-card blue">
    <div class="stat-icon">▶</div>
    <div>
        <h3>Escuchando</h3>
        <strong><?php echo $totalEscuchando; ?></strong>
        <p>Álbumes en proceso</p>
    </div>
</article>

<!-- ESCUCHADOS -->
<article class="stat-card pink">
    <div class="stat-icon">✓</div>
    <div>
        <h3>Escuchados</h3>
        <strong><?php echo $totalEscuchados; ?></strong>
        <p>Álbumes completados</p>
    </div>
</article>


                <!-- ESCUCHADOS -->

                <article class="stat-card pink">

                    <div class="stat-icon">
                        ✓
                    </div>

                    <div>

                        <h3>
                            Escuchados
                        </h3>

                        <strong>
                            45
                        </strong>

                        <p>
                            Álbumes completados
                        </p>

                    </div>

                </article>

            </div>

        </section>



        <!-- =========================
             BIBLIOTECA
        ========================== -->

        <section id="biblioteca" class="section library">


            <div class="section-heading">

                <span class="section-kicker">
                    COLECCIÓN
                </span>

                <h2>
                    Álbumes en tu biblioteca
                </h2>


                <!-- BUSCADOR Y FILTRO -->

                <div class="library-tools">


                    <div class="search-box">

                        <span class="search-icon">
                            ⌕
                        </span>

                        <input
                            type="search"
                            id="buscar"
                            name="buscar"
                            placeholder="Buscar música o artista...">

                    </div>


                    <div class="filter-box">

                        <select
                            id="filtroEstado"
                            name="filtroEstado">

                            <option value="">
                                Todos los estados
                            </option>

                            <option value="pendiente">
                                Pendientes
                            </option>

                            <option value="escuchando">
                                Escuchando
                            </option>

                            <option value="escuchado">
                                Escuchados
                            </option>

                        </select>

                        <span class="filter-arrow">
                            ⌄
                        </span>

                    </div>

                </div>

            </div>



            <div class="library-grid">
    <?php if (empty($canciones)): ?>
        <p style="grid-column: 1/-1; text-align: center; color: var(--muted);">No hay canciones guardadas aún. ¡Registra la primera abajo!</p>
    <?php else: ?>
        <?php foreach ($canciones as $item): ?>
            <article class="album-card">
                <div class="cover cover-one">
                    <span><?php echo htmlspecialchars($item['titulo']); ?></span>
                    <small><?php echo htmlspecialchars($item['artista']); ?></small>
                </div>

                <div class="album-info">
                    <div class="album-title">
                        <div>
                            <h3><?php echo htmlspecialchars($item['titulo']); ?></h3>
                            <p class="artist"><?php echo htmlspecialchars($item['artista']); ?></p>
                        </div>
                        <span class="music-dot">♪</span>
                    </div>

                    <p class="genre"><?php echo ucfirst(htmlspecialchars($item['genero'])); ?> (<?php echo ucfirst(htmlspecialchars($item['tipo'])); ?>)</p>

                    <div class="tags">
                        <span class="badge <?php echo $item['estado'] === 'pendiente' ? 'pending' : ($item['estado'] === 'escuchando' ? 'listening' : 'finished'); ?>">
                            <?php echo ucfirst($item['estado']); ?>
                        </span>
                        <span class="badge <?php echo $item['prioridad'] === 'alta' ? 'high' : ($item['prioridad'] === 'media' ? 'medium' : 'low'); ?>">
                            Prioridad: <?php echo ucfirst($item['prioridad']); ?>
                        </span>
                    </div>

                    <div class="card-actions">
                        <a href="eliminar.php?id=<?php echo $item['id']; ?>" class="delete-action" onclick="return confirm('¿Deseas eliminar este elemento?');">
                            ♡ Eliminar
                        </a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>



                <!-- =========================
                     ÁLBUM 2
                ========================== -->

                <article class="album-card">


                    <div class="cover cover-two">

                        <span>
                            FUTURE<br>
                            NOSTALGIA
                        </span>

                        <small>
                            DUA LIPA
                        </small>

                    </div>


                    <div class="album-info">


                        <div class="album-title">

                            <div>

                                <h3>
                                    Future Nostalgia
                                </h3>

                                <p class="artist">
                                    Dua Lipa
                                </p>

                            </div>

                            <span class="music-dot">
                                ♪
                            </span>

                        </div>


                        <p class="genre">
                            Pop / Electronic
                        </p>


                        <div class="tags">

                            <span class="badge listening">
                                Escuchando
                            </span>

                            <span class="badge medium">
                                Media
                            </span>

                        </div>


                        <div class="card-actions">

                            <button
                                type="button"
                                class="card-action">
                                ✎ Editar
                            </button>

                            <button
                                type="button"
                                class="delete-action">
                                ♡ Eliminar
                            </button>

                        </div>

                    </div>

                </article>



                <!-- =========================
                     ÁLBUM 3
                ========================== -->

                <article class="album-card">


                    <div class="cover cover-three">

                        <span>
                            KIND<br>
                            OF BLUE
                        </span>

                        <small>
                            MILES DAVIS
                        </small>

                    </div>


                    <div class="album-info">


                        <div class="album-title">

                            <div>

                                <h3>
                                    Kind of Blue
                                </h3>

                                <p class="artist">
                                    Miles Davis
                                </p>

                            </div>

                            <span class="music-dot">
                                ♪
                            </span>

                        </div>


                        <p class="genre">
                            Jazz / Lo-Fi
                        </p>


                        <div class="tags">

                            <span class="badge finished">
                                Escuchado
                            </span>

                            <span class="badge low">
                                Baja
                            </span>

                        </div>


                        <div class="card-actions">

                            <button
                                type="button"
                                class="card-action">
                                ✎ Editar
                            </button>

                            <button
                                type="button"
                                class="delete-action">
                                ♡ Eliminar
                            </button>

                        </div>

                    </div>

                </article>


            </div>

        </section>



        <!-- =========================
             REGISTRAR MÚSICA
        ========================== -->

        <section id="registrar" class="section register">


            <div class="register-box">


                <div class="register-intro">

                    <span class="section-kicker">
                        NUEVA MÚSICA
                    </span>

                    <h2>
                        Agrega algo nuevo a tu biblioteca
                    </h2>

                    <p>
                        Registra un álbum o canción y decide
                        cuándo quieres escucharlo.
                    </p>

                    <div class="mini-record">
                        ♫
                    </div>

                </div>



                <!-- FORMULARIO PARA PHP -->

                <form
                    action="registrar.php"
                    method="POST"
                    class="music-form">


                    <div class="form-group">

                        <label for="titulo">
                            Título
                        </label>

                        <input
                            type="text"
                            id="titulo"
                            name="titulo"
                            placeholder="Ej. Abbey Road"
                            required>

                    </div>


                    <div class="form-group">

                        <label for="artista">
                            Artista o banda
                        </label>

                        <input
                            type="text"
                            id="artista"
                            name="artista"
                            placeholder="Ej. The Beatles"
                            required>

                    </div>


                    <div class="form-group">

                        <label for="tipo">
                            Tipo de música
                        </label>

                        <select
                            id="tipo"
                            name="tipo"
                            required>

                            <option value="">
                                Seleccionar tipo...
                            </option>

                            <option value="album">
                                Álbum
                            </option>

                            <option value="cancion">
                                Canción
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label for="genero">
                            Género musical
                        </label>

                        <select
                            id="genero"
                            name="genero"
                            required>

                            <option value="">
                                Seleccionar género...
                            </option>

                            <option value="rock">
                                Rock
                            </option>

                            <option value="pop">
                                Pop
                            </option>

                            <option value="hiphop">
                                Hip Hop / Rap
                            </option>

                            <option value="jazz">
                                Jazz / Blues
                            </option>

                            <option value="electronica">
                                Electrónica
                            </option>

                            <option value="otro">
                                Otro
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label for="prioridad">
                            Prioridad de escucha
                        </label>

                        <select
                            id="prioridad"
                            name="prioridad"
                            required>

                            <option value="">
                                Seleccionar prioridad...
                            </option>

                            <option value="alta">
                                Alta (Próximamente)
                            </option>

                            <option value="media">
                                Media (Sin afán)
                            </option>

                            <option value="baja">
                                Baja (Posterior)
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label for="estado">
                            Estado de escucha
                        </label>

                        <select
                            id="estado"
                            name="estado"
                            required>

                            <option value="pendiente">
                                Pendiente
                            </option>

                            <option value="escuchando">
                                Escuchando
                            </option>

                            <option value="escuchado">
                                Escuchado
                            </option>

                        </select>

                    </div>


                    <div class="form-group full">

                        <label for="notas">
                            Notas adicionales
                            <span>(opcional)</span>
                        </label>

                        <textarea
                            id="notas"
                            name="notas"
                            rows="4"
                            placeholder="¿Quién te lo recomendó o por qué quieres escucharlo?"></textarea>

                    </div>


                    <button
                        type="submit"
                        class="form-submit">

                        ♡ Guardar en mi biblioteca

                    </button>


                </form>

            </div>

        </section>

    </main>



    <!-- =========================
         FOOTER
    ========================== -->

    <footer class="main-footer">


        <div class="footer-top">


            <div class="footer-brand">

                <div class="brand-icon">
                    ♫
                </div>

                <div>

                    <h2>
                        MUSICTRACK
                    </h2>

                    <p>
                        Tu música, organizada a tu manera.
                    </p>

                </div>

            </div>


            <div>

                <h3>
                    Explora
                </h3>

                <a href="#inicio">
                    Inicio
                </a>

                <a href="#progreso">
                    Mi progreso
                </a>

                <a href="#biblioteca">
                    Biblioteca
                </a>

                <a href="#registrar">
                    Registrar música
                </a>

            </div>


            <div>

                <h3>
                    Conecta
                </h3>

                <a href="#">
                    Instagram
                </a>

                <a href="#">
                    Spotify
                </a>

                <a href="#">
                    Facebook
                </a>

            </div>


        </div>


        <div class="footer-bottom">

            <p>
                © 2026 MusicTrack · Proyecto académico
            </p>

            <p>
                Hecho con ♡ para amantes de la música
            </p>

        </div>

    </footer>


</body>

</html>