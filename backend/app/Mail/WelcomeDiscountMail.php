<?php

namespace App\Mail;

use App\Models\Discount;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Email di benvenuto col codice sconto personale, mandata dopo la verifica dell'email. */
class WelcomeDiscountMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public Discount $discount) {}

    public function build(): self
    {
        $percent = rtrim(rtrim(number_format((float) $this->discount->value, 2, '.', ''), '0'), '.');
        $subject = "Benvenuto in F&L Beauty: ecco il tuo sconto del {$percent}%";

        return $this->subject($subject)
            ->view('emails.welcome-discount')
            ->with([
                'subject' => $subject,
                'name' => trim(explode(' ', trim($this->user->name))[0]),
                'code' => $this->discount->code,
                'percent' => $percent,
                'expires' => $this->discount->ends_at->locale('it')->translatedFormat('j F Y'),
                'days' => (int) config('shop.welcome_discount_days'),
            ]);
    }
}
