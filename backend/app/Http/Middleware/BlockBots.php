<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Anti-bot senza servizi esterni (niente captcha, niente cookie di terze parti):
 *
 * - honeypot: il form contiene un campo `website` invisibile agli umani; i bot
 *   che compilano tutti i campi lo riempiono e vengono respinti
 * - tempo minimo: il form invia `form_time` (millisecondi passati da quando è
 *   stato mostrato); un invio più rapido di `$minSeconds` non è umano
 *
 * Uso nelle rotte: `bot.guard` (solo honeypot) o `bot.guard:3` (anche tempo minimo).
 * L'errore è restituito sotto la chiave `form` (errore generale del form).
 */
class BlockBots
{
    public function handle(Request $request, Closure $next, int $minSeconds = 0): Response
    {
        if ($request->filled('website')) {
            $this->reject($request, 'honeypot');
        }

        if ($minSeconds > 0 && $request->integer('form_time') < $minSeconds * 1000) {
            $this->reject($request, 'troppo veloce');
        }

        return $next($request);
    }

    private function reject(Request $request, string $reason): never
    {
        Log::warning('Richiesta bloccata dall\'anti-bot', [
            'path' => $request->path(),
            'ip' => $request->ip(),
            'reason' => $reason,
        ]);

        throw ValidationException::withMessages([
            'form' => ['Non siamo riusciti a inviare il modulo. Attendi qualche secondo e riprova.'],
        ]);
    }
}
