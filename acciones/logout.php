<?php

require_once __DIR__ . '/../includes/auth.php';
accion_protegida(false);

cerrar_sesion();
header('Location: ../login.php?ok=salir');
exit;
