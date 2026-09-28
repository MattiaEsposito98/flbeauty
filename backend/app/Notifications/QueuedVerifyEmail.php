<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Email di verifica inviata tramite la coda, come tutte le altre email: se il
 * server di posta non risponde la registrazione va a buon fine lo stesso e
 * l'invio viene ritentato dal worker. Testo personalizzato in AppServiceProvider.
 */
class QueuedVerifyEmail extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public $backoff = 60;
}
