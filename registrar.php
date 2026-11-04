<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo    = trim($_POST['titulo']);
    $artista   = trim($_POST['artista']);
    $tipo      = $_POST['tipo'];
    $genero    = $_POST['genero'];
    $prioridad = $_POST['prioridad'];
    $estado    = $_POST['estado'];
    $notas     = isset($_POST['notas']) ? trim($_POST['notas']) : null;

    if (!empty($titulo) && !empty($artista) && !empty($tipo) && !empty($genero) && !empty($prioridad) && !empty($estado)) {
        $sql = "INSERT INTO canciones (titulo, artista, tipo, genero, prioridad, estado, notas) 
                VALUES (:titulo, :artista, :tipo, :genero, :prioridad, :estado, :notas)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':titulo'    => $titulo,
            ':artista'   => $artista,
            ':tipo'      => $tipo,
            ':genero'    => $genero,
            ':prioridad' => $prioridad,
            ':estado'    => $estado,
            ':notas'     => $notas
        ]);

        // Redirige a la biblioteca al terminar de guardar
        header('Location: index.php#biblioteca');
        exit;
    } else {
        echo "Por favor, completa todos los campos requeridos.";
    }
}
?>