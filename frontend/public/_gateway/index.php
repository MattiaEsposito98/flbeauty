<?php

/*
|--------------------------------------------------------------------------
| Ingresso del backend Laravel quando il sito sta tutto su flbeauty.it
|--------------------------------------------------------------------------
|
| Su Aruba l'utente SSH/FTP può scrivere solo dentro la cartella pubblica del
| dominio (`home`, cioè www.flbeauty.it). Il backend sta quindi in una sottocartella
| `backend/` di quella cartella, chiusa al web dalle regole del .htaccess (nessuno può
| aprire flbeauty.it/backend/.env). Questo file riceve dal .htaccess tutte le richieste
| dirette al backend (/api, /admin, /livewire) ed è la copia di backend/public/index.php
| con il percorso del backend cambiato.
|
|   home/                          ← cartella pubblica del dominio
|   ├── index.html, assets/…       ← negozio React (contenuto di frontend/dist)
|   ├── .htaccess                  ← regole: https, backend chiuso, /api e /admin al backend
|   ├── _gateway/index.php         ← questo file
|   ├── backend/ → repo/backend    ← il backend (collegamento alla cartella del repository)
|   ├── repo/                      ← git clone del progetto (chiusa al web)
|   └── css, js, fonts, images, storage   ← collegamenti a backend/ (vedi DEPLOY.md)
|
| Se il backend è invece in una cartella accanto a quella pubblica, la trova lo stesso.
| FLBEAUTY_BACKEND (variabile d'ambiente) permette di indicare un percorso qualsiasi.
|
*/

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$backend = getenv('FLBEAUTY_BACKEND') ?: null;

if (! $backend) {
    foreach ([dirname(__DIR__).'/backend', dirname(__DIR__, 2).'/backend'] as $candidate) {
        if (is_file($candidate.'/vendor/autoload.php')) {
            $backend = $candidate;
            break;
        }
    }
}

if (! $backend) {
    http_response_code(503);
    exit('Backend non trovato.');
}

// Modalità manutenzione (php artisan down)
if (file_exists($maintenance = $backend.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $backend.'/vendor/autoload.php';

$app = require_once $backend.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
