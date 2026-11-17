<?php
// Conexión PDO única (se crea la primera vez que se llama a db())
function db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $c = require __DIR__ . '/config.php';

        try {
            $pdo = new PDO(
                "mysql:host={$c['db_host']};dbname={$c['db_name']};charset=utf8mb4",
                $c['db_user'],
                $c['db_pass'],
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            // El detalle va al log del servidor, no a la pantalla
            error_log('Error de conexión a la base de datos: ' . $e->getMessage());
            http_response_code(500);
            die('No se pudo conectar a la base de datos.');
        }
    }

    return $pdo;
}
