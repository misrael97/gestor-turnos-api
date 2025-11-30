<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['http://localhost:8100', 'http://127.0.0.1:8100', 'http://192.168.1.6:8100', 'http://192.168.1.15:8100','http://localhost:4200'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],
    // 'allowed_methods' => [
    //     'GET',
    //     'POST',
    //     'PUT',
    //     'PATCH',
    //     'DELETE',
    //     'OPTIONS', // ¡IMPORTANTE para el preflight!
    // ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
