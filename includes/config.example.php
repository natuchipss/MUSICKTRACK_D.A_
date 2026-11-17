<?php
// Plantilla de configuración. Cópiala como config.php y pon tus datos reales.
// config.php está en .gitignore para que el Client Secret nunca se publique.
return [
    // Spotify: developer.spotify.com/dashboard > tu app > Settings
    'spotify_client_id'     => 'TU_CLIENT_ID',
    'spotify_client_secret' => 'TU_CLIENT_SECRET',

    // Base de datos (valores por defecto de XAMPP)
    'db_host' => 'localhost',
    'db_name' => 'musictrack_db',
    'db_user' => 'root',
    'db_pass' => '',
];
