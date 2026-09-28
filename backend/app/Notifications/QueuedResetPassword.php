<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Email "reimposta password" inviata tramite la coda (vedi QueuedVerifyEmail).
 * Il link scade comunque 60 minuti dopo la richiesta.
 */
class QueuedResetPassword extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public $backoff = 60;
}
