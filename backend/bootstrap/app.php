<?php

use App\Http\Middleware\BlockBots;
use App\Support\Throttle;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'bot.guard' => BlockBots::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Limiti di richieste superati sulle API: messaggio in italiano sotto la
        // chiave `form`, così il frontend lo mostra come errore generale del form.
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $message = Throttle::message((int) ($e->getHeaders()['Retry-After'] ?? 60));

            return response()->json([
                'message' => $message,
                'errors' => ['form' => [$message]],
            ], 429, $e->getHeaders());
        });
    })->create();
