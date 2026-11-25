<?php

namespace MusicTrack\Tests\Unitarias;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/** CP-14: validación compartida de los datos de una canción (includes/validacion.php, refactorización R1). */
final class ValidacionTest extends TestCase
{
    /** @return array<string, string> */
    private function valida(): array
    {
        return ['titulo' => ' Abbey Road ', 'artista' => 'The Beatles', 'tipo' => 'album',
            'genero' => 'rock', 'prioridad' => 'alta', 'estado' => 'pendiente', 'notas' => '  '];
    }

    #[TestDox('CP-14 Limpia los textos y convierte las notas vacías en null')]
    public function testDatosValidos(): void
    {
        $this->assertSame([
            'titulo' => 'Abbey Road', 'artista' => 'The Beatles', 'tipo' => 'album', 'genero' => 'rock',
            'prioridad' => 'alta', 'estado' => 'pendiente', 'notas' => null,
        ], validar_cancion($this->valida()));
    }

    #[TestDox('CP-14 Rechaza campos vacíos o valores fuera de los catálogos')]
    public function testDatosInvalidos(): void
    {
        foreach (['titulo' => '   ', 'artista' => '', 'tipo' => 'ep', 'genero' => 'kpop', 'prioridad' => 'urgente', 'estado' => 'terminado'] as $campo => $valor) {
            $this->assertNull(validar_cancion([$campo => $valor] + $this->valida()), "campo $campo = '$valor'");
        }
        $this->assertNull(validar_cancion([]));
        $this->assertNull(validar_cancion(['tipo' => ['album']] + $this->valida()), 'un arreglo no es un tipo válido');
    }

    #[TestDox('CP-14 Recorta el título al largo de la columna y valida la calificación de 0 a 5')]
    public function testLimites(): void
    {
        $largo = validar_cancion(['titulo' => str_repeat('ñ', 200)] + $this->valida());
        $this->assertSame(LARGO_TITULO, mb_strlen((string) $largo['titulo']));

        $this->assertTrue(calificacion_valida(0));
        $this->assertTrue(calificacion_valida(5));
        $this->assertFalse(calificacion_valida(6));
        $this->assertFalse(calificacion_valida(-1));
    }
}
