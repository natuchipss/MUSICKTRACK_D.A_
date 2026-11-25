# Evaluación del software — MusicTrack

Guía Práctica N.º 11 · Arquitectura y Diseño del Software · FESC

Este documento describe cómo se evaluó el código fuente de MusicTrack, con qué herramientas y
qué problemas se encontraron en la **evaluación inicial**. Las métricas comparadas están en
[METRICAS_SOFTWARE.md](METRICAS_SOFTWARE.md), las mejoras en [REFACTORIZACION.md](REFACTORIZACION.md),
las pruebas en [PRUEBAS_SOFTWARE.md](PRUEBAS_SOFTWARE.md) y lo pendiente en [MANTENIMIENTO.md](MANTENIMIENTO.md).

## 1. Proceso de evaluación

1. **Línea base congelada.** La evaluación inicial se hizo sobre el commit `5f0bb3a`, el último antes
   de cualquier cambio de la Guía 11. Se exportó con `git archive` para medir exactamente lo que estaba
   en el repositorio.
2. **Configuración fija.** Antes de medir se escribieron los archivos de configuración
   (`phpcs.xml`, `phpstan.neon`, `phpmd.xml`, `tools/complejidad.xml`) y el script
   `tools/analizar.ps1`. Se usaron **sin cambios** antes y después de refactorizar.
3. **Pruebas antes de tocar el código.** Se escribieron 13 casos de prueba sobre el comportamiento
   existente y se ejecutaron sobre el código original (ver [PRUEBAS_SOFTWARE.md](PRUEBAS_SOFTWARE.md)).
4. **Revisión manual.** Los resultados de las herramientas se revisaron a mano, junto con una revisión
   de seguridad de las entradas (`$_GET`, `$_POST`) y de los archivos que se publican en Git.
5. **Refactorización** en commits separados (R1 a R7), con las pruebas ejecutadas después de cada uno.
6. **Evaluación posterior** con las mismas herramientas sobre el commit final, exportado igual que la línea base.

### Alcance

| Se analizó | No se analizó (y por qué) |
|---|---|
| `acciones/`, `api/`, `includes/`, `vistas/`, `index.php`, `login.php`, `registro.php` (35 archivos PHP escritos por el equipo) | `includes/config.php`: credenciales locales, no está en Git |
| | `assets/css/styles.css` y `assets/js/app.js`: las herramientas elegidas son de PHP. Se contaron sus líneas (3455 y 283), pero no se midió su calidad (ver [MANTENIMIENTO.md](MANTENIMIENTO.md)) |
| | `tests/` y `tools/`: código de apoyo, no del producto |
| | No hay dependencias de terceros: el proyecto no usa Composer ni npm |

## 2. Herramientas utilizadas

El proyecto es PHP 8.2 sin framework, con MySQL. Las herramientas se eligieron de la tabla 2.16 de la
guía según esa tecnología.

| Herramienta | Versión | Para qué se usó | Indicadores que entrega |
|---|---|---|---|
| **phploc** | 7.0.2 | Tamaño del código | Archivos, LOC, líneas de comentario, LLOC, funciones |
| **phpcpd** | 6.0.3 | Duplicidad | Clones, líneas duplicadas y porcentaje |
| **PHPStan** | 2.3.7 | Análisis estático de errores y tipos | Problemas de calidad (variables indefinidas, tipos) |
| **PHPMD** | 3.0.0 | Problemas de diseño y complejidad | Complejidad ciclomática, NPath, *code smells* |
| **PHP_CodeSniffer** | 4.0.4 | Estándar de programación PSR-12 | Incumplimientos del estándar |
| **PHPUnit** | 11.5.58 | Pruebas automatizadas | Pruebas ejecutadas, aprobadas y fallidas |
| Revisión manual | — | Seguridad y defectos | Posibles vulnerabilidades |

Las herramientas son archivos `.phar` oficiales y **no se guardan en el repositorio** (pesan 46 MB).
Se descargan una vez con:

```powershell
New-Item -ItemType Directory -Force C:\herramientas-php
Set-Location C:\herramientas-php
curl.exe -LO https://phar.phpunit.de/phploc-7.0.2.phar
curl.exe -LO https://phar.phpunit.de/phpcpd-6.0.3.phar
curl.exe -LO https://github.com/phpstan/phpstan/releases/latest/download/phpstan.phar
curl.exe -LO https://github.com/phpmd/phpmd/releases/latest/download/phpmd.phar
curl.exe -LO https://phars.phpcodesniffer.com/phpcs.phar
curl.exe -LO https://phars.phpcodesniffer.com/phpcbf.phar
curl.exe -LO https://phar.phpunit.de/phpunit-11.phar
```

**No se usó SonarQube** porque necesita un servidor propio con Java y base de datos. Por eso la deuda
técnica se estimó a mano (sección 5 de [METRICAS_SOFTWARE.md](METRICAS_SOFTWARE.md)).

## 3. Comandos ejecutados

Todo el análisis lo ejecuta un solo script, que guarda los reportes en `docs/evidencias/<etapa>/`:

```powershell
.\tools\analizar.ps1 -Etapa despues -Herramientas C:\herramientas-php
```

Para repetir la medición inicial sobre la línea base:

```powershell
git archive 5f0bb3a -o $env:TEMP\antes.zip
Expand-Archive $env:TEMP\antes.zip $env:TEMP\antes -Force
Copy-Item phpcs.xml, phpstan.neon, phpmd.xml $env:TEMP\antes
Copy-Item tools $env:TEMP\antes -Recurse -Force
.\tools\analizar.ps1 -Etapa antes -Herramientas C:\herramientas-php -Raiz $env:TEMP\antes
```

Estos son los comandos que ejecuta el script, desde la raíz del código:

```powershell
php phploc-7.0.2.phar --exclude includes/config.php acciones api includes vistas index.php login.php registro.php
php phpcpd-6.0.3.phar --min-lines 4 --min-tokens 30 --exclude includes/config.php acciones api includes vistas index.php login.php registro.php
php phpstan.phar analyse -c phpstan.neon --no-progress --error-format raw
php phpmd.phar analyze --ruleset phpmd.xml --format text acciones api includes vistas index.php login.php registro.php
php phpmd.phar analyze --ruleset tools/complejidad.xml --format text acciones api includes vistas index.php login.php registro.php
php phpcs.phar --standard=phpcs.xml --report=summary acciones api includes vistas index.php login.php registro.php
php phpcs.phar --standard=phpcs.xml --report=source acciones api includes vistas index.php login.php registro.php
```

## 4. Configuración del análisis

| Archivo | Configuración | Justificación |
|---|---|---|
| [`phpstan.neon`](../phpstan.neon) | Nivel 6 de 10 | Exige tipos en parámetros y retornos sin obligar a reescribir el proyecto con clases |
| [`phpmd.xml`](../phpmd.xml) | Los 6 conjuntos de reglas con los umbrales por defecto (complejidad 10, NPath 200), sin la regla `Superglobals` | El proyecto es PHP procedimental: leer `$_POST` en los puntos de entrada es su diseño |
| [`tools/complejidad.xml`](../tools/complejidad.xml) | `CyclomaticComplexity` con `reportLevel = 1` | Para obtener la complejidad de **cada** función, no solo de las que pasan de 10 |
| [`phpcs.xml`](../phpcs.xml) | PSR-12 sin `Generic.Files.LineEndings`; en `vistas/` sin las reglas de firma de control, encabezado y largo de línea | Git guarda LF y Windows entrega CRLF (medirlo contaría el entorno, no el código). Las vistas son plantillas HTML con `if: … endif;`, que esas reglas no contemplan |
| `phpcpd` | Bloques de 4 líneas o 30 *tokens* | El valor por defecto (5 líneas, 70 *tokens*) no detecta nada en un proyecto de este tamaño |

## 5. Resultados de la evaluación inicial (commit `5f0bb3a`)

| Indicador | Resultado | Fuente |
|---|---|---|
| Archivos de código fuente | 33 archivos PHP | phploc |
| Líneas de código (LOC) | 1894 (103 de comentarios, 5,44 %) | phploc |
| Líneas lógicas (LLOC) | 468 | phploc |
| Funciones | 32 | phploc |
| Complejidad ciclomática | Promedio 3,16 por función; máxima 11 (`detectar_genero`); 1 función alta y 4 moderadas | PHPMD |
| Porcentaje de duplicidad | 0,74 % (2 clones, 14 líneas) | phpcpd |
| Problemas de calidad | 88 (65 variables indefinidas en vistas, 23 arreglos sin tipo) | PHPStan |
| Problemas de diseño | 33 | PHPMD |
| Incumplimiento de estándares | 84 (78 errores y 6 advertencias PSR-12) | PHP_CodeSniffer |
| Posibles vulnerabilidades | 2 tipos en 13 puntos (datos personales publicados; 12 entradas con error fatal) | Revisión manual |
| Pruebas | 36 de 37 aprobadas | PHPUnit |
| Deuda técnica estimada | 876 minutos (≈ 14,6 h) | Estimación manual |

Detalle de PHPMD: 14 `ShortVariable`, 9 `ExitExpression`, 5 `MissingImport`, 2 `ShortMethodName`,
1 `BooleanArgumentFlag`, 1 `CyclomaticComplexity` y 1 `NPathComplexity`.

## 6. Problemas identificados

Son problemas reales del código de la línea base. Los números de línea corresponden al commit `5f0bb3a`.

| # | Archivo / componente | Ubicación | Tipo | Causa | Impacto en la calidad | Solución propuesta | Mejora |
|---|---|---|---|---|---|---|---|
| P1 | `acciones/registrar.php` y `acciones/actualizar.php` | Líneas 5-12 y 44-51 de `registrar.php`; 7-14 y 29-36 de `actualizar.php` | *Duplicate Code*, *Magic Numbers* | La validación de los 7 campos de una canción se copió de un archivo al otro, con los largos `150` y `500` escritos a mano | Mantenibilidad: una regla nueva se debe cambiar en dos lugares, y si se olvida uno quedan desincronizados | Extraer `validar_cancion()` a `includes/validacion.php` y usar constantes con nombre | [R1](REFACTORIZACION.md#r1) |
| P2 | `includes/spotify.php`, `includes/genero.php` | `spotify_token()` (l. 5), `spotify_get()` (l. 54), `spotify_oembed()` (l. 110), `deezer_get()` (l. 8) | *Duplicate Code* (estructural), falta de abstracción | Cada función armaba su propia petición cURL, con tiempos de espera distintos escritos a mano (10 y 6) | Acoplamiento: cambiar el manejo de errores o del certificado exige tocar 4 funciones | Un solo cliente HTTP (`includes/http.php`) | [R2](REFACTORIZACION.md#r2) |
| P3 | `includes/genero.php` | `mapear_genero()` (l. 31) y `detectar_genero()` (l. 51) | *Long Method*, complejidad alta | 8 `if` encadenados con `return` en una línea; búsqueda, comparación y lectura del álbum en una sola función | Complejidad 11 y NPath 384 (sobre los umbrales 10 y 200): es difícil de probar y de cambiar | Reglas en una tabla de datos y extracción de `mismo_artista()` y `buscar_album_deezer()` | [R3](REFACTORIZACION.md#r3) |
| P4 | `includes/consultas.php` | `consultar_biblioteca()` (l. 16-67) y `leer_filtros()` (l. 5) | *Long Method*, baja cohesión | Una función de 52 líneas arma el WHERE, la búsqueda por género, la paginación y las dos consultas | La paginación no se puede probar sin base de datos; `?estado[]=x` lanzaba `TypeError` | Extraer `condicion_busqueda()`, `filtro_biblioteca()` y `paginar()` | [R4](REFACTORIZACION.md#r4) |
| P5 | `acciones/estado.php`, `acciones/calificar.php` y 12 puntos de entrada | `estado.php` l. 9-14; `calificar.php` l. 7-13; `trim($_GET[...])` en `api/*.php`, `acciones/login.php`… | Defecto funcional y validación de entrada incorrecta | Un dato inválido se ignoraba, pero la respuesta decía "Cambios guardados"; un parámetro enviado como arreglo llegaba a `trim()` | Fiabilidad y seguridad: el usuario cree que guardó; un visitante puede provocar errores fatales que muestran rutas del servidor | Cláusulas de guarda con aviso de error, y `campo_texto()` / `campo_crudo()` para toda entrada | [R5](REFACTORIZACION.md#r5) |
| P6 | Todo el código PHP | 84 infracciones PSR-12; 88 avisos de PHPStan | Incumplimiento de estándares | Llaves de función en la misma línea, `if` sin llaves, arreglos sin tipo y vistas que usan variables sin declarar | Comprensión: ni el análisis estático ni una persona nueva saben qué recibe cada vista | `phpcbf` en los archivos de lógica, PHPDoc con la forma de los arreglos y `@var` en cada vista | [R6](REFACTORIZACION.md#r6) |
| P7 | `database/musictrack_db.sql` | Línea 133 (`INSERT INTO usuarios`) | Vulnerabilidad: exposición de datos personales | El volcado de la base se exportó con una cuenta real | Seguridad: nombre, correo y hash de la contraseña públicos en GitHub e instalados en cada base nueva | Quitar el `INSERT` y los `AUTO_INCREMENT` del script | [R7](REFACTORIZACION.md#r7) |

### Otros hallazgos que no se corrigieron

Se registraron como deuda técnica en [MANTENIMIENTO.md](MANTENIMIENTO.md):

- **`ExitExpression` (9):** `volver()`, `accion_protegida()` y otras terminan con `exit`. En PHP sin
  framework es la forma de redirigir. Cambiarlo exige objetos de respuesta, así que se dejó para una
  versión con arquitectura por capas.
- **`ShortMethodName`:** `e()` y `db()` son nombres cortos a propósito: `e()` aparece 60 veces en las
  vistas, y un nombre largo las haría más difíciles de leer.
- **PSR-1 *Side effects*:** `includes/auth.php` inicia la sesión al incluirse, y algunos archivos de
  `includes/` hacen `require_once`.
- **Sin protección contra fuerza bruta** en el inicio de sesión, y la cookie de sesión sin `secure`
  (no hay HTTPS en el entorno local).
- **El informe del proyecto describe React/Node/PostgreSQL**, pero el código es PHP/MySQL (ver la
  sección 3 de [MANTENIMIENTO.md](MANTENIMIENTO.md)).

## 7. Evidencias

Todas las salidas de las herramientas están versionadas en el repositorio:

| Evidencia | Antes (`5f0bb3a`) | Después |
|---|---|---|
| Tamaño (phploc) | [phploc.txt](evidencias/antes/phploc.txt) | [phploc.txt](evidencias/despues/phploc.txt) |
| Duplicidad (phpcpd) | [phpcpd.txt](evidencias/antes/phpcpd.txt) | [phpcpd.txt](evidencias/despues/phpcpd.txt) |
| Calidad (PHPStan) | [phpstan.txt](evidencias/antes/phpstan.txt) | [phpstan.txt](evidencias/despues/phpstan.txt) (vacío: 0 errores) |
| Diseño (PHPMD) | [phpmd.txt](evidencias/antes/phpmd.txt) | [phpmd.txt](evidencias/despues/phpmd.txt) |
| Complejidad por función | [complejidad.txt](evidencias/antes/complejidad.txt) | [complejidad.txt](evidencias/despues/complejidad.txt) |
| Estándar PSR-12 | [phpcs.txt](evidencias/antes/phpcs.txt), [phpcs-reglas.txt](evidencias/antes/phpcs-reglas.txt) | [phpcs.txt](evidencias/despues/phpcs.txt), [phpcs-reglas.txt](evidencias/despues/phpcs-reglas.txt) |
| Pruebas (PHPUnit) | [pruebas.txt](evidencias/antes/pruebas.txt), [pruebas-junit.xml](evidencias/antes/pruebas-junit.xml) | [pruebas.txt](evidencias/despues/pruebas.txt), [pruebas-junit.xml](evidencias/despues/pruebas-junit.xml) |

Para verificarlas, basta con volver a ejecutar `tools/analizar.ps1`, que debe producir los mismos números.
