<?php

/*
|--------------------------------------------------------------------------
| Ingresso del backend Laravel quando il sito sta tutto su flbeauty.it
|--------------------------------------------------------------------------
|
| Su Aruba il backend si carica FUORI dalla cartella pubblica (così il file .env
| e il codice non sono raggiungibili dal web), in una cartella "backend" accanto
| a "www.flbeauty.it". Questo file sta nella cartella pubblica e riceve dal
| .htaccess tutte le richieste dirette al backend (/api, /admin, /livewire).
| È la copia di backend/public/index.php con il percorso del backend cambiato.
|
|   <spazio web>/
|   ├── backend/                  ← tutto il contenuto di backend/ del repository
|   └── www.flbeauty.it/          ← cartella pubblica = contenuto di frontend/dist
|       └── _gateway/index.php    ← questo file
|
| FLBEAUTY_BACKEND (variabile d'ambiente) permette di indicare un altro percorso.
|
*/

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$backend = getenv('FLBEAUTY_BACKEND') ?: dirname(__DIR__, 2).'/backend';

// Modalità manutenzione (php artisan down)
if (file_exists($maintenance = $backend.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $backend.'/vendor/autoload.php';

$app = require_once $backend.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
