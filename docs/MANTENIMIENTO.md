# Mantenimiento del software — MusicTrack

## 1. Estado actual del software

MusicTrack es una aplicación web en **PHP 8.2 sin framework con MySQL 8**, que se ejecuta con XAMPP o
con Docker (Apache con PHP y MySQL). Integra Spotify Web API (búsqueda, links y reproductor embebido)
y Deezer (sugerencia de género).

| Aspecto | Estado tras la Guía 11 |
|---|---|
| Funcionalidad | Cuentas, biblioteca por usuario, búsqueda e importación desde Spotify, estados, prioridades, calificación, playlists y estadísticas. Todo funciona y está cubierto por 46 pruebas que pasan |
| Calidad estática | PHPStan nivel 6 sin errores; sin duplicidad; ninguna función con complejidad mayor que 8; 5 avisos PSR-12 |
| Seguridad | CSRF en todos los POST, sentencias preparadas, `password_hash`, escape de HTML y filtrado por `usuario_id`. Las entradas manipuladas ya no provocan errores fatales |
| Pruebas | 17 casos (46 pruebas en PHPUnit): unitarias, de integración con la base de datos, funcionales por HTTP y de integración con Deezer y Spotify |
| Deuda técnica estimada | ≈ 288 minutos (4,8 h), frente a 876 min al empezar (ver [METRICAS_SOFTWARE.md](METRICAS_SOFTWARE.md)) |

## 2. Deuda técnica pendiente

| # | Deuda | Dónde | Origen | Estimación |
|---|---|---|---|---:|
| D1 | Datos personales en el historial de Git (el commit `6318463` los quitó del archivo, pero siguen en los commits anteriores, ya publicados en GitHub) | `database/musictrack_db.sql` antes de `6318463` | R7 | 30 min |
| D2 | Se eliminó `registro.php` (raíz), pero `vistas/auth.php` sigue enlazando a él ("¿No tienes cuenta? Regístrate") y conserva el modo `registro`: el enlace da 404 y no se pueden crear cuentas desde la interfaz | `vistas/auth.php`, línea 72 | Decisión del equipo | 15 min |
| D3 | 9 funciones terminan con `exit` (`volver()`, `accion_protegida()`, `api_protegida()`, `requerir_login()`, `db()`, `error_registro()`): obligan a probar las acciones solo por HTTP | `includes/auth.php`, `includes/funciones.php`, `includes/db.php` | PHPMD `ExitExpression` | 135 min |
| D4 | `accion_protegida(bool $requiereUsuario)`: un parámetro booleano que cambia el comportamiento | `includes/auth.php` | PHPMD `BooleanArgumentFlag` | 20 min |
| D5 | Archivos que mezclan declaraciones con efectos secundarios (`session_start()`, `require_once`), por no tener carga automática | `includes/auth.php`, `genero.php`, `spotify.php`, `acciones/registro.php` | PSR-1 | 60 min |
| D6 | Nombres cortos que quedan: `$c` (db.php), `$k`/`$v` (url_biblioteca), `$ch`/`$r` (http.php, spotify.php) | varios | PHPMD `ShortVariable` | 14 min |
| D7 | `url_biblioteca()` tiene complejidad 8, la más alta del proyecto | `includes/funciones.php` | PHPMD | 20 min |
| D8 | **El código no coincide con el informe de arquitectura.** El informe describe React/React Native, Node.js/Express, PostgreSQL/Supabase, JWT, `SeguimientoService` y el patrón Adapter con `CatalogoConsultaInterface`; el código es PHP procedimental con MySQL y sesiones | Informe del proyecto (apartados 3 a 10) | Revisión del proyecto | — |
| D9 | `assets/js/app.js` (283 líneas) y `styles.css` (3455 líneas) sin análisis estático ni pruebas | `assets/` | Alcance de la evaluación | 120 min |
| D10 | Funciones del alcance sin implementar: favoritos, rol de administrador, historial de escucha, fecha de finalización y caché de metadatos | — | Informe del proyecto | — |

## 3. Problemas que requieren atención futura

1. **Seguridad del inicio de sesión:** no hay límite de intentos (fuerza bruta), y la cookie de sesión
   no tiene `secure` porque el entorno no usa HTTPS.
2. **Token de Spotify en la carpeta temporal compartida** (`sys_get_temp_dir()/spotify_token.json`): en
   un servidor compartido, otro usuario del sistema podría leerlo.
3. **Coherencia entre el informe y el código (D8):** hay que decidir si el informe se adapta al código
   real (PHP/MySQL) o el código migra a la arquitectura descrita. Hoy cualquier lector del informe
   espera clases y servicios que no existen.
4. **Dependencia de Spotify Development Mode:** si vence el Premium de la cuenta dueña o Spotify cambia
   los límites, la búsqueda falla (link y oEmbed siguen funcionando).
5. **README desactualizado:** menciona `migracion_spotify.sql` y `migracion_v2.sql`, que no están en
   el repositorio.

## 4. Actividades de mantenimiento propuestas

| # | Actividad | Tipo de mantenimiento | Prioridad | Deuda o problema |
|---|---|---|---|---|
| M1 | Reescribir el historial para quitar el `INSERT` de usuarios (`git filter-repo`), forzar el *push* y cambiar la contraseña de esa cuenta | Correctivo (seguridad) | **Alta** | D1 |
| M2 | Decidir el registro de cuentas: quitar el enlace y el modo `registro` de `vistas/auth.php`, o volver a crear la página con `campo_texto($_GET, 'error')` | Correctivo | **Alta** | D2 |
| M3 | Limitar los intentos de inicio de sesión (por ejemplo, 5 por cada 15 minutos por correo e IP) | Preventivo (seguridad) | **Alta** | Problema 1 |
| M4 | Alinear el informe de arquitectura con el código real, o planear la migración | Adaptativo | **Alta** | D8 |
| M5 | Actualizar el README: quitar las migraciones inexistentes y documentar pruebas y análisis | Correctivo (documentación) | Media | Problema 5 |
| M6 | Introducir Composer con carga automática PSR-4 y pasar `includes/` a clases (`Biblioteca`, `Estadisticas`, `SpotifyCliente`) | Perfectivo | Media | D5, D6 |
| M7 | Formalizar el patrón Adapter: interfaz `CatalogoMusical` implementada por `SpotifyAdapter` (y un `DeezerAdapter`), sobre `includes/http.php` | Perfectivo | Media | D8 (apartado 7 del informe) |
| M8 | Cambiar `exit` por respuestas devueltas (objeto `Respuesta` o redirección como valor), para probar las acciones sin servidor | Perfectivo | Media | D3, D4 |
| M9 | Agregar ESLint y pruebas de JavaScript (por ejemplo, Vitest) para `assets/js/app.js` | Preventivo | Media | D9 |
| M10 | Mover el token de Spotify a una carpeta del proyecto sin acceso web, o a la base de datos | Preventivo | Baja | Problema 2 |
| M11 | Simplificar `url_biblioteca()` con una lista de valores por defecto | Perfectivo | Baja | D7 |
| M12 | Ejecutar `tools/analizar.ps1` y PHPUnit en GitHub Actions en cada *push* | Preventivo | Baja | Todas |
| M13 | Implementar favoritos, historial de escucha y fecha de finalización (con la regla "solo se califica lo escuchado"), con pruebas | Perfectivo | Baja | D10 |
| M14 | Actualizar PHP 8.2 → 8.4 en Docker cuando termine su soporte de seguridad (diciembre de 2026) | Adaptativo | Baja | Entorno |

## 5. Recomendaciones para la evolución del sistema

1. **Mantener la línea base de calidad:** antes de cada entrega, ejecutar `tools/analizar.ps1` y PHPUnit,
   y no aceptar cambios que suban los errores de PHPStan o las fallas de las pruebas. Los reportes de
   `docs/evidencias` sirven de referencia.
2. **Escribir la prueba antes de corregir un defecto**, como CP-10 y CP-17: primero se comprueba que
   falla con el código actual y después se corrige.
3. **Evolucionar por capas sin reescribir todo:** primero Composer y clases (M6), luego el Adapter (M7)
   y las respuestas sin `exit` (M8). Cada paso se valida con las 46 pruebas existentes, que prueban el
   comportamiento por HTTP y no dependen de la estructura interna.
4. **Separar datos de estructura:** la base de datos de instalación solo debe crear tablas. Los datos
   de ejemplo, si se necesitan, van en un archivo aparte con cuentas ficticias.
5. **Documentar cada decisión técnica** en `docs/`, junto al código, para que el informe y el
   repositorio no vuelvan a separarse.
