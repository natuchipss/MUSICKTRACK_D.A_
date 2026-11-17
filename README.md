# MusicTrack

Biblioteca musical en PHP + MySQL que busca canciones y álbumes en la API de Spotify, guarda su ID
y los reproduce con el reproductor embebido (iframe). No se guarda la música, solo el ID.

## Funciones

- **Cuentas de usuario**: registro, inicio de sesión y una biblioteca por persona.
- **Spotify**: búsqueda de canciones o álbumes, o pegar un link (`open.spotify.com/...` o `spotify:track:...`).
  Si la API falla, el link se resuelve con el oEmbed público (título y portada, sin artista).
- **Género automático** sugerido con la API pública de Deezer.
- **Biblioteca**: búsqueda, filtro por estado, orden (recientes, título, artista, prioridad, calificación)
  y paginación (9 por página), todo en el servidor.
- **En cada tarjeta**: cambiar estado, calificar de 1 a 5 estrellas, agregar o quitar de playlists,
  editar todos los datos (modal) y eliminar.
- **Playlists**: crear, filtrar la biblioteca por playlist y eliminar.
- **Estadísticas**: avance general, canciones por género, artistas más guardados y calificación promedio.

## Estructura

```
MUSICKTRACK_D.A_/
├── index.php              página principal (requiere sesión; consulta datos y une las vistas)
├── login.php              iniciar sesión
├── registro.php           crear cuenta
├── vistas/                partes de la página (HTML)
│   ├── encabezado.php     <head>, aviso flotante, menú y cuenta
│   ├── inicio.php         portada
│   ├── progreso.php       contadores, barra de avance y estadísticas
│   ├── biblioteca.php     playlists, filtros, tarjetas y paginación
│   ├── formulario.php     buscador de Spotify y formulario de registro
│   ├── modal_editar.php   ventana para editar una canción
│   ├── auth.php           diseño de login y registro
│   └── pie.php            footer y script
├── acciones/              reciben formularios POST (con token CSRF) y redirigen
│   ├── login.php  registro.php  logout.php
│   ├── registrar.php      agregar canción
│   ├── actualizar.php     editar canción
│   ├── estado.php         cambiar estado
│   ├── calificar.php      estrellas
│   ├── eliminar.php
│   ├── playlist_crear.php  playlist_eliminar.php
│   └── playlist_cancion.php  agregar/quitar canción de una playlist
├── api/                   endpoints JSON que usa JavaScript (requieren sesión)
│   ├── search.php         busca canciones o álbumes en Spotify
│   ├── link.php           resuelve un link de Spotify pegado
│   ├── genre.php          sugiere el género (Deezer)
│   └── save.php           guardado rápido desde los resultados
├── includes/              lógica interna (no accesible desde el navegador)
│   ├── config.php         credenciales  ← NO se sube a Git
│   ├── config.example.php plantilla de config.php
│   ├── db.php             conexión PDO
│   ├── auth.php           sesión, usuario actual y CSRF
│   ├── consultas.php      biblioteca (filtros/orden/paginación), playlists y estadísticas
│   ├── spotify.php        token, búsqueda, consulta por ID, oEmbed y lectura de links
│   ├── genero.php         detecta el género con la API pública de Deezer
│   └── funciones.php      catálogos (estados, géneros, orden...) y helpers
├── assets/
│   ├── css/styles.css
│   └── js/app.js
└── database/
    ├── musictrack_db.sql      instalación desde cero (todas las tablas)
    ├── migracion_spotify.sql  1.ª migración: columnas de Spotify
    └── migracion_v2.sql       2.ª migración: usuarios, playlists, calificación y álbumes
```

## Base de datos

- `usuarios`: nombre, correo único y contraseña con `password_hash`.
- `canciones`: cada fila pertenece a un usuario (`usuario_id`). `spotify_id` + `spotify_tipo`
  (track/album) arman el reproductor. `UNIQUE (usuario_id, spotify_id)` evita duplicados por usuario.
- `playlists` y `playlist_canciones` (muchos a muchos). Borrar una canción o una playlist limpia la
  relación sola (`ON DELETE CASCADE`).

## Género musical

Spotify ya no entrega géneros a las apps en modo desarrollo (cambios de febrero de 2026).
Por eso, al elegir una canción o álbum, `api/genre.php` lo busca en la API pública de Deezer
(gratis, sin clave), lee los géneros del álbum y los traduce a los géneros de MusicTrack.
Es una sugerencia: el usuario puede cambiarla antes de guardar.

## Instalación

1. Crear la base `musictrack_db` (utf8mb4_spanish_ci) e importar `database/musictrack_db.sql`.
   Si ya tenías la base anterior, ejecuta en orden `migracion_spotify.sql` y `migracion_v2.sql`.
2. Copiar `includes/config.example.php` como `includes/config.php` y poner:
   - Client ID y Client Secret de la app en developer.spotify.com/dashboard
     (la cuenta dueña debe tener Spotify Premium activo).
   - Usuario y contraseña de MySQL.
3. Abrir el proyecto:
   - Con PHP: `php -S 127.0.0.1:8000` y entrar a http://127.0.0.1:8000
   - Con XAMPP: copiar la carpeta a `htdocs` y entrar a http://localhost/MUSICKTRACK_D.A_
4. Crear una cuenta. **El primer usuario que se registra se queda con las canciones que
   existían antes de haber usuarios.**

Requiere PHP 8.1 o superior con las extensiones `pdo_mysql` y `curl`.

## Seguridad

- `includes/config.php` está en `.gitignore`; el Client Secret solo se usa en el servidor.
- Contraseñas con `password_hash` / `password_verify`; la sesión se regenera al iniciar sesión.
- Todos los formularios POST y el guardado rápido llevan un token CSRF.
- Cada consulta filtra por `usuario_id`: nadie puede ver ni modificar lo de otro usuario.
- Sentencias preparadas (PDO) contra inyección SQL; el `ORDER BY` sale de una lista fija.
- `htmlspecialchars` (función `e()`) en PHP y `textContent` en JavaScript contra XSS.
- El ID de Spotify se valida con regex (22 caracteres alfanuméricos).
- `includes/`, `vistas/` y `database/` tienen `.htaccess` que bloquea el acceso directo en Apache.
