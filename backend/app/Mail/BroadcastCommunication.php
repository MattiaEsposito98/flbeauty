<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BroadcastCommunication extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $bodyHtml,
    ) {}

    public function build(): self
    {
        return $this->subject($this->subjectLine)
            ->view('emails.broadcast')
            ->with([
                'subject' => $this->subjectLine,
                'body' => $this->bodyHtml,
            ]);
    }
}
