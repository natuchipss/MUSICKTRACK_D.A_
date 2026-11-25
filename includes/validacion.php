<?php

// Validación de los datos de una canción. La usan el formulario "Agregar" (acciones/registrar.php)
// y el modal "Editar" (acciones/actualizar.php), así las dos reglas no se pueden desincronizar.

require_once __DIR__ . '/funciones.php';

/**
 * Lee los campos comunes de una canción enviados por POST y los valida contra los catálogos.
 *
 * @param array<string, mixed> $post Normalmente $_POST
 * @return array{titulo: string, artista: string, tipo: string, genero: string,
 *               prioridad: string, estado: string, notas: ?string}|null
 *         null si falta el título o el artista, o si un valor no existe en su catálogo
 */
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
        && isset(TIPOS[$datos['tipo']], GENEROS[$datos['genero']], PRIORIDADES[$datos['prioridad']], ESTADOS[$datos['estado']]);

    return $completo ? $datos : null;
}

/**
 * Texto de un campo del formulario, sin espacios a los lados.
 * Si llega un arreglo (ej. titulo[]=x) se trata como vacío en vez de provocar un error.
 *
 * @param array<string, mixed> $post
 */
function campo_texto(array $post, string $campo): string
{
    $valor = $post[$campo] ?? '';
    return is_scalar($valor) ? trim((string) $valor) : '';
}

/** Calificación válida: 0 (sin calificar) a 5 estrellas. */
function calificacion_valida(int $calificacion): bool
{
    return $calificacion >= 0 && $calificacion <= CALIFICACION_MAXIMA;
}
