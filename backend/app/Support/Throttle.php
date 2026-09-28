<?php

namespace App\Support;

class Throttle
{
    /**
     * Messaggio mostrato quando si superano i tentativi consentiti.
     */
    public static function message(int $seconds): string
    {
        $minutes = max(1, (int) ceil($seconds / 60));

        return 'Troppi tentativi. Riprova tra '.$minutes.' '.($minutes === 1 ? 'minuto' : 'minuti').'.';
    }
}
