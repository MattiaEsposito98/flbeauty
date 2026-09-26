<x-mail.layout :subject="$subject">
    <p>Ciao {{ $order->customer_name }},</p>

    <p>lo stato del tuo ordine <strong>#{{ str_pad((string) $order->id, 5, '0', STR_PAD_LEFT) }}</strong> è cambiato:</p>

    <p style="margin:24px 0;">
        <span style="display:inline-block; background-color:#B76E79; color:#ffffff; padding:8px 16px; border-radius:4px; font-weight:bold;">
            {{ $statusLabel }}
        </span>
    </p>

    @if($order->status === 'evaso')
        <p>Il tuo ordine è pronto! Ti contatteremo a breve per il ritiro/consegna.</p>
    @elseif($order->status === 'in_lavorazione')
        <p>Stiamo preparando il tuo ordine.</p>
    @elseif($order->status === 'annullato')
        <p>Il tuo ordine è stato annullato. Se non te lo aspettavi, contattaci pure per chiarimenti.</p>
    @endif

    <p>Totale ordine: <strong>&euro;{{ number_format((float) $order->total, 2, ',', '.') }}</strong></p>

    <p>Grazie per aver scelto F&amp;L Beauty.</p>
</x-mail.layout>
