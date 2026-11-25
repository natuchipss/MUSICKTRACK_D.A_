<?php

namespace MusicTrack\Tests\Unitarias;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/** CP-03: traducción de los géneros de Deezer a los géneros de MusicTrack (regla de negocio). */
final class GeneroTest extends TestCase
{
    /** @return array<string, array{list<string>, ?string}> */
    public static function generos(): array
    {
        return [
            'reggaetón explícito'      => [['Reggaeton'], 'reggaeton'],
            'latino + rap = urbano'    => [['Rap/Hip Hop', 'Latino'], 'reggaeton'],
            'latino + dance = urbano'  => [['Dance', 'Latin Music'], 'reggaeton'],
            'salsa es latina'          => [['Salsa'], 'latina'],
            'rap sin latino'           => [['Rap/Hip Hop'], 'hiphop'],
            'R&B'                      => [['R&B'], 'hiphop'],
            'metal es rock'            => [['Metal'], 'rock'],
            'indie es rock'            => [['Indie Pop'], 'rock'],
            'house es electrónica'     => [['House'], 'electronica'],
            'soul es jazz/blues'       => [['Soul & Funk'], 'jazz'],
            'pop'                      => [['Pop'], 'pop'],
            'tildes y mayúsculas'      => [['MÚSICA LATINA TROPICAL'], 'latina'],
            'sin coincidencia'         => [['Clásica'], null],
            'lista vacía'              => [[], null],
        ];
    }

    /** @param list<string> $deezer */
    #[DataProvider('generos')]
    #[TestDox('CP-03 Mapea el género: $_dataName')]
    public function testMapearGenero(array $deezer, ?string $esperado): void
    {
        $this->assertSame($esperado, mapear_genero($deezer));
    }

    #[TestDox('CP-03 Todo género que devuelve el mapeo existe en el catálogo GENEROS')]
    public function testGenerosValidos(): void
    {
        foreach (self::generos() as [$deezer, $esperado]) {
            if ($esperado !== null) {
                $this->assertArrayHasKey($esperado, GENEROS);
            }
        }
    }
}
