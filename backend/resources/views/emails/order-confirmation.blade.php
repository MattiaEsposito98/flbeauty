<x-mail.layout :subject="$subject">
    <p>Ciao {{ $order->customer_name }},</p>

    <p>abbiamo ricevuto il tuo ordine <strong>#{{ str_pad((string) $order->id, 5, '0', STR_PAD_LEFT) }}</strong>, grazie!</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 16px 0;">
        @foreach ($order->items as $item)
            <tr>
                <td style="padding: 4px 0;">{{ $item->product?->name ?? 'Prodotto' }} &times;{{ $item->quantity }}</td>
                <td style="padding: 4px 0; text-align: right;">&euro;{{ number_format($item->quantity * $item->unit_price, 2, ',', '.') }}</td>
            </tr>
        @endforeach
        @if ($order->shippingRate)
            <tr>
                <td style="padding: 4px 0;">Spedizione ({{ $order->shippingRate->name }})</td>
                <td style="padding: 4px 0; text-align: right;">&euro;{{ number_format($order->shipping_cost, 2, ',', '.') }}</td>
            </tr>
        @endif
        @if ($order->discount)
            <tr>
                <td style="padding: 4px 0;">Sconto ({{ $order->discount->code }})</td>
                <td style="padding: 4px 0; text-align: right;">applicato</td>
            </tr>
        @endif
    </table>

    <p style="font-size: 1.1em;"><strong>Totale: &euro;{{ number_format($order->total, 2, ',', '.') }}</strong></p>

    <p>Ti aggiorneremo via email quando lo stato dell'ordine cambierà.</p>
</x-mail.layout>
