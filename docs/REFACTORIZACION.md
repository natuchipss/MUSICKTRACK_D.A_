# Refactorización — MusicTrack

Este documento describe las siete mejoras implementadas en el código. Cada una tiene su propio commit
en `main`, y después de cada commit se ejecutaron todas las pruebas
(ver [PRUEBAS_SOFTWARE.md](PRUEBAS_SOFTWARE.md)). La guía pide cinco; R6 y R7 son adicionales.

| # | Mejora | Commit | Tipo de mantenimiento |
|---|---|---|---|
| [R1](#r1) | Validación de canciones unificada | `0c7e457` | Preventivo |
| [R2](#r2) | Cliente HTTP único para Spotify y Deezer | `cca311f` | Preventivo / perfectivo |
| [R3](#r3) | Detección de género con menos complejidad | `e1475a1` | Perfectivo |
| [R4](#r4) | `consultar_biblioteca()` dividida | `9d060d1` | Perfectivo y correctivo |
| [R5](#r5) | Validación con aviso de error y entradas seguras | `ddff45c`, `cf7cea2` | Correctivo |
| [R6](#r6) | Estándar PSR-12 y tipos para PHPStan | `afcd9b3` | Preventivo |
| [R7](#r7) | Datos personales fuera del script SQL | `6318463` | Correctivo (seguridad) |

Para ver un cambio completo: `git show <commit>`.

---

<a id="r1"></a>
## R1. Validación de canciones unificada

**Problema.** `acciones/registrar.php` y `acciones/actualizar.php` leían y validaban los mismos siete
campos con código copiado (phpcpd: 2 clones, 14 líneas, el 0,74 % del proyecto). Los largos de las
columnas (`150`, `500`) y la calificación máxima (`5`) estaban escritos como números sueltos.

**Archivos intervenidos:** `includes/validacion.php` (nuevo), `includes/funciones.php`,
`acciones/registrar.php`, `acciones/actualizar.php`.

**Antes**, en `acciones/registrar.php` (en `actualizar.php` estaba el mismo bloque):

```php
$titulo    = trim($_POST['titulo'] ?? '');
$artista   = trim($_POST['artista'] ?? '');
$tipo      = $_POST['tipo'] ?? '';
$genero    = $_POST['genero'] ?? '';
$prioridad = $_POST['prioridad'] ?? '';
$estado    = $_POST['estado'] ?? '';
$notas     = trim($_POST['notas'] ?? '');
// ...
$valido = $titulo !== '' && $artista !== ''
    && isset(TIPOS[$tipo], GENEROS[$genero], PRIORIDADES[$prioridad], ESTADOS[$estado]);

if (!$valido) {
    volver('incompleto', 'registrar');
}
// ...
    ':titulo'       => mb_substr($titulo, 0, 150),
    ':artista'      => mb_substr($artista, 0, 150),
```

**Después**, en `includes/validacion.php`:

```php
function validar_cancion(array $post): ?array
{
    $titulo  = campo_texto($post, 'titulo');
    $artista = campo_texto($post, 'artista');
    $notas   = campo_texto($post, 'notas');

    $datos = [
        'titulo'    => mb_substr($titulo, 0, LARGO_TITULO),
        'artista'   => mb_substr($artista, 0, LARGO_TITULO),
        'tipo'      => campo_texto($post, 'tipo'),
        'genero'    => campo_texto($post, 'genero'),
        'prioridad' => campo_texto($post, 'prioridad'),
        'estado'    => campo_texto($post, 'estado'),
        'notas'     => $notas !== '' ? $notas : null,
    ];

    $completo = $titulo !== '' && $artista !== ''
        && isset(TIPOS[$datos['tipo']], GENEROS[$datos['genero']])
        && isset(PRIORIDADES[$datos['prioridad']], ESTADOS[$datos['estado']]);

    return $completo ? $datos : null;
}
```

Y en cada acción queda una línea:

```php
$cancion = validar_cancion($_POST) ?? volver('incompleto', 'registrar');
```

**Técnicas:** extracción de función, eliminación de código duplicado, reemplazo de números mágicos
por constantes (`LARGO_TITULO`, `LARGO_URL_PORTADA`, `CALIFICACION_MAXIMA`).

**Principios:** DRY, responsabilidad única (SRP).

**Justificación.** Qué datos son válidos para una canción es una regla de negocio y debe estar en un
solo lugar. Con dos copias, agregar un campo o un género exigía tocar dos archivos, y si se olvidaba
uno, editar y agregar validaban distinto.

**Beneficios.** La duplicidad bajó de 0,74 % a 0 %, la regla se prueba sin servidor (CP-14), y las
acciones pasaron de 54 a 46 y de 41 a 34 líneas.

---

<a id="r2"></a>
## R2. Cliente HTTP único para Spotify y Deezer

**Problema.** `spotify_token()`, `spotify_get()`, `spotify_oembed()` y `deezer_get()` armaban cada una
su propia petición cURL (`curl_init`, `curl_setopt_array`, `curl_exec`, el código de estado y el
error), con tiempos de espera distintos escritos a mano (10 y 6 segundos). Es duplicación estructural:
phpcpd no la detecta porque cada copia tiene opciones algo distintas.

**Archivos intervenidos:** `includes/http.php` (nuevo), `includes/spotify.php`, `includes/genero.php`.

**Antes**, en `includes/spotify.php` (en `spotify_token()` y en `genero.php` había otras dos
versiones):

```php
function spotify_get(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . spotify_token()],
    ]);
    $respuesta = curl_exec($ch);
    $errorCurl = curl_error($ch);
    $status    = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($respuesta === false) {
        throw new RuntimeException('cURL: ' . $errorCurl);
    }
    if ($status !== 200) {
        throw new RuntimeException("Spotify respondió HTTP $status: $respuesta");
    }
    return json_decode($respuesta, true) ?? [];
}
```

**Después**, `includes/http.php` concentra la comunicación:

```php
function http_solicitud(string $url, array $cabeceras = [], ?string $cuerpoPost = null, int $segundos = 10): array
{
    $ch = curl_init($url);
    $opciones = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $segundos,
        CURLOPT_HTTPHEADER     => $cabeceras,
    ];
    if ($cuerpoPost !== null) {
        $opciones[CURLOPT_POST]       = true;
        $opciones[CURLOPT_POSTFIELDS] = $cuerpoPost;
    }
    curl_setopt_array($ch, $opciones);

    $respuesta = curl_exec($ch);
    if (!is_string($respuesta)) {
        throw new RuntimeException('cURL: ' . curl_error($ch));
    }
    return ['status' => (int) curl_getinfo($ch, CURLINFO_HTTP_CODE), 'cuerpo' => $respuesta];
}
```

Y las funciones de Spotify y Deezer solo dicen qué piden:

```php
function spotify_get(string $url): array
{
    $r = http_solicitud($url, ['Authorization: Bearer ' . spotify_token()], null, SPOTIFY_SEGUNDOS);

    if ($r['status'] !== 200) {
        throw new RuntimeException("Spotify respondió HTTP {$r['status']}: {$r['cuerpo']}");
    }
    return json_decode($r['cuerpo'], true) ?? [];
}

function deezer_get(string $url): ?array
{
    $data = http_json_o_null($url, DEEZER_SEGUNDOS);
    return $data !== null && !isset($data['error']) ? $data : null;
}
```

**Técnicas:** extracción de función, eliminación de duplicación, constantes con nombre
(`SPOTIFY_SEGUNDOS`, `DEEZER_SEGUNDOS`, `SPOTIFY_RESULTADOS`, `TOKEN_MARGEN`).

**Principios:** DRY, SRP, bajo acoplamiento: Spotify y Deezer ya no dependen de cURL, sino de
`http_solicitud()`.

**Justificación.** Un cambio en la red (proxy, certificado de XAMPP, reintentos o *logs*) ahora se hace
en un solo archivo. De paso se corrigió un defecto latente: `spotify_search()` devolvía el resultado de
`array_filter()` sin reindexar, y si Spotify mandaba un elemento vacío, `json_encode` producía un objeto
en vez de una lista y el buscador del navegador dejaba de funcionar. Ahora usa `array_values()`.

**Beneficios.** Hay un punto único de comunicación externa (es la base para un futuro patrón Adapter,
ver [MANTENIMIENTO.md](MANTENIMIENTO.md)), `spotify.php` pasa de complejidad máxima 6 a 5, y se
agregaron pruebas en vivo contra Spotify (CP-15) y Deezer (CP-13).

---

<a id="r3"></a>
## R3. Detección de género con menos complejidad

**Problema.** `mapear_genero()` encadenaba ocho `if … return` en una línea (complejidad 10, NPath 384,
por encima del umbral de 200). `detectar_genero()` hacía tres tareas: buscar en Deezer, comparar
artistas y leer el álbum (complejidad 11, por encima del umbral de 10). Era la única función de
complejidad alta del proyecto.

**Archivo intervenido:** `includes/genero.php`.

**Antes:**

```php
function mapear_genero(array $generosDeezer): ?string {
    $texto = normalizar(implode(' | ', $generosDeezer));
    $esLatino = preg_match('/latin|salsa|tropical|cumbia|vallenato|bachata|merengue|regional/', $texto);

    if (str_contains($texto, 'reggaet')) return 'reggaeton';
    if ($esLatino && preg_match('/rap|hip hop|dance|electro|r&b/', $texto)) return 'reggaeton';
    if ($esLatino) return 'latina';
    if (preg_match('/rap|hip hop|r&b/', $texto)) return 'hiphop';
    if (preg_match('/rock|metal|alternativ|punk|indie/', $texto)) return 'rock';
    if (preg_match('/electro|dance|house|techno/', $texto)) return 'electronica';
    if (preg_match('/jazz|blues|soul/', $texto)) return 'jazz';
    if (str_contains($texto, 'pop')) return 'pop';

    return null;
}
```

```php
    // dentro de detectar_genero()
    $albumId = null;
    foreach ($busqueda['data'] ?? [] as $resultado) {
        $nombre = normalizar($resultado['artist']['name'] ?? '');
        $buscado = normalizar($artistaPrincipal);
        if ($nombre !== '' && (str_contains($nombre, $buscado) || str_contains($buscado, $nombre))) {
            $albumId = $tipo === 'album' ? ($resultado['id'] ?? null) : ($resultado['album']['id'] ?? null);
            break;
        }
    }
```

**Después:** las reglas son datos, y el algoritmo es un solo ciclo:

```php
const REGLAS_GENERO = [
    ['reggaeton',   '/reggaet/', false],
    ['reggaeton',   '/rap|hip hop|dance|electro|r&b/', true],
    ['latina',      PATRON_LATINO, false],
    ['hiphop',      '/rap|hip hop|r&b/', false],
    ['rock',        '/rock|metal|alternativ|punk|indie/', false],
    ['electronica', '/electro|dance|house|techno/', false],
    ['jazz',        '/jazz|blues|soul/', false],
    ['pop',         '/pop/', false],
];

function mapear_genero(array $generosDeezer): ?string
{
    $texto    = normalizar(implode(' | ', $generosDeezer));
    $esLatino = (bool) preg_match(PATRON_LATINO, $texto);

    foreach (REGLAS_GENERO as [$genero, $patron, $exigeLatino]) {
        if ((!$exigeLatino || $esLatino) && preg_match($patron, $texto)) {
            return $genero;
        }
    }
    return null;
}
```

La búsqueda pasa a `buscar_album_deezer()` y la comparación a `mismo_artista()`, y
`detectar_genero()` queda como coordinadora:

```php
    $albumId = buscar_album_deezer($titulo, $artistaPrincipal, $tipo);
    if (!$albumId) {
        return null;
    }
    $album   = deezer_get(DEEZER_API . '/album/' . $albumId);
```

**Técnicas:** reemplazo de condicionales por una tabla de datos, extracción de funciones,
simplificación de condiciones.

**Principios:** KISS, SRP, abierto/cerrado (OCP): agregar un género es agregar una fila, sin tocar el
algoritmo.

**Justificación.** El orden de los `if` era una regla de negocio implícita ("latino + rap es
reggaetón") escondida en el flujo de control. Como tabla se lee y se revisa como lo que es: una lista
de reglas con prioridad.

**Beneficios.** La complejidad máxima del archivo bajó de 11 a 6, desaparecieron los avisos
`CyclomaticComplexity` y `NPathComplexity`, y `genero.php` pasó de rojo a amarillo en la vista
polimétrica. Los 14 casos de CP-03 y la prueba en vivo CP-13 dan los mismos resultados antes y después.

---

<a id="r4"></a>
## R4. `consultar_biblioteca()` dividida

**Problema.** `consultar_biblioteca()` (52 líneas) armaba el WHERE, la búsqueda por género, el filtro
por playlist, la paginación y las dos consultas. La paginación no se podía probar sin base de datos.
Además, `leer_filtros()` indexaba catálogos con el valor recibido: `?estado[]=x` lanzaba
`TypeError: Illegal offset type` (comprobado sobre el código original).

**Archivos intervenidos:** `includes/consultas.php`; `includes/funciones.php` (`campo_texto()`, que
pasa aquí desde `validacion.php`, y la constante `LARGO_BUSQUEDA`).

**Antes:**

```php
function leer_filtros(array $get): array {
    return [
        'q'        => mb_substr(trim((string) ($get['q'] ?? '')), 0, 100),
        'estado'   => isset(ESTADOS[$get['estado'] ?? '']) ? $get['estado'] : '',
        // ...
    ];
}

function consultar_biblioteca(PDO $pdo, int $uid, array $f): array {
    $where  = ['c.usuario_id = :uid'];
    $params = [':uid' => $uid];

    if ($f['q'] !== '') {
        $like = '%' . addcslashes($f['q'], '%_\\') . '%';
        // ... 14 líneas de búsqueda por título, artista y género
    }
    if ($f['estado'] !== '') { /* ... */ }
    if ($f['playlist'] > 0) { /* ... */ }

    $sqlWhere = implode(' AND ', $where);
    // ... COUNT(*)
    $paginas = max(1, (int) ceil($total / POR_PAGINA));
    $pagina  = min($f['pagina'], $paginas);
    $offset  = ($pagina - 1) * POR_PAGINA;
    // ... SELECT con LIMIT/OFFSET
}
```

**Después:** cada función tiene una tarea, y la principal solo las coordina:

```php
function consultar_biblioteca(PDO $pdo, int $uid, array $filtros): array
{
    [$sqlWhere, $params] = filtro_biblioteca($uid, $filtros);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM canciones c WHERE $sqlWhere");
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    $pag   = paginar($total, $filtros['pagina']);
    $orden = ORDEN_SQL[$filtros['orden']];

    $stmt = $pdo->prepare("SELECT c.* FROM canciones c WHERE $sqlWhere ORDER BY $orden
                           LIMIT " . POR_PAGINA . " OFFSET {$pag['offset']}");
    $stmt->execute($params);

    return ['items' => $stmt->fetchAll(), 'total' => $total, 'paginas' => $pag['paginas'], 'pagina' => $pag['pagina']];
}

function paginar(int $total, int $paginaPedida): array
{
    $paginas = max(1, (int) ceil($total / POR_PAGINA));
    $pagina  = min(max(1, $paginaPedida), $paginas);

    return ['pagina' => $pagina, 'paginas' => $paginas, 'offset' => ($pagina - 1) * POR_PAGINA];
}
```

`condicion_busqueda()` arma la búsqueda por título, artista y género, y `filtro_biblioteca()` el
WHERE completo. `leer_filtros()` lee cada valor con `campo_texto()`, que trata un arreglo como vacío.

**Técnicas:** extracción de funciones, renombramiento (`$f` → `$filtros`, `$n` → `$nombre`),
número mágico `100` → `LARGO_BUSQUEDA`.

**Principios:** SRP, alta cohesión, KISS.

**Justificación.** Separar "qué filtrar" de "cómo paginar" y de "cómo consultar" permite probar cada
parte por separado y cambiar una sin leer las otras.

**Beneficios.** La complejidad máxima de `consultas.php` bajó de 6 a 4, la paginación se prueba sin
base de datos (CP-04), y se corrigió el error fatal por `?estado[]=x`. CP-05 y CP-06 confirman que la
biblioteca, los filtros y la paginación responden igual.

---

<a id="r5"></a>
## R5. Validación con aviso de error y entradas seguras

**Problema 1 (lo detectó la prueba CP-10 en la línea base).** Si `acciones/estado.php` recibía un estado
que no existe, no guardaba nada, pero redirigía con `ok=actualizado` y el usuario veía "Cambios
guardados". `acciones/calificar.php` ignoraba sin avisar los valores fuera de rango.

**Problema 2 (revisión de seguridad).** Doce puntos de entrada pasaban `$_GET`/`$_POST` directo a
`trim()` o los usaban como índice. Un parámetro enviado como arreglo (`?ok[]=x`, `email[]=x`) provocaba
un `TypeError` fatal que, con `display_errors` activo, mostraba rutas internas del servidor.

**Archivos intervenidos:** `acciones/estado.php`, `acciones/calificar.php`, `acciones/login.php`,
`acciones/registro.php`, `acciones/registrar.php`, `acciones/playlist_*.php`, `api/genre.php`,
`api/link.php`, `api/search.php`, `includes/funciones.php`, `index.php`, `login.php`.

**Antes**, en `acciones/estado.php`:

```php
$id     = (int) ($_POST['id'] ?? 0);
$estado = $_POST['estado'] ?? '';

if ($id > 0 && isset(ESTADOS[$estado])) {
    db()->prepare('UPDATE canciones SET estado = :estado WHERE id = :id AND usuario_id = :uid')
        ->execute([':estado' => $estado, ':id' => $id, ':uid' => usuario_id()]);
}

volver('actualizado', "cancion-$id");   // ← se dice "guardado" aunque no se guardó
```

Y en `api/search.php`:

```php
$q = trim($_GET['q'] ?? '');            // ← ?q[]=x provoca TypeError
```

**Después:**

```php
$id     = (int) ($_POST['id'] ?? 0);
$estado = campo_texto($_POST, 'estado');

// Cláusula de guarda: si el dato no es válido se avisa en lugar de decir "Cambios guardados"
if ($id <= 0 || !isset(ESTADOS[$estado])) {
    volver('incompleto', "cancion-$id");
}

db()->prepare('UPDATE canciones SET estado = :estado WHERE id = :id AND usuario_id = :uid')
    ->execute([':estado' => $estado, ':id' => $id, ':uid' => usuario_id()]);

volver('actualizado', "cancion-$id");
```

```php
// includes/funciones.php
function campo_texto(array $datos, string $campo): string
{
    $valor = $datos[$campo] ?? '';
    return is_scalar($valor) ? trim((string) $valor) : '';
}

function campo_crudo(array $datos, string $campo): string   // sin trim: contraseñas
{
    $valor = $datos[$campo] ?? '';
    return is_scalar($valor) ? (string) $valor : '';
}
```

```php
// acciones/calificar.php: falta, "abc" o un arreglo ya no se convierten en un número
$calificacion = filter_var($_POST['calificacion'] ?? null, FILTER_VALIDATE_INT);

if ($id <= 0 || $calificacion === false || !calificacion_valida($calificacion)) {
    volver('incompleto', "cancion-$id");
}
```

**Técnicas:** cláusulas de guarda (*guard clauses*), extracción de función, simplificación de
condicionales.

**Principios:** *fail fast*, DRY (un solo punto de lectura de entradas), validación en el servidor.

**Justificación.** Un mensaje de éxito falso es peor que un error: el usuario confía en un cambio que
no existe. Y nada que mande el navegador debe poder detener PHP.

**Beneficios.** CP-10 pasó de fallar a aprobar. CP-17 (12 entradas con arreglos) falla con el código
anterior y aprueba con el nuevo. CP-11 comprueba que una calificación vacía ya no se toma como válida.

---

<a id="r6"></a>
## R6. Estándar PSR-12 y tipos para PHPStan

**Problema.** Había 84 incumplimientos de PSR-12 (llaves de función en la misma línea, `if` sin
llaves, falta de línea en blanco tras `<?php` y líneas de más de 120 caracteres) y 88 avisos de
PHPStan: 23 funciones con `array` sin tipo y 65 variables que las vistas usan sin que nada indique de
dónde salen.

**Archivos intervenidos:** todos los de `acciones/`, `api/`, `includes/` y `vistas/`, además de
`index.php` y `login.php`.

**Antes**, en `vistas/progreso.php`, la vista usaba `$conteos`, `$total`, `$porcentaje` y `$stats`
sin declararlos:

```php
        <!-- ========================= PROGRESO Y ESTADÍSTICAS ========================= -->
        <section id="progreso" class="section">
```

Y en `acciones/registro.php`:

```php
function error_registro(string $codigo, string $nombre, string $email): never {
// ...
if (mb_strlen($password) < 8) error_registro('password', $nombre, $email);
```

**Después:**

```php
<?php

/**
 * Vista progreso: la incluye index.php, que define estas variables.
 *
 * @var array<string, int> $conteos
 * @var int $total
 * @var int $porcentaje
 * @var array{conteos: array<string, int>, total: int, porcentaje: int, ...} $stats
 */

?>
        <!-- ========================= PROGRESO Y ESTADÍSTICAS ========================= -->
```

```php
function error_registro(string $codigo, string $nombre, string $email): never
{
// ...
if (mb_strlen($password) < 8) {
    error_registro('password', $nombre, $email);
}
```

```php
/**
 * Para cada canción mostrada, sus playlists: [cancion_id => [playlist_id => nombre]].
 *
 * @param list<int|string> $idsCanciones
 * @return array<int, array<int, string>>
 */
function playlists_por_cancion(PDO $pdo, int $uid, array $idsCanciones): array
```

**Técnicas:** aplicación de estándares de programación (`phpcbf` con la configuración de
`phpcs.xml`), documentación de tipos con PHPDoc y división de líneas largas.

**Principios:** estándares de programación, documentación técnica, nombres y contratos explícitos.

**Justificación.** Las vistas dependían de variables "mágicas" definidas en otro archivo. Declararlas
documenta el contrato entre `index.php` y cada vista, y permite que PHPStan revise las vistas en vez de
ignorarlas.

**Beneficios.** PHPStan pasó de 88 a 0, PSR-12 de 84 a 5, y CP-16 comprueba que la página principal
se sigue mostrando y escapando el HTML. `phpcbf` solo cambió espacios y llaves: el comportamiento no
cambió y las 46 pruebas siguen pasando.

---

<a id="r7"></a>
## R7. Datos personales fuera del script SQL

**Problema.** `database/musictrack_db.sql`, el script de instalación que también usa Docker, se exportó
con una cuenta real: nombre, correo y hash de la contraseña. Quedaba publicado en GitHub y se creaba en
cada base nueva.

**Archivo intervenido:** `database/musictrack_db.sql`.

**Antes** (línea 133; el dato real se omite aquí):

```sql
LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (2,'<nombre real>','<correo real>','$2y$12$<hash>','2026-11-17 21:05:52');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
...
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
```

**Después:**

```sql
LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
...
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
```

**Técnicas:** eliminación de contenido innecesario (el script de instalación solo debe crear
estructura).

**Principios:** seguridad por diseño, mínimo privilegio, YAGNI.

**Justificación.** Un hash bcrypt no es la contraseña, pero se puede atacar sin conexión, y el correo y
el nombre son datos personales. Un script de instalación no debe traer cuentas.

**Beneficios.** Una base nueva queda vacía. Las pruebas crean su base con este mismo script y
confirman que las cuatro tablas siguen creándose bien. **Pendiente:** el dato sigue en el historial de
Git anterior a `6318463` (ver [MANTENIMIENTO.md](MANTENIMIENTO.md)).
