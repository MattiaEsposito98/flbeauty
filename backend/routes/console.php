<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// In produzione basta UN cron ogni minuto: `php artisan schedule:run`.
// Da qui partono sia l'invio delle email in coda sia la sitemap (vedi docs/DEPLOY.md).

// Spedisce le email in coda (conferma ordine, verifica account, comunicazioni).
// Con la coda vuota fa una sola query e termina; `withoutOverlapping` evita che due
// esecuzioni lavorino insieme se una è lenta.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping(10);

// Tiene aggiornata la sitemap per Google (prodotti nuovi o disattivati).
Schedule::command('sitemap:generate')->dailyAt('04:00');
