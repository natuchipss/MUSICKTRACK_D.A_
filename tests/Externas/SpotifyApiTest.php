<?php

namespace MusicTrack\Tests\Externas;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * CP-15: integración real con Spotify Web API a través de includes/http.php (refactorización R2).
 * Usa las credenciales de includes/config.php; se omite si no existen o no hay internet.
 */
final class SpotifyApiTest extends TestCase
{
    protected function setUp(): void
    {
        $config = __DIR__ . '/../../includes/config.php';
        if (!is_file($config) || str_starts_with((string) (require $config)['spotify_client_id'], 'TU_')) {
            $this->markTestSkipped('No hay credenciales de Spotify en includes/config.php');
        }
        if (getenv('MT_SIN_RED') || @fsockopen('api.spotify.com', 443, $codigo, $mensaje, 3) === false) {
            $this->markTestSkipped('Sin conexión con api.spotify.com');
        }
    }

    #[TestDox('CP-15 Busca un álbum en Spotify y lo devuelve en el formato de MusicTrack')]
    public function testBuscarAlbum(): void
    {
        $resultados = spotify_search('Random Access Memories Daft Punk', 'album');

        $this->assertNotEmpty($resultados);
        $this->assertTrue(array_is_list($resultados), 'debe llegar como lista JSON, no como objeto');
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{22}$/', $resultados[0]['id']);
        $this->assertSame('album', $resultados[0]['tipo']);
        $this->assertStringContainsString('Daft Punk', $resultados[0]['artist']);
    }

    #[TestDox('CP-15 Un ID inexistente produce una excepción controlada con el código HTTP')]
    public function testIdInexistente(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/HTTP (400|404)/');
        spotify_item('album', '0000000000000000000000');
    }
}
