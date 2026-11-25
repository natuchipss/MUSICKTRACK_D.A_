<?php

namespace MusicTrack\Tests\Soporte;

use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Base de datos exclusiva para las pruebas.
 *
 * Variables de entorno (si MT_TEST_DB_NAME no está definida, las pruebas que la usan se omiten):
 *   MT_TEST_DB_HOST (127.0.0.1), MT_TEST_DB_PORT (3306), MT_TEST_DB_NAME, MT_TEST_DB_USER (root), MT_TEST_DB_PASS ('')
 *
 * ¡Cuidado! Las pruebas borran el contenido de esa base: nunca uses musictrack_db.
 */
final class BaseDatosPrueba
{
    private static ?PDO $pdo = null;

    /** @return array{host: string, port: string, name: string, user: string, pass: string} */
    public static function config(): array
    {
        return [
            'host' => getenv('MT_TEST_DB_HOST') ?: '127.0.0.1',
            'port' => getenv('MT_TEST_DB_PORT') ?: '3306',
            'name' => (string) getenv('MT_TEST_DB_NAME'),
            'user' => getenv('MT_TEST_DB_USER') ?: 'root',
            'pass' => (string) getenv('MT_TEST_DB_PASS'),
        ];
    }

    /** Devuelve la conexión, crea las tablas la primera vez y deja la base vacía. */
    public static function preparar(TestCase $prueba): PDO
    {
        $c = self::config();
        if ($c['name'] === '' || $c['name'] === 'musictrack_db') {
            $prueba->markTestSkipped('Define MT_TEST_DB_NAME con una base de pruebas (no musictrack_db).');
        }

        if (self::$pdo === null) {
            self::$pdo = new PDO(
                "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset=utf8mb4",
                $c['user'],
                $c['pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
            // El mismo script que usa la instalación (database/musictrack_db.sql)
            self::$pdo->exec((string) file_get_contents(__DIR__ . '/../../database/musictrack_db.sql'));
        }

        self::$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['playlist_canciones', 'playlists', 'canciones', 'usuarios'] as $tabla) {
            self::$pdo->exec("TRUNCATE TABLE $tabla");
        }
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        return self::$pdo;
    }

    public static function crearUsuario(PDO $pdo, string $nombre): int
    {
        $pdo->prepare('INSERT INTO usuarios (nombre, email, password_hash) VALUES (?, ?, ?)')
            ->execute([$nombre, strtolower($nombre) . '@prueba.test', password_hash('x', PASSWORD_DEFAULT)]);
        return (int) $pdo->lastInsertId();
    }

    /** @param array<string, mixed> $datos */
    public static function crearCancion(PDO $pdo, int $usuarioId, array $datos = []): int
    {
        $fila = $datos + [
            'titulo' => 'Canción', 'artista' => 'Artista', 'tipo' => 'cancion', 'genero' => 'pop',
            'prioridad' => 'media', 'estado' => 'pendiente', 'spotify_id' => null, 'calificacion' => null,
        ];
        $pdo->prepare('INSERT INTO canciones (usuario_id, spotify_id, titulo, artista, tipo, genero, prioridad, estado, calificacion)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$usuarioId, $fila['spotify_id'], $fila['titulo'], $fila['artista'], $fila['tipo'],
                $fila['genero'], $fila['prioridad'], $fila['estado'], $fila['calificacion']]);
        return (int) $pdo->lastInsertId();
    }
}
