<x-mail.layout :subject="$subject">
    <p>Ciao {{ $order->customer_name }},</p>

    <p>lo stato del tuo ordine <strong>#{{ str_pad((string) $order->id, 5, '0', STR_PAD_LEFT) }}</strong> è cambiato:</p>

    <p style="margin:24px 0;">
        <span style="display:inline-block; background-color:#B76E79; color:#ffffff; padding:8px 16px; border-radius:4px; font-weight:bold;">
            {{ $statusLabel }}
        </span>
    </p>

    @if($order->status === 'evaso')
        @if($order->tracking_number)
            <p>Il tuo ordine è stato spedito!</p>
            <p style="margin: 0 0 16px;">
                @if($order->carrier)<strong>Corriere:</strong> {{ $order->carrier }}<br>@endif
                <strong>Numero di tracking:</strong> {{ $order->tracking_number }}
            </p>
            <p style="margin: 0 0 16px;">
                <a href="{{ $order->effective_tracking_url ?: rtrim(config('app.frontend_url'), '/').'/ordini/'.$order->id }}" style="display:inline-block; background-color:#B76E79; color:#ffffff; padding:10px 18px; border-radius:6px; text-decoration:none; font-weight:bold;">
                    Segui la spedizione
                </a>
            </p>
            @if($order->tracking_needs_manual_code)
                <p style="color:#8a8a8a; font-size:13px;">Nella pagina del corriere incolla il numero di tracking nel campo di ricerca.</p>
            @endif
        @else
            <p>Il tuo ordine è pronto! Ti contatteremo a breve per il ritiro/consegna.</p>
        @endif
    @elseif($order->status === 'in_lavorazione')
        <p>Abbiamo ricevuto il pagamento: stiamo preparando il tuo ordine.</p>
    @elseif($order->status === 'annullato')
        <p>Il tuo ordine è stato annullato. Se non te lo aspettavi, contattaci pure per chiarimenti.</p>
    @endif

    <p>Totale ordine: <strong>&euro;{{ number_format((float) $order->total, 2, ',', '.') }}</strong></p>

    <p>Grazie per aver scelto F&amp;L Beauty.</p>
</x-mail.layout>
