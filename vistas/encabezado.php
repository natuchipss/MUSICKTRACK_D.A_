<?php

/**
 * Vista encabezado: la incluye index.php, que define estas variables.
 *
 * @var array{0: string, 1: string}|null $aviso
 * @var array{id: int, nombre: string} $usuario
 */

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="MusicTrack - Organiza tus álbumes y canciones pendientes, en proceso y escuchados, con reproducción desde Spotify.">
    <!-- Token CSRF para las peticiones de JavaScript (guardado rápido) -->
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title>MusicTrack | Sistema de Gestión Musical</title>
    <link rel="stylesheet" href="assets/css/styles.css?v=<?= filemtime(__DIR__ . '/../assets/css/styles.css') ?>">
</head>

<body>

    <!-- Aviso flotante (después de guardar, editar o eliminar) -->
    <div id="toast" class="toast <?= $aviso ? e($aviso[0]) : '' ?>" role="status" aria-live="polite" <?= $aviso ? '' : 'hidden' ?>>
        <?= $aviso ? e($aviso[1]) : '' ?>
    </div>

    <header class="main-header">

        <a class="brand" href="#inicio">
            <div class="brand-icon">♫</div>
            <div>
                <h1>MUSICTRACK</h1>
                <p>SISTEMA DE SEGUIMIENTO MUSICAL</p>
            </div>
        </a>

        <nav class="main-nav" aria-label="Navegación principal">
            <a href="#inicio">Inicio</a>
            <a href="#progreso">Mi progreso</a>
            <a href="#biblioteca">Biblioteca</a>
            <a href="#registrar">Registrar música</a>
        </nav>

        <div class="header-actions">
            <a href="#registrar" class="btn-spotify" aria-label="Agregar desde Spotify">＋ Agregar</a>

            <details class="user-menu">
                <summary aria-label="Mi cuenta">
                    <span class="user-avatar"><?= e(mb_strtoupper(mb_substr($usuario['nombre'], 0, 1))) ?></span>
                </summary>
                <div class="user-dropdown">
                    <p>Hola, <b><?= e($usuario['nombre']) ?></b></p>
                    <form action="acciones/logout.php" method="POST">
                        <?= csrf_campo() ?>
                        <button type="submit">Cerrar sesión</button>
                    </form>
                </div>
            </details>
        </div>

    </header>
