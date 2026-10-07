<?php

/*
|--------------------------------------------------------------------------
| Ingresso del cron di Aruba
|--------------------------------------------------------------------------
|
| Il modulo "Processi Cron" di Aruba (modalità PHP) esegue un file PHP senza poter
| aggiungere argomenti, quindi non può lanciare `php artisan schedule:run`. Questo file
| fa la stessa cosa: avvia Laravel ed esegue le attività pianificate (invio delle
| comunicazioni di massa in coda, sitemap di notte; vedi routes/console.php).
| Sta in backend/, chiuso al web: si lancia solo dal cron o da riga di comando.
|
*/

if (PHP_SAPI !== 'cli' && ! getenv('FLBEAUTY_ALLOW_WEB_CRON')) {
    http_response_code(403);
    exit('Non disponibile dal web.');
}

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';

exit($app->make(Illuminate\Contracts\Console\Kernel::class)->call('schedule:run'));
