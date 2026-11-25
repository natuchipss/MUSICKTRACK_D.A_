<?php

namespace MusicTrack\Tests\Funcionales;

use MusicTrack\Tests\Soporte\BaseDatosPrueba as BD;
use MusicTrack\Tests\Soporte\ClienteHttp;
use MusicTrack\Tests\Soporte\ServidorPrueba;
use PDO;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * CP-08 a CP-12: los formularios de /acciones y /api probados de punta a punta,
 * con el servidor integrado de PHP y la base de datos de pruebas.
 */
final class AccionesTest extends TestCase
{
    private PDO $pdo;
    private ClienteHttp $ana;
    private string $csrf;

    protected function setUp(): void
    {
        ServidorPrueba::iniciar($this);
        $this->pdo  = BD::preparar($this);
        $this->ana  = new ClienteHttp();
        $this->csrf = $this->ana->registrarse('Ana');
    }

    /** @return array<string, string> */
    private function cancionValida(): array
    {
        return [
            'csrf' => $this->csrf, 'titulo' => 'Random Access Memories', 'artista' => 'Daft Punk',
            'tipo' => 'album', 'genero' => 'electronica', 'prioridad' => 'alta', 'estado' => 'pendiente',
            'notas' => '', 'spotify_id' => '4m2880jivSbbyEGAKfITCa', 'spotify_tipo' => 'album',
            'portada_url' => 'https://i.scdn.co/image/ab67616d0000b273',
        ];
    }

    /** @return array<string, mixed> */
    private function fila(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM canciones WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: [];
    }

    #[TestDox('CP-08 Registrar: guarda una canción válida y rechaza una con datos inválidos')]
    public function testRegistrar(): void
    {
        $ok = $this->ana->post('acciones/registrar.php', $this->cancionValida());
        $this->assertSame(302, $ok['status']);
        $this->assertStringContainsString('ok=registrado', $ok['location']);

        $guardada = $this->pdo->query('SELECT * FROM canciones')->fetchAll();
        $this->assertCount(1, $guardada);
        $this->assertSame('Daft Punk', $guardada[0]['artista']);
        $this->assertSame('4m2880jivSbbyEGAKfITCa', $guardada[0]['spotify_id']);

        $malo = $this->ana->post('acciones/registrar.php', ['prioridad' => 'urgente'] + $this->cancionValida());
        $this->assertStringContainsString('ok=incompleto', $malo['location']);

        $sinTitulo = $this->ana->post('acciones/registrar.php', ['titulo' => '   '] + $this->cancionValida());
        $this->assertStringContainsString('ok=incompleto', $sinTitulo['location']);

        $this->assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM canciones')->fetchColumn());
    }

    #[TestDox('CP-09 Editar: actualiza la canción propia y no permite tocar la de otro usuario')]
    public function testActualizar(): void
    {
        $this->ana->post('acciones/registrar.php', $this->cancionValida());
        $propia = (int) $this->pdo->query('SELECT id FROM canciones')->fetchColumn();
        $ajena  = BD::crearCancion($this->pdo, BD::crearUsuario($this->pdo, 'Luis'), ['titulo' => 'De Luis']);

        $edicion = ['estado' => 'escuchado', 'calificacion' => '5', 'notas' => 'Obra maestra'] + $this->cancionValida();

        $this->ana->post('acciones/actualizar.php', ['id' => (string) $propia] + $edicion);
        $this->assertSame('escuchado', $this->fila($propia)['estado']);
        $this->assertSame(5, (int) $this->fila($propia)['calificacion']);
        $this->assertSame('Obra maestra', $this->fila($propia)['notas']);

        $this->ana->post('acciones/actualizar.php', ['id' => (string) $ajena] + $edicion);
        $this->assertSame('De Luis', $this->fila($ajena)['titulo'], 'la canción de otro usuario no cambia');

        $fueraDeRango = $this->ana->post('acciones/actualizar.php', ['id' => (string) $propia, 'calificacion' => '9'] + $edicion);
        $this->assertStringContainsString('ok=incompleto', $fueraDeRango['location']);
    }

    #[TestDox('CP-10 Cambiar estado: un estado válido se guarda y uno inválido se rechaza avisando al usuario')]
    public function testCambiarEstado(): void
    {
        $id = BD::crearCancion($this->pdo, (int) $this->pdo->query('SELECT id FROM usuarios')->fetchColumn());

        $ok = $this->ana->post('acciones/estado.php', ['csrf' => $this->csrf, 'id' => (string) $id, 'estado' => 'escuchando']);
        $this->assertStringContainsString('ok=actualizado', $ok['location']);
        $this->assertSame('escuchando', $this->fila($id)['estado']);

        $invalido = $this->ana->post('acciones/estado.php', ['csrf' => $this->csrf, 'id' => (string) $id, 'estado' => 'terminado']);
        $this->assertSame('escuchando', $this->fila($id)['estado'], 'no se guarda un estado inexistente');
        $this->assertStringNotContainsString('ok=actualizado', $invalido['location'], 'no debe decir "Cambios guardados" si no guardó nada');
        $this->assertStringContainsString('ok=incompleto', $invalido['location']);
    }

    #[TestDox('CP-11 Calificar: acepta de 1 a 5, 0 la quita y fuera de rango no cambia nada')]
    public function testCalificar(): void
    {
        $id = BD::crearCancion($this->pdo, (int) $this->pdo->query('SELECT id FROM usuarios')->fetchColumn());

        $this->ana->post('acciones/calificar.php', ['csrf' => $this->csrf, 'id' => (string) $id, 'calificacion' => '4']);
        $this->assertSame(4, (int) $this->fila($id)['calificacion']);

        $this->ana->post('acciones/calificar.php', ['csrf' => $this->csrf, 'id' => (string) $id, 'calificacion' => '7']);
        $this->assertSame(4, (int) $this->fila($id)['calificacion']);

        $this->ana->post('acciones/calificar.php', ['csrf' => $this->csrf, 'id' => (string) $id, 'calificacion' => '0']);
        $this->assertNull($this->fila($id)['calificacion']);
    }

    #[TestDox('CP-12 Seguridad: sin token CSRF responde 403 y la API sin sesión responde 401')]
    public function testSeguridad(): void
    {
        $sinToken = $this->ana->post('acciones/registrar.php', ['csrf' => 'falso'] + $this->cancionValida());
        $this->assertSame(403, $sinToken['status']);
        $this->assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM canciones')->fetchColumn());

        $anonimo = (new ClienteHttp())->get('api/search.php?q=daft');
        $this->assertSame(401, $anonimo['status']);
        $this->assertSame(['error' => 'Inicia sesión'], json_decode($anonimo['cuerpo'], true));

        $guardadoSinToken = $this->ana->post('api/save.php', [], ['Content-Type: application/json']);
        $this->assertSame(403, $guardadoSinToken['status']);
    }
}
