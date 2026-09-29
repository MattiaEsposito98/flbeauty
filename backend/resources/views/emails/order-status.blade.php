<x-mail.layout :subject="$subject">
    <p>Ciao {{ $order->customer_name }},</p>

    <p>lo stato del tuo ordine <strong>#{{ str_pad((string) $order->id, 5, '0', STR_PAD_LEFT) }}</strong> è cambiato:</p>

    <p style="margin:24px 0;">
        <span style="display:inline-block; background-color:#B76E79; color:#ffffff; padding:8px 16px; border-radius:4px; font-weight:bold;">
            {{ $statusLabel }}
        </span>
    </p>

    @if($status === 'evaso')
        @if($trackingNumber)
            <p>Il tuo ordine è stato spedito!</p>
            <p style="margin: 0 0 16px;">
                @if($carrier)<strong>Corriere:</strong> {{ $carrier }}<br>@endif
                <strong>Numero di tracking:</strong> {{ $trackingNumber }}
            </p>
            <p style="margin: 0 0 16px;">
                <a href="{{ $trackingUrl ?: rtrim(config('app.frontend_url'), '/').'/ordini/'.$order->id }}" style="display:inline-block; background-color:#B76E79; color:#ffffff; padding:10px 18px; border-radius:6px; text-decoration:none; font-weight:bold;">
                    Segui la spedizione
                </a>
            </p>
        @else
            <p>Il tuo ordine è pronto! Ti contatteremo a breve per il ritiro/consegna.</p>
        @endif
    @elseif($status === 'in_lavorazione')
        <p>Abbiamo ricevuto il pagamento: stiamo preparando il tuo ordine.</p>
    @elseif($status === 'annullato')
        <p>Il tuo ordine è stato annullato. Se non te lo aspettavi, contattaci pure per chiarimenti.</p>
    @endif

    <p>Totale ordine: <strong>&euro;{{ number_format((float) $order->total, 2, ',', '.') }}</strong></p>

    <p>Grazie per aver scelto F&amp;L Beauty.</p>
</x-mail.layout>
