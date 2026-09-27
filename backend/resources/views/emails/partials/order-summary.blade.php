@php
    $itemsTotal = $order->items->sum(fn ($item) => $item->quantity * $item->unit_price);
    $discountAmount = max(0, $itemsTotal + (float) $order->shipping_cost - (float) $order->total);
    $euro = fn ($value) => '&euro;'.number_format((float) $value, 2, ',', '.');
@endphp

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 16px 0; border-collapse: collapse;">
    @foreach ($order->items as $item)
        <tr>
            <td style="padding: 6px 0; border-bottom: 1px solid #f0e2e0;">
                {{ $item->product?->name ?? 'Prodotto' }} &times; {{ $item->quantity }}
                <span style="color: #8a8a8a;">({!! $euro($item->unit_price) !!} cad.)</span>
            </td>
            <td style="padding: 6px 0; border-bottom: 1px solid #f0e2e0; text-align: right;">{!! $euro($item->quantity * $item->unit_price) !!}</td>
        </tr>
    @endforeach
    <tr>
        <td style="padding: 6px 0;">Subtotale</td>
        <td style="padding: 6px 0; text-align: right;">{!! $euro($itemsTotal) !!}</td>
    </tr>
    @if ($order->discount)
        <tr>
            <td style="padding: 6px 0;">Sconto ({{ $order->discount->code }})</td>
            <td style="padding: 6px 0; text-align: right;">-{!! $euro($discountAmount) !!}</td>
        </tr>
    @endif
    <tr>
        <td style="padding: 6px 0;">Spedizione{{ $order->shippingRate ? ' ('.$order->shippingRate->name.')' : '' }}</td>
        <td style="padding: 6px 0; text-align: right;">{!! $euro($order->shipping_cost) !!}</td>
    </tr>
    <tr>
        <td style="padding: 10px 0; font-size: 1.1em;"><strong>Totale</strong></td>
        <td style="padding: 10px 0; font-size: 1.1em; text-align: right;"><strong>{!! $euro($order->total) !!}</strong></td>
    </tr>
</table>

@if ($order->shipping_address_line)
    <p style="margin: 0 0 16px;">
        <strong>Spedizione a:</strong><br>
        {{ $order->customer_name }}<br>
        {{ $order->shipping_address_line }}<br>
        {{ $order->shipping_postal_code }} {{ $order->shipping_city }} ({{ $order->shipping_province }})
        @if ($order->customer_phone)
            <br>Tel. {{ $order->customer_phone }}
        @endif
    </p>
@endif
