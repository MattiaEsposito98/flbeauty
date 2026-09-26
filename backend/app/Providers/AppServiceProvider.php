<?php

namespace App\Providers;

use App\Models\Order;
use App\Observers\OrderObserver;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
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

        VerifyEmail::toMailUsing(function ($notifiable, string $url) {
            return (new MailMessage)
                ->subject('Verifica il tuo indirizzo email - F&L Beauty')
                ->greeting('Ciao'.($notifiable->name ? ' '.$notifiable->name : '').'!')
                ->line('Grazie per esserti registrato su F&L Beauty. Conferma il tuo indirizzo email per poter accedere al tuo account.')
                ->action('Verifica la tua email', $url)
                ->line('Se non hai richiesto tu questa registrazione, puoi ignorare questa email.');
        });
    }
}
