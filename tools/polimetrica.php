<?php

/**
 * Datos para la vista polimétrica de docs/METRICAS_SOFTWARE.md.
 *
 * Por cada archivo PHP: líneas (LOC), funciones declaradas y complejidad ciclomática máxima,
 * tomada del reporte de PHPMD (docs/evidencias/<etapa>/complejidad.txt).
 *
 * Uso: php tools/polimetrica.php <carpeta del código> <complejidad.txt> [archivo ...]
 */

[$script, $raiz, $reporte] = $argv + [null, null, null];
if ($raiz === null || $reporte === null) {
    fwrite(STDERR, "Uso: php tools/polimetrica.php <carpeta> <complejidad.txt> [archivo ...]\n");
    exit(1);
}

// "includes\genero.php:51  CyclomaticComplexity  The function detectar_genero() has a Cyclomatic Complexity of 11."
$complejidad = [];
foreach (file($reporte) ?: [] as $linea) {
    if (preg_match('/^\W*(\S+\.php):\d+\s.*The function (\w+)\(\).* of (\d+)\./', $linea, $m)) {
        $archivo = str_replace('\\', '/', $m[1]);
        $complejidad[$archivo][$m[2]] = (int) $m[3];
    }
}

/** @return list<string> Nombres de las funciones declaradas en el archivo */
function funciones_declaradas(string $ruta): array
{
    $tokens  = token_get_all((string) file_get_contents($ruta));
    $nombres = [];
    foreach ($tokens as $i => $token) {
        if (is_array($token) && $token[0] === T_FUNCTION) {
            // el siguiente identificador es el nombre (las funciones anónimas no tienen)
            for ($j = $i + 1; isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE; $j++);
            if (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                $nombres[] = $tokens[$j][1];
            }
        }
    }
    return $nombres;
}

$archivos = array_slice($argv, 3) ?: array_keys($complejidad);
echo "| Archivo | LOC | Funciones | CC máxima | CC total |\n|---|---:|---:|---:|---:|\n";
foreach ($archivos as $archivo) {
    $ruta = "$raiz/$archivo";
    if (!is_file($ruta)) {
        echo "| $archivo | — | — | — | — |\n";
        continue;
    }
    $loc = count(file($ruta) ?: []);
    // PHPMD solo informa funciones con al menos una decisión: las demás tienen complejidad 1
    $cc = array_map(fn($funcion) => $complejidad[$archivo][$funcion] ?? 1, funciones_declaradas($ruta));
    printf("| %s | %d | %d | %d | %d |\n", $archivo, $loc, count($cc), $cc ? max($cc) : 0, array_sum($cc));
}
