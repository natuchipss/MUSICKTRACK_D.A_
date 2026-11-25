<?php

// Arranque de las pruebas: carga solo la lógica (sin sesión ni conexión a la base de datos).
// La conexión de prueba se configura con variables de entorno (ver tests/Soporte/BaseDatosPrueba.php).

require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/consultas.php';
require_once __DIR__ . '/../includes/spotify.php';
require_once __DIR__ . '/../includes/genero.php';

require_once __DIR__ . '/Soporte/BaseDatosPrueba.php';
require_once __DIR__ . '/Soporte/ServidorPrueba.php';
require_once __DIR__ . '/Soporte/ClienteHttp.php';
