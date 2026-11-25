<?php

namespace MusicTrack\Tests\Integracion;

use MusicTrack\Tests\Soporte\BaseDatosPrueba as BD;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/** CP-05 a CP-07: consultas de la biblioteca, estadísticas y restricciones de la base de datos (MySQL/MariaDB). */
final class BibliotecaTest extends TestCase
{
    private PDO $pdo;
    private int $ana;
    private int $luis;

    protected function setUp(): void
    {
        $this->pdo  = BD::preparar($this);
        $this->ana  = BD::crearUsuario($this->pdo, 'Ana');
        $this->luis = BD::crearUsuario($this->pdo, 'Luis');
    }

    #[TestDox('CP-05 Cada usuario solo ve su biblioteca y los filtros se aplican en el servidor')]
    public function testFiltrosYAislamiento(): void
    {
        BD::crearCancion($this->pdo, $this->ana, ['titulo' => 'Tusa', 'artista' => 'Karol G', 'genero' => 'reggaeton', 'estado' => 'escuchado']);
        BD::crearCancion($this->pdo, $this->ana, ['titulo' => 'Yellow', 'artista' => 'Coldplay', 'genero' => 'rock']);
        BD::crearCancion($this->pdo, $this->luis, ['titulo' => 'Otra', 'artista' => 'Karol G', 'genero' => 'reggaeton']);

        $todo = consultar_biblioteca($this->pdo, $this->ana, leer_filtros([]));
        $this->assertSame(2, $todo['total'], 'no aparece la canción de Luis');

        $escuchadas = consultar_biblioteca($this->pdo, $this->ana, leer_filtros(['estado' => 'escuchado']));
        $this->assertSame(['Tusa'], array_column($escuchadas['items'], 'titulo'));

        // "urbano" es parte del nombre visible "Reggaetón / Urbano"
        $porGenero = consultar_biblioteca($this->pdo, $this->ana, leer_filtros(['q' => 'urbano']));
        $this->assertSame(['Tusa'], array_column($porGenero['items'], 'titulo'));

        // Los comodines de LIKE se escapan: "%" no encuentra todo
        $comodin = consultar_biblioteca($this->pdo, $this->ana, leer_filtros(['q' => '%']));
        $this->assertSame(0, $comodin['total']);
    }

    #[TestDox('CP-05 La paginación muestra 9 por página y corrige páginas fuera de rango')]
    public function testPaginacion(): void
    {
        for ($i = 1; $i <= 11; $i++) {
            BD::crearCancion($this->pdo, $this->ana, ['titulo' => "Canción $i"]);
        }

        $pagina2 = consultar_biblioteca($this->pdo, $this->ana, leer_filtros(['pagina' => '2']));
        $this->assertSame(2, $pagina2['paginas']);
        $this->assertCount(2, $pagina2['items']);

        $fuera = consultar_biblioteca($this->pdo, $this->ana, leer_filtros(['pagina' => '99']));
        $this->assertSame(2, $fuera['pagina']);
    }

    #[TestDox('CP-06 Las estadísticas cuentan estados, porcentaje, artista principal y promedio')]
    public function testEstadisticas(): void
    {
        BD::crearCancion($this->pdo, $this->ana, ['artista' => 'Feid', 'estado' => 'escuchado', 'calificacion' => 5]);
        BD::crearCancion($this->pdo, $this->ana, ['artista' => 'Feid, ATL Jacob', 'estado' => 'escuchado', 'calificacion' => 4]);
        BD::crearCancion($this->pdo, $this->ana, ['artista' => 'Shakira', 'estado' => 'escuchando']);
        BD::crearCancion($this->pdo, $this->ana, ['artista' => 'Bad Bunny']);
        BD::crearCancion($this->pdo, $this->luis, ['artista' => 'Feid', 'estado' => 'escuchado']);

        $s = estadisticas($this->pdo, $this->ana);

        $this->assertSame(['pendiente' => 1, 'escuchando' => 1, 'escuchado' => 2], $s['conteos']);
        $this->assertSame(4, $s['total']);
        $this->assertSame(50, $s['porcentaje']);
        $this->assertSame(['artista' => 'Feid', 'total' => 2], $s['topArtistas'][0], 'colaboraciones cuentan para el artista principal');
        $this->assertSame(4.5, $s['promedio']);
        $this->assertSame(2, $s['calificadas']);
    }

    #[TestDox('CP-07 Un usuario no puede repetir un ID de Spotify y borrar una canción la quita de sus playlists')]
    public function testRestricciones(): void
    {
        $id = BD::crearCancion($this->pdo, $this->ana, ['spotify_id' => '4uLU6hMCjMI75M1A2tKUQC']);
        BD::crearCancion($this->pdo, $this->luis, ['spotify_id' => '4uLU6hMCjMI75M1A2tKUQC']); // otro usuario: permitido

        try {
            BD::crearCancion($this->pdo, $this->ana, ['spotify_id' => '4uLU6hMCjMI75M1A2tKUQC']);
            $this->fail('Se permitió un duplicado');
        } catch (PDOException $e) {
            $this->assertSame('23000', $e->getCode());
        }

        $this->pdo->prepare('INSERT INTO playlists (usuario_id, nombre) VALUES (?, ?)')->execute([$this->ana, 'Favoritas']);
        $playlist = (int) $this->pdo->lastInsertId();
        $this->pdo->prepare('INSERT INTO playlist_canciones (playlist_id, cancion_id) VALUES (?, ?)')->execute([$playlist, $id]);
        $this->assertSame([$playlist => 'Favoritas'], playlists_por_cancion($this->pdo, $this->ana, [$id])[$id] ?? []);
        $this->assertSame([], playlists_por_cancion($this->pdo, $this->luis, [$id]), 'Luis no ve las playlists de Ana');

        $this->pdo->prepare('DELETE FROM canciones WHERE id = ?')->execute([$id]);
        $this->assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM playlist_canciones')->fetchColumn());
    }
}
