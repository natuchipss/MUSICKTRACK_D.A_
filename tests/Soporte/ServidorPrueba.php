<?php

namespace MusicTrack\Tests\Soporte;

use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Levanta MusicTrack con el servidor integrado de PHP para las pruebas funcionales.
 *
 * Se copia el proyecto a una carpeta temporal con un includes/config.php que apunta a la base
 * de pruebas, así nunca se usa el config.php real ni la base musictrack_db.
 */
final class ServidorPrueba
{
    private const PUERTO = 8765;

    /** @var resource|null */
    private static $proceso = null;
    private static string $carpeta = '';

    public static function url(string $ruta = ''): string
    {
        return 'http://127.0.0.1:' . self::PUERTO . '/' . ltrim($ruta, '/');
    }

    public static function iniciar(TestCase $prueba): void
    {
        BaseDatosPrueba::preparar($prueba); // omite la prueba si no hay base de pruebas
        if (self::$proceso !== null) {
            return;
        }

        $raiz = dirname(__DIR__, 2);
        self::$carpeta = sys_get_temp_dir() . '/musictrack_prueba_' . getmypid();
        foreach (['acciones', 'api', 'includes', 'vistas', 'assets'] as $dir) {
            self::copiar("$raiz/$dir", self::$carpeta . "/$dir");
        }
        foreach (['index.php', 'login.php', 'registro.php'] as $archivo) {
            if (is_file("$raiz/$archivo")) {
                copy("$raiz/$archivo", self::$carpeta . "/$archivo");
            }
        }

        $c = BaseDatosPrueba::config();
        file_put_contents(self::$carpeta . '/includes/config.php', '<?php return ' . var_export([
            'spotify_client_id' => 'prueba', 'spotify_client_secret' => 'prueba',
            'db_host' => "{$c['host']};port={$c['port']}", 'db_name' => $c['name'],
            'db_user' => $c['user'], 'db_pass' => $c['pass'],
        ], true) . ';');

        $nulo = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
        $proceso = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:' . self::PUERTO, '-t', self::$carpeta],
            [0 => ['file', $nulo, 'r'], 1 => ['file', $nulo, 'w'], 2 => ['file', $nulo, 'w']],
            $tuberias
        );
        if ($proceso === false) {
            throw new RuntimeException('No se pudo iniciar el servidor de pruebas');
        }
        self::$proceso = $proceso;
        register_shutdown_function([self::class, 'detener']);

        // Espera a que el servidor acepte conexiones
        for ($i = 0; $i < 50; $i++) {
            $socket = @fsockopen('127.0.0.1', self::PUERTO);
            if ($socket) {
                fclose($socket);
                return;
            }
            usleep(100_000);
        }
        throw new RuntimeException('El servidor de pruebas no respondió');
    }

    public static function detener(): void
    {
        if (self::$proceso !== null) {
            proc_terminate(self::$proceso);
            self::$proceso = null;
        }
    }

    private static function copiar(string $origen, string $destino): void
    {
        @mkdir($destino, 0777, true);
        foreach (scandir($origen) ?: [] as $nombre) {
            if ($nombre === '.' || $nombre === '..' || $nombre === 'config.php') {
                continue;
            }
            is_dir("$origen/$nombre")
                ? self::copiar("$origen/$nombre", "$destino/$nombre")
                : copy("$origen/$nombre", "$destino/$nombre");
        }
    }
}
