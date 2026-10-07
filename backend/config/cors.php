<?php

/*
|--------------------------------------------------------------------------
| CORS — chi può chiamare le API dal browser
|--------------------------------------------------------------------------
|
| Il negozio (flbeauty.it) e le API (admin.flbeauty.it) stanno su domini diversi,
| quindi il browser chiede al backend il permesso. In sviluppo si accetta tutto;
| in produzione imposta CORS_ALLOWED_ORIGINS=https://flbeauty.it nel .env, così
| nessun altro sito può usare le API dal browser dei tuoi clienti.
| Più origini: separate da virgola.
|
*/

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', '*'))))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
