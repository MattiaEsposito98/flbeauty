<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// In produzione basta UN cron di Aruba (PHP, file backend/cron.php, ogni 10 minuti).
// Le attività girano nello stesso processo (non lanciano programmi esterni): sugli
// hosting condivisi `proc_open`/`exec` possono essere limitati. Vedi docs/DEPLOY.md.

// Spedisce le comunicazioni di massa in coda (le email singole partono subito, vedi
// QUEUE_CONNECTION=deferred). Con la coda vuota fa una sola query e termina;
// `withoutOverlapping` evita che due esecuzioni lavorino insieme.
Schedule::call(fn () => Artisan::call('queue:work', [
    '--stop-when-empty' => true,
    '--max-time' => 50,
    '--tries' => 3,
]))->name('queue-work')->everyMinute()->withoutOverlapping(10);

// Tiene aggiornata la sitemap per Google (prodotti nuovi o disattivati).
Schedule::call(fn () => Artisan::call('sitemap:generate'))
    ->name('sitemap-generate')
    ->dailyAt('04:00');
