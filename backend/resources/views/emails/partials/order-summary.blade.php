@php
    $itemsTotal = $order->items->sum(fn ($item) => $item->quantity * $item->unit_price);
    $discountAmount = max(0, $itemsTotal + (float) $order->shipping_cost - (float) $order->total);
    $euro = fn ($value) => '&euro;'.number_format((float) $value, 2, ',', '.');
@endphp

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px; border-collapse:collapse; font-size:14px;">
    <tr>
        <td style="padding:0 0 8px; font-size:12px; letter-spacing:2px; text-transform:uppercase; color:#8d6c65; border-bottom:2px solid #f5e1e0;" colspan="2">Riepilogo ordine</td>
    </tr>
    @foreach ($order->items as $item)
        <tr>
            <td style="padding:10px 12px 10px 0; border-bottom:1px solid #f5e1e0;">
                {{ $item->displayName() }}
                <span style="color:#7b6468;">&times; {{ $item->quantity }}</span><br>
                <span style="font-size:12px; color:#7b6468;">{!! $euro($item->unit_price) !!} cad.</span>
            </td>
            <td align="right" style="padding:10px 0; border-bottom:1px solid #f5e1e0; white-space:nowrap;">{!! $euro($item->quantity * $item->unit_price) !!}</td>
        </tr>
    @endforeach
    <tr>
        <td style="padding:10px 0 4px; color:#7b6468;">Subtotale</td>
        <td align="right" style="padding:10px 0 4px;">{!! $euro($itemsTotal) !!}</td>
    </tr>
    @if ($order->discount)
        <tr>
            <td style="padding:4px 0; color:#7b6468;">Sconto ({{ $order->discount->code }})</td>
            <td align="right" style="padding:4px 0;">-{!! $euro($discountAmount) !!}</td>
        </tr>
    @endif
    <tr>
        <td style="padding:4px 0; color:#7b6468;">Spedizione{{ $order->shippingRate ? ' ('.$order->shippingRate->name.')' : '' }}</td>
        <td align="right" style="padding:4px 0;">{!! $euro($order->shipping_cost) !!}</td>
    </tr>
    <tr>
        <td style="padding:12px 0 0; border-top:2px solid #f5e1e0; font-family:Georgia, 'Times New Roman', serif; font-size:18px;">Totale</td>
        <td align="right" style="padding:12px 0 0; border-top:2px solid #f5e1e0; font-family:Georgia, 'Times New Roman', serif; font-size:18px; color:#8e4f5b;"><strong>{!! $euro($order->total) !!}</strong></td>
    </tr>
</table>

@if ($order->shipping_address_line)
    <p style="margin:0 0 18px; font-size:14px; line-height:1.65;">
        <strong style="color:#8e4f5b;">Spedizione a</strong><br>
        {{ $order->customer_name }}<br>
        {{ $order->shipping_address_line }}<br>
        {{ $order->shipping_postal_code }} {{ $order->shipping_city }} ({{ $order->shipping_province }})
        @if ($order->customer_phone)
            <br>Tel. {{ $order->customer_phone }}
        @endif
    </p>
@endif
