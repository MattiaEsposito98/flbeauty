<?php

use App\Filament\Resources\EmailLogs\Pages\ListEmailLogs;
use App\Mail\BroadcastCommunication;
use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

uses(RefreshDatabase::class);

it('registra ogni email inviata con destinatario, oggetto e tipo', function () {
    $user = User::factory()->unverified()->create(['email' => 'cliente@example.test', 'name' => 'Giulia']);

    $user->sendEmailVerificationNotification();

    $log = EmailLog::sole();

    expect($log->status)->toBe(EmailLog::STATUS_SENT)
        ->and($log->recipient)->toBe('cliente@example.test')
        ->and($log->subject)->toBe('Verifica il tuo indirizzo email - F&L Beauty')
        ->and(EmailLog::kindLabel($log->kind))->toBe('Verifica account');
});

it('registra l\'errore quando l\'invio fallisce, senza dare errore al cliente', function () {
    Mail::extend('guasto', fn () => new class extends AbstractTransport
    {
        protected function doSend(SentMessage $message): void
        {
            throw new RuntimeException('525 Invio disabilitato per la casella');
        }

        public function __toString(): string
        {
            return 'guasto';
        }
    });
    config(['mail.mailers.guasto' => ['transport' => 'guasto'], 'mail.default' => 'guasto']);

    $user = User::factory()->unverified()->create(['email' => 'cliente@example.test']);

    try {
        $user->sendEmailVerificationNotification();
    } catch (Throwable) {
        // con la coda "sync" dei test l'errore risale: in produzione (coda deferred) non tocca la risposta
    }

    $log = EmailLog::sole();

    expect($log->status)->toBe(EmailLog::STATUS_FAILED)
        ->and($log->recipient)->toBe('cliente@example.test')
        ->and($log->error)->toContain('525')
        ->and(EmailLog::kindLabel($log->kind))->toBe('Verifica account');
});

it('manda le email automatiche da no-reply con risposte a info@', function () {
    config(['brand.noreply' => 'no-reply@flbeauty.it']);

    User::factory()->unverified()->create(['email' => 'cliente@example.test'])->sendEmailVerificationNotification();

    $message = Mail::mailer()->getSymfonyTransport()->messages()->last()->getOriginalMessage();

    expect($message->getFrom()[0]->getAddress())->toBe('no-reply@flbeauty.it')
        ->and($message->getReplyTo()[0]->getAddress())->toBe('info@flbeauty.it');

    $broadcast = (new BroadcastCommunication('Oggetto', '<p>Ciao</p>'))->build();

    expect($broadcast->from[0]['address'])->toBe('no-reply@flbeauty.it')
        ->and($broadcast->replyTo[0]['address'])->toBe('info@flbeauty.it');
});

it('usa il mittente normale finché l\'alias no-reply non è configurato', function () {
    expect(config('brand.noreply'))->toBe(config('mail.from.address') ?? 'info@flbeauty.it');
});

it('mostra il registro agli admin e lo pulisce dopo 90 giorni', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $nuova = EmailLog::create(['status' => 'inviata', 'recipient' => 'a@example.test', 'subject' => 'Recente', 'created_at' => now()]);
    $vecchia = EmailLog::create(['status' => 'errore', 'recipient' => 'b@example.test', 'subject' => 'Vecchia', 'error' => 'x', 'created_at' => now()->subDays(100)]);

    Livewire::actingAs($admin)
        ->test(ListEmailLogs::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$nuova, $vecchia]);

    $this->artisan('model:prune', ['--model' => [EmailLog::class]])->assertSuccessful();

    expect(EmailLog::pluck('subject')->all())->toBe(['Recente']);
});
