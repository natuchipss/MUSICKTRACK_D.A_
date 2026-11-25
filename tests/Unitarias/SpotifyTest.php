<?php

namespace MusicTrack\Tests\Unitarias;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/** CP-01 y CP-02: lectura de links de Spotify y conversión de su respuesta al modelo de MusicTrack. */
final class SpotifyTest extends TestCase
{
    /** @return array<string, array{string, ?array{tipo: string, id: string}}> */
    public static function links(): array
    {
        $id = '4uLU6hMCjMI75M1A2tKUQC';
        return [
            'link de canción con ?si='   => ["https://open.spotify.com/track/$id?si=abc", ['tipo' => 'track', 'id' => $id]],
            'link de álbum con idioma'   => ["https://open.spotify.com/intl-es/album/$id", ['tipo' => 'album', 'id' => $id]],
            'URI de Spotify'             => ["spotify:track:$id", ['tipo' => 'track', 'id' => $id]],
            'playlist (no se admite)'    => ["https://open.spotify.com/playlist/$id", null],
            'ID de longitud incorrecta'  => ['https://open.spotify.com/track/abc123', null],
            'texto que no es un link'    => ['Random Access Memories', null],
        ];
    }

    /** @param ?array{tipo: string, id: string} $esperado */
    #[DataProvider('links')]
    #[TestDox('CP-01 Lee el link: $_dataName')]
    public function testParsearLink(string $texto, ?array $esperado): void
    {
        $this->assertSame($esperado, spotify_parsear_link($texto));
    }

    #[TestDox('CP-02 Convierte una canción de Spotify: une los artistas y toma la portada del álbum')]
    public function testFormatearCancion(): void
    {
        $item = [
            'id' => 'abc', 'name' => 'Get Lucky',
            'artists' => [['name' => 'Daft Punk'], ['name' => 'Pharrell Williams']],
            'album' => ['images' => [['url' => 'https://i.scdn.co/640'], ['url' => 'https://i.scdn.co/300']]],
        ];

        $this->assertSame([
            'id' => 'abc', 'tipo' => 'track', 'name' => 'Get Lucky',
            'artist' => 'Daft Punk, Pharrell Williams', 'image' => 'https://i.scdn.co/300',
        ], spotify_formatear($item, 'track'));
    }

    #[TestDox('CP-02 Convierte un álbum de Spotify: la portada está en images y puede faltar')]
    public function testFormatearAlbum(): void
    {
        $conUnaImagen = ['id' => 'x', 'name' => 'RAM', 'artists' => [['name' => 'Daft Punk']], 'images' => [['url' => 'https://i.scdn.co/640']]];
        $sinImagen    = ['id' => 'y', 'name' => 'Demo', 'artists' => []];

        $this->assertSame('https://i.scdn.co/640', spotify_formatear($conUnaImagen, 'album')['image']);
        $this->assertNull(spotify_formatear($sinImagen, 'album')['image']);
        $this->assertSame('', spotify_formatear($sinImagen, 'album')['artist']);
    }
}
