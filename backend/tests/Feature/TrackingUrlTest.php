<?php

use App\Models\Order;

function orderWith(array $attributes): Order
{
    return (new Order)->forceFill($attributes);
}

it('costruisce il link diretto alla spedizione per Poste e SDA', function (string $carrier) {
    $order = orderWith(['carrier' => $carrier, 'tracking_number' => ' 3UW14AS664236 ']);

    expect($order->effective_tracking_url)
        ->toBe('https://business.poste.it/grandi-imprese/cerca-spedizioni/index.html#/risultati-spedizioni/3UW14AS664236');
})->with(['Poste Italiane', 'SDA']);

it('usa il link inserito a mano quando c\'è', function () {
    $order = orderWith([
        'carrier' => 'Poste Italiane',
        'tracking_number' => 'ABC',
        'tracking_url' => 'https://esempio.it/traccia/ABC',
    ]);

    expect($order->effective_tracking_url)->toBe('https://esempio.it/traccia/ABC');
});

it('non crea link senza numero o per corrieri sconosciuti', function () {
    expect(orderWith(['carrier' => 'Poste Italiane'])->effective_tracking_url)->toBeNull()
        ->and(orderWith(['carrier' => 'BRT', 'tracking_number' => 'X1'])->effective_tracking_url)->toBeNull();
});
