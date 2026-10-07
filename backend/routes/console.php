<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tiene aggiornata la sitemap per Google (prodotti nuovi, disattivati, ecc.).
// Serve un cron ogni minuto su `php artisan schedule:run` (vedi docs/SEO.md).
Schedule::command('sitemap:generate')->hourly();
