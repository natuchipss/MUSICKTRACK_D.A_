# Pruebas del software — MusicTrack

## 1. Herramientas

| Herramienta | Versión | Uso |
|---|---|---|
| PHPUnit | 11.5.58 (`phpunit-11.phar`) | Ejecución de todas las pruebas y reporte JUnit |
| Servidor integrado de PHP | PHP 8.2.12 | Levanta MusicTrack para las pruebas funcionales por HTTP |
| cURL (extensión de PHP) | — | Cliente HTTP de las pruebas funcionales, con cookie de sesión |
| MariaDB 10.4 (XAMPP) / MySQL 8 | — | Base de datos **exclusiva** para pruebas (`musictrack_test`) |

## 2. Ubicación dentro del repositorio

```
phpunit.xml                      configuración: 4 suites
tests/
├── bootstrap.php                carga la lógica (sin sesión ni conexión)
├── Soporte/
│   ├── BaseDatosPrueba.php      crea la base de pruebas con database/musictrack_db.sql y la vacía
│   ├── ServidorPrueba.php       copia el proyecto a una carpeta temporal y lo sirve con php -S
│   └── ClienteHttp.php          navegador mínimo: registro, token CSRF, GET y POST
├── Unitarias/                   CP-01 a CP-04 y CP-14 (sin base de datos ni red)
├── Integracion/                 CP-05 a CP-07 (consultas reales a MySQL/MariaDB)
├── Funcionales/                 CP-08 a CP-12, CP-16 y CP-17 (formularios y API por HTTP)
└── Externas/                    CP-13 (Deezer) y CP-15 (Spotify Web API), con internet
```

Las pruebas **nunca usan la base real** `musictrack_db`: si `MT_TEST_DB_NAME` no está definida o vale
`musictrack_db`, las pruebas de integración y las funcionales se omiten. Las funcionales tampoco leen
`includes/config.php`: el servidor de pruebas recibe un `config.php` propio que apunta a la base de
pruebas.

## 3. Comandos de ejecución

1. Crear la base de pruebas una sola vez (con la consola de MySQL de XAMPP):

   ```powershell
   C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE musictrack_test CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci"
   ```

2. Indicar la conexión y ejecutar todas las pruebas:

   ```powershell
   $env:MT_TEST_DB_NAME = 'musictrack_test'; $env:MT_TEST_DB_PORT = '3306'
   C:\xampp\php\php.exe C:\herramientas-php\phpunit-11.phar --testdox
   ```

3. Otras formas útiles:

   ```powershell
   C:\xampp\php\php.exe C:\herramientas-php\phpunit-11.phar --testsuite unitarias
   ```

   ```powershell
   $env:MT_SIN_RED = '1'; C:\xampp\php\php.exe C:\herramientas-php\phpunit-11.phar
   ```

   ```powershell
   C:\xampp\php\php.exe C:\herramientas-php\phpunit-11.phar --testdox --log-junit docs\evidencias\despues\pruebas-junit.xml
   ```

   `MT_SIN_RED=1` omite las pruebas que necesitan internet. Las variables opcionales son
   `MT_TEST_DB_HOST` (por defecto `127.0.0.1`), `MT_TEST_DB_USER` (`root`) y `MT_TEST_DB_PASS` (vacía).

Para las evidencias de este documento se usó una instancia de MariaDB de XAMPP en el puerto 3307, con
una carpeta de datos propia, para no tocar la base del proyecto.

## 4. Casos de prueba

"Línea base" indica si la prueba ya existía cuando se ejecutó contra el código original (`5f0bb3a`).
Las que se agregaron después prueban el código nuevo o los defectos corregidos.

| ID | Archivo | Qué verifica | Tipo (según la guía) | Línea base | Resultado esperado |
|---|---|---|---|---|---|
| CP-01 | `Unitarias/SpotifyTest.php` | Lectura de links y URI de Spotify; rechaza playlists, IDs mal formados y texto suelto (6 casos) | Validación de información | Sí | Devuelve `tipo` e `id`, o `null` |
| CP-02 | `Unitarias/SpotifyTest.php` | Conversión de la respuesta de Spotify al modelo de MusicTrack (artistas unidos, portada, campos faltantes) | Integración externa (mapeo) | Sí | Arreglo con `id`, `tipo`, `name`, `artist`, `image` |
| CP-03 | `Unitarias/GeneroTest.php` | Traducción de los géneros de Deezer a los de MusicTrack (14 casos) y que todo resultado exista en `GENEROS` | Reglas de negocio | Sí | Clave de género correcta o `null` |
| CP-04 | `Unitarias/FiltrosTest.php` | Filtros de la URL: valores inválidos, intento de inyección en `orden`, arreglos, paginación y URL limpia | Validación de información | 3 de 5 casos | Valores seguros por defecto |
| CP-05 | `Integracion/BibliotecaTest.php` | Cada usuario ve solo su biblioteca; filtro por estado; búsqueda por nombre de género; `%` no actúa como comodín; 9 por página | Almacenamiento y recuperación | Sí | Solo las filas propias y filtradas |
| CP-06 | `Integracion/BibliotecaTest.php` | Estadísticas: conteos por estado, porcentaje, colaboraciones contadas para el artista principal, promedio | Reglas de negocio | Sí | 1/1/2, 50 %, "Feid" con 2, promedio 4,5 |
| CP-07 | `Integracion/BibliotecaTest.php` | `UNIQUE (usuario_id, spotify_id)` y `ON DELETE CASCADE` con playlists | Almacenamiento de datos | Sí | Error `23000` para el duplicado; la relación se borra sola |
| CP-08 | `Funcionales/AccionesTest.php` | `acciones/registrar.php` guarda una canción válida y rechaza prioridad inválida o título vacío | Funcionalidad + validación | Sí | 302 `ok=registrado`; inválidos con `ok=incompleto` y sin filas nuevas |
| CP-09 | `Funcionales/AccionesTest.php` | `acciones/actualizar.php` edita la canción propia, no la ajena, y rechaza calificación 9 | Reglas de negocio | Sí | Cambios solo en la canción propia |
| CP-10 | `Funcionales/AccionesTest.php` | `acciones/estado.php`: un estado válido se guarda; uno inválido se rechaza **avisando** al usuario | Manejo de errores | Sí | `ok=incompleto`, nunca `ok=actualizado` sin guardar |
| CP-11 | `Funcionales/AccionesTest.php` | `acciones/calificar.php`: de 1 a 5 se guarda, 7 no, vacío no, 0 quita la calificación | Validación de información | Sí (el caso "vacío" se agregó con R5) | Calificación esperada en la base |
| CP-12 | `Funcionales/AccionesTest.php` | Sin token CSRF → 403; API sin sesión → 401; guardado rápido sin token → 403 | Manejo de errores (seguridad) | Sí | Códigos HTTP y ninguna fila creada |
| CP-13 | `Externas/DeezerTest.php` | Consulta real a Deezer: "Tusa" de Karol G es reggaetón; un artista inexistente devuelve `null` | Integración externa | Sí | `reggaeton` / `null` sin excepciones |
| CP-14 | `Unitarias/ValidacionTest.php` | `validar_cancion()` y `calificacion_valida()` (R1): limpieza, catálogos, arreglos, largo máximo | Validación de información | No (R1) | Datos limpios o `null` |
| CP-15 | `Externas/SpotifyApiTest.php` | Búsqueda real en Spotify Web API por `http.php` (R2); un ID inexistente da excepción controlada | Integración externa | No (R2) | Lista JSON con IDs de 22 caracteres; `RuntimeException` con HTTP 400/404 |
| CP-16 | `Funcionales/AccionesTest.php` | La página principal se muestra con sesión, sin salida antes del `<!DOCTYPE>`, y escapa el HTML | Funcionalidad (regresión de vistas) | No (R6) | 200, "Has escuchado 0 de 1", `&lt;b&gt;` |
| CP-17 | `Funcionales/AccionesTest.php` | `?ok[]=x`, `?q[]=x`, `?error[]=1` y `email[]=x` no provocan errores fatales | Manejo de errores (seguridad) | No (R5) | 200 sin "Fatal error"; login redirige a `error=credenciales` |

Algunos ID agrupan varios métodos o casos de datos: por eso 17 casos producen 46 pruebas en PHPUnit.

## 5. Resultados

### Antes de la refactorización (código del commit `5f0bb3a`)

```
Tests: 37, Assertions: 91, Failures: 1.
```

| Suite | Pruebas | Aprobadas | Fallidas | Omitidas |
|---|---:|---:|---:|---:|
| Unitarias (CP-01 a CP-04) | 26 | 26 | 0 | 0 |
| Integración (CP-05 a CP-07) | 4 | 4 | 0 | 0 |
| Funcionales (CP-08 a CP-12) | 5 | 4 | **1** | 0 |
| Externas (CP-13) | 2 | 2 | 0 | 0 |
| **Total** | **37** | **36** | **1** | **0** |

La prueba que falló es **CP-10**. Detectó un defecto real del código original:

```
✘ CP-10 Cambiar estado: un estado válido se guarda y uno inválido se rechaza avisando al usuario
  no debe decir "Cambios guardados" si no guardó nada
  Failed asserting that '../index.php?ok=actualizado#cancion-1' does not contain "ok=actualizado".
```

Evidencia completa: [`evidencias/antes/pruebas.txt`](evidencias/antes/pruebas.txt) y
[`evidencias/antes/pruebas-junit.xml`](evidencias/antes/pruebas-junit.xml).

### Durante la refactorización

Las pruebas se ejecutaron después de cada commit (R1 a R7). CP-10 siguió fallando, como se esperaba,
hasta R5. Ninguna otra prueba falló por el código; la única excepción fue un error 503 temporal de
Spotify en CP-15 durante R2 (ver sección 7). Durante R1, CP-14 encontró que un campo enviado
como arreglo producía el aviso *Array to string conversion*, y se corrigió con `campo_texto()` antes
del commit.

### Después de la refactorización (commit `cf7cea2`)

```
OK (46 tests, 135 assertions)
```

| Suite | Pruebas | Aprobadas | Fallidas | Omitidas |
|---|---:|---:|---:|---:|
| Unitarias (CP-01 a CP-04, CP-14) | 31 | 31 | 0 | 0 |
| Integración (CP-05 a CP-07) | 4 | 4 | 0 | 0 |
| Funcionales (CP-08 a CP-12, CP-16, CP-17) | 7 | 7 | 0 | 0 |
| Externas (CP-13, CP-15) | 4 | 4 | 0 | 0 |
| **Total** | **46** | **46** | **0** | **0** |

Evidencia completa: [`evidencias/despues/pruebas.txt`](evidencias/despues/pruebas.txt) y
[`evidencias/despues/pruebas-junit.xml`](evidencias/despues/pruebas-junit.xml).

### Comprobaciones de las pruebas nuevas contra el código anterior

Para asegurar que las pruebas nuevas detectan los defectos y no solo pasan, se ejecutó CP-17 sobre
una copia del código anterior a R5:

```
Failed asserting that '...<b>Fatal error</b>: Uncaught TypeError: trim(): Argument #1 ($string)
must be of type string, array given in ...\api\search.php:8' does not contain "Fatal error".
```

Con el código final, la misma prueba aprueba.

## 6. Resumen

| | Antes | Después |
|---|---:|---:|
| Pruebas ejecutadas | 37 | 46 |
| Aprobadas | 36 | 46 |
| Fallidas | 1 | 0 |
| Aserciones | 91 | 135 |
| Tiempo de ejecución | 7,2 s | 7,8 s |

## 7. Limitaciones

- **CP-15** necesita credenciales de Spotify en `includes/config.php` e internet. Sin ellas se omite (no
  falla). Durante el trabajo, Spotify respondió una vez `HTTP 503 Turnstile Unavailable`: fue un fallo
  temporal del servicio, porque el código original y el nuevo dieron el mismo resultado al repetir la
  consulta.
- **`assets/js/app.js` no tiene pruebas automatizadas.** Su efecto se cubre en parte porque CP-08 y
  CP-12 prueban los endpoints que el JavaScript llama.
- **No se mide la cobertura de código:** necesita Xdebug o PCOV, que no vienen con XAMPP.
