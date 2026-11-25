<?php

// Editar una canción desde el modal "Editar"
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/validacion.php';
accion_protegida();

$id           = (int) ($_POST['id'] ?? 0);
$calificacion = (int) ($_POST['calificacion'] ?? 0);
$cancion      = validar_cancion($_POST);

if ($id <= 0 || $cancion === null || !calificacion_valida($calificacion)) {
    volver('incompleto');
}

// WHERE usuario_id: nadie puede editar canciones de otro usuario
db()->prepare('UPDATE canciones
               SET titulo = :titulo, artista = :artista, tipo = :tipo, genero = :genero,
                   prioridad = :prioridad, estado = :estado, notas = :notas, calificacion = :calificacion
               WHERE id = :id AND usuario_id = :uid')
    ->execute([
        ':titulo'       => $cancion['titulo'],
        ':artista'      => $cancion['artista'],
        ':tipo'         => $cancion['tipo'],
        ':genero'       => $cancion['genero'],
        ':prioridad'    => $cancion['prioridad'],
        ':estado'       => $cancion['estado'],
        ':notas'        => $cancion['notas'],
        ':calificacion' => $calificacion > 0 ? $calificacion : null,
        ':id'           => $id,
        ':uid'          => usuario_id(),
    ]);

volver('actualizado', "cancion-$id");
