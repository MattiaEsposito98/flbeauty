<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;

class BroadcastCommunication extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array{api: string, page: string}|null  $unsubscribeUrls  solo per le email promozionali
     */
    public function __construct(
        public string $subjectLine,
        public string $bodyHtml,
        public ?array $unsubscribeUrls = null,
    ) {
        // Le comunicazioni di massa passano SEMPRE dalla coda del database (le spedisce il
        // cron a gruppi), anche quando le email singole partono subito (QUEUE_CONNECTION=deferred).
        $this->onConnection('database');
    }

    public function build(): self
    {
        if ($this->unsubscribeUrls) {
            // Pulsante "Annulla iscrizione" di Gmail/Outlook (disiscrizione in un clic).
            $this->withSymfonyMessage(function (Email $message) {
                $message->getHeaders()->addTextHeader('List-Unsubscribe', '<'.$this->unsubscribeUrls['api'].'>');
                $message->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            });
        }

        // Mittente "no-reply" (nessuno legge quella casella); chi risponde scrive a info@.
        return $this->from(config('brand.noreply'), config('brand.name'))
            ->replyTo(config('brand.email'), config('brand.name'))
            ->subject($this->subjectLine)
            ->view('emails.broadcast')
            ->with([
                'subject' => $this->subjectLine,
                'body' => $this->bodyHtml,
                'unsubscribeUrl' => $this->unsubscribeUrls['page'] ?? null,
            ]);
    }
}
