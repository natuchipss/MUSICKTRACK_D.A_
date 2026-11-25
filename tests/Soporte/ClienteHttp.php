<?php

namespace MusicTrack\Tests\Soporte;

use RuntimeException;

/** Navegador mínimo para las pruebas funcionales: guarda la cookie de sesión y no sigue redirecciones. */
final class ClienteHttp
{
    private string $cookies;

    public function __construct()
    {
        $this->cookies = (string) tempnam(sys_get_temp_dir(), 'mt_cookies_');
    }

    /** @return array{status: int, location: string, cuerpo: string} */
    public function get(string $ruta): array
    {
        return $this->enviar($ruta, []);
    }

    /**
     * @param array<string, string|int> $campos
     * @param list<string> $cabeceras
     * @return array{status: int, location: string, cuerpo: string}
     */
    public function post(string $ruta, array $campos, array $cabeceras = []): array
    {
        return $this->enviar($ruta, [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => http_build_query($campos),
            CURLOPT_HTTPHEADER => $cabeceras,
        ]);
    }

    /** Crea una cuenta nueva (queda con la sesión iniciada) y devuelve el token CSRF de la sesión. */
    public function registrarse(string $nombre): string
    {
        $token = $this->tokenDe($this->get('login.php')['cuerpo']);
        $r = $this->post('acciones/registro.php', [
            'csrf' => $token, 'nombre' => $nombre, 'email' => strtolower($nombre) . '@prueba.test',
            'password' => 'clave-segura-1', 'confirmar' => 'clave-segura-1',
        ]);
        if ($r['status'] !== 302 || !str_contains($r['location'], 'ok=bienvenido')) {
            throw new RuntimeException('No se pudo registrar el usuario de prueba: ' . $r['status'] . ' ' . $r['location']);
        }
        return $token;
    }

    public function tokenDe(string $html): string
    {
        if (!preg_match('/name="csrf" value="([a-f0-9]{64})"/', $html, $m)) {
            throw new RuntimeException('La página no trae token CSRF');
        }
        return $m[1];
    }

    /**
     * @param array<int, mixed> $opciones
     * @return array{status: int, location: string, cuerpo: string}
     */
    private function enviar(string $ruta, array $opciones): array
    {
        $ch = curl_init(ServidorPrueba::url($ruta));
        curl_setopt_array($ch, $opciones + [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_COOKIEJAR      => $this->cookies,
            CURLOPT_COOKIEFILE     => $this->cookies,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $respuesta = (string) curl_exec($ch);
        $tamCabecera = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $cabecera = substr($respuesta, 0, $tamCabecera);
        preg_match('/^Location:\s*(.+)$/mi', $cabecera, $m);

        return [
            'status'   => (int) curl_getinfo($ch, CURLINFO_HTTP_CODE),
            'location' => trim($m[1] ?? ''),
            'cuerpo'   => substr($respuesta, $tamCabecera),
        ];
    }
}
