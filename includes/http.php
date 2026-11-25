<?php

// Cliente HTTP único para las APIs externas (Spotify y Deezer).
// Antes cada función armaba su propia petición cURL; ahora todas pasan por aquí.

/**
 * Hace una petición HTTP y devuelve el código de estado y el cuerpo de la respuesta.
 *
 * @param list<string> $cabeceras  Ej. ['Authorization: Bearer ...']
 * @param string|null  $cuerpoPost Si no es null, la petición es POST con este cuerpo
 * @return array{status: int, cuerpo: string}
 * @throws RuntimeException Si no hubo respuesta (sin conexión, DNS, certificado, tiempo agotado)
 */
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
        // Típico en XAMPP/Windows: error 60 de certificado (ver README)
        throw new RuntimeException('cURL: ' . curl_error($ch));
    }
    // curl_close() no hace falta desde PHP 8 (y es obsoleta en 8.5)

    return ['status' => (int) curl_getinfo($ch, CURLINFO_HTTP_CODE), 'cuerpo' => $respuesta];
}

/**
 * GET que devuelve el JSON decodificado, o null si falla por cualquier motivo.
 * Para las consultas opcionales (oEmbed, Deezer), donde un error no debe detener al usuario.
 *
 * @return array<mixed>|null
 */
function http_json_o_null(string $url, int $segundos = 10): ?array
{
    try {
        $r = http_solicitud($url, [], null, $segundos);
    } catch (RuntimeException $e) {
        error_log($e->getMessage());
        return null;
    }
    $datos = json_decode($r['cuerpo'], true);
    return $r['status'] === 200 && is_array($datos) ? $datos : null;
}
