<?php
// Plantilla de configuración. Cópiala como config.php y pon tus datos reales.
// config.php está en .gitignore para que el Client Secret nunca se publique.
return [
    // Spotify: developer.spotify.com/dashboard > tu app > Settings
    'spotify_client_id'     => getenv('SPOTIFY_CLIENT_ID') ?: 'TU_CLIENT_ID',
    'spotify_client_secret' => getenv('SPOTIFY_CLIENT_SECRET') ?: 'TU_CLIENT_SECRET',

    // Base de datos (valores por defecto de XAMPP; Docker los define en el entorno)
    'db_host' => getenv('DB_HOST') ?: 'localhost',
    'db_name' => getenv('DB_NAME') ?: 'musictrack_db',
    'db_user' => getenv('DB_USER') ?: 'root',
    'db_pass' => getenv('DB_PASS') ?: '',
];
