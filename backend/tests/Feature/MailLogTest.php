<?php

use App\Mail\BroadcastCommunication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->mailLog = tempnam(sys_get_temp_dir(), 'maillog');
    config(['logging.channels.mail' => ['driver' => 'single', 'path' => $this->mailLog, 'level' => 'debug']]);
});

afterEach(function () {
    @unlink($this->mailLog);
});

it('scrive nel registro mail ogni email inviata con destinatario, oggetto e tipo', function () {
    User::factory()->unverified()->create(['email' => 'cliente@example.test', 'name' => 'Giulia'])
        ->sendEmailVerificationNotification();

    $log = file_get_contents($this->mailLog);

    expect($log)->toContain('INVIATA')
        ->toContain('cliente@example.test')
        ->toContain('Verifica il tuo indirizzo email - F&L Beauty')
        ->toContain('QueuedVerifyEmail')
        ->not->toContain('ERRORE');
});

it('scrive l\'errore e il motivo quando l\'invio fallisce', function () {
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

    try {
        User::factory()->unverified()->create(['email' => 'cliente@example.test'])->sendEmailVerificationNotification();
    } catch (Throwable) {
        // con la coda "sync" dei test l'errore risale: in produzione (coda deferred) non tocca la risposta
    }

    $log = file_get_contents($this->mailLog);

    expect($log)->toContain('ERRORE')
        ->toContain('cliente@example.test')
        ->toContain('525 Invio disabilitato per la casella')
        ->toContain('QueuedVerifyEmail')
        ->not->toContain('INVIATA');
});

it('usa il mittente delle email automatiche e risponde a info@', function () {
    config(['brand.noreply' => 'no-reply@flbeauty.it']);

    User::factory()->unverified()->create(['email' => 'cliente@example.test'])->sendEmailVerificationNotification();

    $message = Mail::mailer()->getSymfonyTransport()->messages()->last()->getOriginalMessage();

    expect($message->getFrom()[0]->getAddress())->toBe('no-reply@flbeauty.it')
        ->and($message->getReplyTo()[0]->getAddress())->toBe('info@flbeauty.it');

    $broadcast = (new BroadcastCommunication('Oggetto', '<p>Ciao</p>'))->build();

    expect($broadcast->from[0]['address'])->toBe('no-reply@flbeauty.it')
        ->and($broadcast->replyTo[0]['address'])->toBe('info@flbeauty.it');
});

it('usa il mittente normale finché MAIL_NOREPLY_ADDRESS non è impostato', function () {
    expect(config('brand.noreply'))->toBe(config('mail.from.address') ?? 'info@flbeauty.it');
});
