<?php

use App\Mail\OrderStatusUpdated;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/**
 * Le email passano dalla coda: quando partono, l'ordine viene riletto dal
 * database. Ogni email deve comunque mostrare lo stato del cambio che l'ha
 * generata, anche se nel frattempo l'ordine è cambiato di nuovo.
 */
it('mostra lo stato del momento del cambio anche se parte più tardi', function () {
    Mail::fake();

    $order = Order::create([
        'customer_name' => 'Mario Rossi',
        'customer_email' => 'mario@example.test',
        'status' => 'nuovo',
        'total' => 20,
    ]);

    $order->update(['status' => 'in_lavorazione']);
    $order->update(['status' => 'evaso', 'carrier' => 'Poste Italiane', 'tracking_number' => 'ABC123']);

    $sent = Mail::queued(OrderStatusUpdated::class)->values();
    expect($sent)->toHaveCount(2);

    // Simula il passaggio in coda: l'email viene salvata e ricaricata dal database.
    $first = unserialize(serialize($sent[0]))->render();
    $second = unserialize(serialize($sent[1]))->render();

    expect($first)->toContain('In lavorazione')
        ->toContain('Abbiamo ricevuto il pagamento')
        ->not->toContain('Il tuo ordine è stato spedito');

    expect($second)->toContain('Evaso')
        ->toContain('Il tuo ordine è stato spedito')
        ->toContain('risultati-spedizioni/ABC123');
});

it('manda un\'email per ogni stato con il testo giusto', function (string $status, string $text) {
    Mail::fake();

    $order = Order::create([
        'customer_name' => 'Mario Rossi',
        'customer_email' => 'mario@example.test',
        'status' => $status === 'nuovo' ? 'in_lavorazione' : 'nuovo',
        'total' => 20,
    ]);

    $order->update(['status' => $status]);

    Mail::assertQueued(OrderStatusUpdated::class, 1);
    $html = unserialize(serialize(Mail::queued(OrderStatusUpdated::class)->first()))->render();

    expect($html)->toContain(OrderStatusUpdated::STATUS_LABELS[$status])->toContain($text);
})->with([
    ['nuovo', 'lo stato del tuo ordine'],
    ['in_lavorazione', 'Abbiamo ricevuto il pagamento'],
    ['evaso', 'Il tuo ordine è pronto'],
    ['annullato', 'Il tuo ordine è stato annullato'],
]);
