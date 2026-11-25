<#
.SYNOPSIS
    Ejecuta el análisis estático de MusicTrack y guarda los reportes como evidencia.

.EXAMPLE
    .\tools\analizar.ps1 -Etapa despues -Herramientas C:\herramientas-php

.NOTES
    Herramientas (archivos .phar) usadas en la Guía 11:
      phploc 7.0.2, phpcpd 6.0.3, PHPStan 2.3.7, PHPMD 3.0.0, PHP_CodeSniffer 4.0.4.
    Los .phar no se guardan en el repositorio (ver docs/EVALUACION_SOFTWARE.md).
#>
param(
    [Parameter(Mandatory)] [ValidateSet('antes', 'despues')] [string] $Etapa,
    [Parameter(Mandatory)] [string] $Herramientas,
    # Carpeta del código a analizar (por defecto, la raíz del repositorio).
    # Debe contener phpcs.xml, phpstan.neon, phpmd.xml y tools/complejidad.xml
    [string] $Raiz = (Split-Path $PSScriptRoot -Parent),
    [string] $Php = 'C:\xampp\php\php.exe'
)

$ErrorActionPreference = 'Continue'
$repo   = Split-Path $PSScriptRoot -Parent
$salida = Join-Path $repo "docs\evidencias\$Etapa"
New-Item -ItemType Directory -Force $salida | Out-Null

$fuentes = @('acciones', 'api', 'includes', 'vistas', 'index.php', 'login.php')

Push-Location $Raiz
try {
    # 1. Tamaño (LOC, archivos, funciones)
    & $Php "$Herramientas\phploc-7.0.2.phar" --exclude includes/config.php @fuentes |
        Out-File -Encoding utf8 "$salida\phploc.txt"

    # 2. Duplicidad: bloques de 4 líneas y 30 tokens o más (más estricto que el valor
    #    por defecto, 5 líneas y 70 tokens, que en un proyecto pequeño no detecta nada)
    & $Php "$Herramientas\phpcpd-6.0.3.phar" --min-lines 4 --min-tokens 30 --exclude includes/config.php @fuentes |
        Out-File -Encoding utf8 "$salida\phpcpd.txt"

    # 3. Errores y tipos (PHPStan nivel 6, configuración en phpstan.neon)
    & $Php "$Herramientas\phpstan.phar" analyse -c "$Raiz\phpstan.neon" --no-progress --memory-limit 1G --error-format raw |
        Out-File -Encoding utf8 "$salida\phpstan.txt"

    # 4. Diseño (PHPMD, reglas en phpmd.xml)
    & $Php -d error_reporting=0 "$Herramientas\phpmd.phar" analyze --no-progress --ruleset "$Raiz\phpmd.xml" --format text --exclude '*includes/config.php' @fuentes |
        Out-File -Encoding utf8 "$salida\phpmd.txt"

    # 5. Complejidad ciclomática de cada función
    & $Php -d error_reporting=0 "$Herramientas\phpmd.phar" analyze --no-progress --ruleset "$Raiz\tools\complejidad.xml" --format text --exclude '*includes/config.php' @fuentes |
        Out-File -Encoding utf8 "$salida\complejidad.txt"

    # 6. Estándar PSR-12 (configuración en phpcs.xml)
    & $Php "$Herramientas\phpcs.phar" --standard="$Raiz\phpcs.xml" --report=summary --report-width=140 --basepath="$Raiz" @fuentes |
        Out-File -Encoding utf8 "$salida\phpcs.txt"
    & $Php "$Herramientas\phpcs.phar" --standard="$Raiz\phpcs.xml" --report=source --report-width=140 --basepath="$Raiz" @fuentes |
        Out-File -Encoding utf8 "$salida\phpcs-reglas.txt"
}
finally {
    Pop-Location
}

# Las rutas absolutas de la máquina no aportan nada al reporte
Get-ChildItem $salida -Filter *.txt | ForEach-Object {
    $texto = [string] (Get-Content $_.FullName -Raw -Encoding utf8)
    $texto = $texto.Replace("$Raiz\", '').Replace("$Raiz/", '').Replace($Raiz, '.')
    $texto = $texto -replace '\x1b\[[0-9;]*m', ''
    Set-Content $_.FullName $texto -Encoding utf8 -NoNewline
}

Write-Host "Reportes guardados en $salida"
