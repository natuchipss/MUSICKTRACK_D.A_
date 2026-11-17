<?php
// Diseño compartido de login.php y registro.php.
// Variables: $tituloPagina, $modo ('login' | 'registro'), $error (texto o null), $aviso (texto o null)
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tituloPagina) ?> | MusicTrack</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>

<body class="auth-page">

    <main class="auth-box">

        <div class="auth-intro">
            <div class="brand-icon">♫</div>
            <h1>MUSICTRACK</h1>
            <p>Tu música, organizada a tu manera. Busca en Spotify, guarda tu biblioteca y lleva tu progreso.</p>
        </div>

        <div class="auth-card">

            <h2><?= $modo === 'login' ? 'Inicia sesión' : 'Crea tu cuenta' ?></h2>

            <?php if ($error): ?>
                <p class="auth-alert error"><?= e($error) ?></p>
            <?php elseif ($aviso): ?>
                <p class="auth-alert ok"><?= e($aviso) ?></p>
            <?php endif; ?>

            <form action="acciones/<?= $modo ?>.php" method="POST" class="auth-form">
                <?= csrf_campo() ?>

                <?php if ($modo === 'registro'): ?>
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" maxlength="80" required autocomplete="name"
                        value="<?= e($viejoNombre ?? '') ?>">
                <?php endif; ?>

                <label for="email">Correo</label>
                <input type="email" id="email" name="email" maxlength="190" required autocomplete="email"
                    value="<?= e($viejoEmail ?? '') ?>">

                <label for="password">Contraseña<?= $modo === 'registro' ? ' <span>(mínimo 8 caracteres)</span>' : '' ?></label>
                <input type="password" id="password" name="password" required
                    <?= $modo === 'registro' ? 'minlength="8" autocomplete="new-password"' : 'autocomplete="current-password"' ?>>

                <?php if ($modo === 'registro'): ?>
                    <label for="confirmar">Repite la contraseña</label>
                    <input type="password" id="confirmar" name="confirmar" minlength="8" required autocomplete="new-password">
                <?php endif; ?>

                <button type="submit" class="btn btn-primary">
                    <?= $modo === 'login' ? 'Entrar' : 'Crear cuenta' ?>
                </button>
            </form>

            <p class="auth-switch">
                <?php if ($modo === 'login'): ?>
                    ¿No tienes cuenta? <a href="registro.php">Regístrate</a>
                <?php else: ?>
                    ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
                <?php endif; ?>
            </p>

        </div>

    </main>

</body>

</html>
