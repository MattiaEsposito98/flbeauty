<?php

use App\Support\Sitemap;
use Illuminate\Support\Facades\Route;

// Il dominio del backend non è un sito da indicizzare: la pagina iniziale è solo
// una risposta di servizio.
Route::get('/', function () {
    return response()->json([
        'app' => config('app.name'),
        'status' => 'ok',
    ])->header('X-Robots-Tag', 'noindex, nofollow');
});

// Stessa sitemap del comando `sitemap:generate`, utile per controllarla dal browser.
Route::get('/sitemap.xml', fn () => response(Sitemap::xml(), 200, [
    'Content-Type' => 'application/xml; charset=UTF-8',
    'X-Robots-Tag' => 'noindex',
]));
