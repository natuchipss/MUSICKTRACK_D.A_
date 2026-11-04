<?php
$host = "localhost";
$user = "root";     // Usuario por defecto de XAMPP
$pass = "";         // En XAMPP la contraseña del root por defecto es vacía
$dbname = "musictrack_db";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error al conectar a la base de datos: " . $e->getMessage());
}
?>