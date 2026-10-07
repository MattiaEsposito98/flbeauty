<?php

namespace App\Providers;

use App\Models\Order;
use App\Observers\OrderObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Order::observe(OrderObserver::class);

        // Limiti per IP sulle rotte di accesso. Il blocco per singolo account
        // dopo troppe password sbagliate è invece in AuthController::login.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('register', fn (Request $request) => Limit::perHour(10)->by($request->ip()));

        // Le email "verifica account" e "reimposta password" usano lo stesso layout delle
        // altre email del sito (emails/action.blade.php), non quello inglese di Laravel.
        VerifyEmail::toMailUsing(function ($notifiable, string $url) {
            $subject = 'Verifica il tuo indirizzo email - F&L Beauty';

            return (new MailMessage)
                ->subject($subject)
                ->view(['emails.action', 'emails.action-text'], [
                    'subject' => $subject,
                    'preheader' => 'Conferma la tua email per accedere al tuo account.',
                    'greeting' => 'Ti diamo il benvenuto'.($notifiable->name ? ', '.$notifiable->name : '').'!',
                    'introLines' => [
                        'Grazie per la tua registrazione su F&L Beauty.',
                        'Conferma il tuo indirizzo email per poter accedere al tuo account.',
                    ],
                    'actionText' => 'Verifica la tua email',
                    'actionUrl' => $url,
                    'outroLines' => [
                        'Se non hai fatto tu questa registrazione, puoi ignorare questa email.',
                    ],
                ]);
        });

        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            return config('app.frontend_url').'/reimposta-password?token='.$token
                .'&email='.urlencode($notifiable->getEmailForPasswordReset());
        });

        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = config('app.frontend_url').'/reimposta-password?token='.$token
                .'&email='.urlencode($notifiable->getEmailForPasswordReset());

            $subject = 'Reimposta la tua password - F&L Beauty';

            return (new MailMessage)
                ->subject($subject)
                ->view(['emails.action', 'emails.action-text'], [
                    'subject' => $subject,
                    'preheader' => 'Scegli una nuova password per il tuo account.',
                    'greeting' => 'Ciao'.($notifiable->name ? ' '.$notifiable->name : '').'!',
                    'introLines' => [
                        'Hai chiesto di reimpostare la password del tuo account F&L Beauty.',
                        'Clicca il pulsante qui sotto per sceglierne una nuova.',
                    ],
                    'actionText' => 'Reimposta la password',
                    'actionUrl' => $url,
                    'outroLines' => [
                        'Il link scade tra 60 minuti.',
                        'Se non hai fatto tu questa richiesta, puoi ignorare questa email: la tua password non cambierà.',
                    ],
                ]);
        });
    }
}
