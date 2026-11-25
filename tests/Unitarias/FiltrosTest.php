<?php

namespace MusicTrack\Tests\Unitarias;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/** CP-04: validación de los filtros que llegan por la URL. */
final class FiltrosTest extends TestCase
{
    #[TestDox('CP-04 Los filtros inválidos se reemplazan por valores seguros')]
    public function testFiltrosInvalidos(): void
    {
        $f = leer_filtros([
            'q' => '  ' . str_repeat('a', 150) . '  ',
            'estado' => 'terminado',              // no existe
            'orden' => 'id; DROP TABLE canciones', // intento de inyección
            'playlist' => '-4',
            'pagina' => '0',
        ]);

        $this->assertSame(100, mb_strlen($f['q']));
        $this->assertSame('', $f['estado']);
        $this->assertSame('recientes', $f['orden']);
        $this->assertSame(0, $f['playlist']);
        $this->assertSame(1, $f['pagina']);
    }

    #[TestDox('CP-04 Los filtros válidos se conservan')]
    public function testFiltrosValidos(): void
    {
        $f = leer_filtros(['q' => ' Feid ', 'estado' => 'escuchado', 'orden' => 'prioridad', 'playlist' => '3', 'pagina' => '2']);

        $this->assertSame(['q' => 'Feid', 'estado' => 'escuchado', 'orden' => 'prioridad', 'playlist' => 3, 'pagina' => 2], $f);
    }

    #[TestDox('CP-04 Un filtro enviado como arreglo (?estado[]=x) no rompe la página (agregada con R4)')]
    public function testFiltrosComoArreglo(): void
    {
        $f = leer_filtros(['estado' => ['pendiente'], 'orden' => ['titulo'], 'q' => ['a'], 'pagina' => ['2']]);

        $this->assertSame(['q' => '', 'estado' => '', 'orden' => 'recientes', 'playlist' => 0, 'pagina' => 1], $f);
    }

    #[TestDox('CP-04 La paginación calcula la página, el total de páginas y el desplazamiento (agregada con R4)')]
    public function testPaginar(): void
    {
        $this->assertSame(['pagina' => 1, 'paginas' => 1, 'offset' => 0], paginar(0, 1));
        $this->assertSame(['pagina' => 2, 'paginas' => 2, 'offset' => 9], paginar(10, 2));
        $this->assertSame(['pagina' => 2, 'paginas' => 2, 'offset' => 9], paginar(10, 50), 'página inexistente → la última');
    }

    #[TestDox('CP-04 La URL de la biblioteca omite los valores por defecto')]
    public function testUrlBiblioteca(): void
    {
        $base = leer_filtros([]);

        $this->assertSame('index.php#biblioteca', url_biblioteca($base));
        $this->assertSame('index.php?estado=pendiente&pagina=2#biblioteca', url_biblioteca($base, ['estado' => 'pendiente', 'pagina' => 2]));
        $this->assertSame('index.php#biblioteca', url_biblioteca($base, ['otro' => 'x']), 'parámetros desconocidos no pasan');
    }
}
