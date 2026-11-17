<?php
// Editar una canción desde el modal "Editar"
require_once __DIR__ . '/../includes/auth.php';
accion_protegida();

$id           = (int) ($_POST['id'] ?? 0);
$titulo       = trim($_POST['titulo'] ?? '');
$artista      = trim($_POST['artista'] ?? '');
$tipo         = $_POST['tipo'] ?? '';
$genero       = $_POST['genero'] ?? '';
$prioridad    = $_POST['prioridad'] ?? '';
$estado       = $_POST['estado'] ?? '';
$notas        = trim($_POST['notas'] ?? '');
$calificacion = (int) ($_POST['calificacion'] ?? 0);

$valido = $id > 0 && $titulo !== '' && $artista !== '' && $calificacion >= 0 && $calificacion <= 5
    && isset(TIPOS[$tipo], GENEROS[$genero], PRIORIDADES[$prioridad], ESTADOS[$estado]);

if (!$valido) {
    volver('incompleto');
}

// WHERE usuario_id: nadie puede editar canciones de otro usuario
db()->prepare('UPDATE canciones
               SET titulo = :titulo, artista = :artista, tipo = :tipo, genero = :genero,
                   prioridad = :prioridad, estado = :estado, notas = :notas, calificacion = :calificacion
               WHERE id = :id AND usuario_id = :uid')
    ->execute([
        ':titulo'       => mb_substr($titulo, 0, 150),
        ':artista'      => mb_substr($artista, 0, 150),
        ':tipo'         => $tipo,
        ':genero'       => $genero,
        ':prioridad'    => $prioridad,
        ':estado'       => $estado,
        ':notas'        => $notas !== '' ? $notas : null,
        ':calificacion' => $calificacion > 0 ? $calificacion : null,
        ':id'           => $id,
        ':uid'          => usuario_id(),
    ]);

volver('actualizado', "cancion-$id");
