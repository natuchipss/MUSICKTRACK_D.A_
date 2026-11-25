<?php

namespace MusicTrack\Tests\Externas;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * CP-13: integración real con la API pública de Deezer (necesita internet; se omite sin conexión).
 * No se prueba Spotify en vivo porque exige las credenciales reales de includes/config.php.
 */
final class DeezerTest extends TestCase
{
    protected function setUp(): void
    {
        if (getenv('MT_SIN_RED') || @fsockopen('api.deezer.com', 443, $codigo, $mensaje, 3) === false) {
            $this->markTestSkipped('Sin conexión con api.deezer.com');
        }
    }

    #[TestDox('CP-13 Detecta el género de una canción conocida consultando Deezer')]
    public function testDetectaGenero(): void
    {
        $resultado = detectar_genero('Tusa', 'Karol G, Nicki Minaj', 'track');

        $this->assertNotNull($resultado);
        $this->assertSame('reggaeton', $resultado['genero']);
        $this->assertNotSame('', $resultado['fuente']);
    }

    #[TestDox('CP-13 Si Deezer no tiene el artista, devuelve null sin lanzar errores')]
    public function testArtistaInexistente(): void
    {
        $this->assertNull(detectar_genero('zzqx canción inexistente', 'Artista Que No Existe 98765', 'track'));
    }
}
