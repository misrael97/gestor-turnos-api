<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['https://panel.gturnos.tech','https://gturnos.tech','https://sucursal.gturnos.tech' ],

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
