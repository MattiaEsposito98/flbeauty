@php $number = str_pad((string) $order->id, 5, '0', STR_PAD_LEFT); @endphp
<x-mail.layout :subject="$subject" :preheader="'Il tuo ordine #'.$number.' è: '.$statusLabel">
    <x-mail.heading>Novità sul tuo ordine, {{ $order->customer_name }}</x-mail.heading>

    <p style="margin:0 0 16px;">Ti scriviamo perché lo stato del tuo ordine <strong>#{{ $number }}</strong> è cambiato:</p>

    <p style="margin:0 0 22px;"><x-mail.status :status="$status" :label="$statusLabel" /></p>

    @if ($status === 'evaso')
        @if ($trackingNumber)
            <p style="margin:0 0 14px;">Il tuo ordine è stato spedito!</p>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 6px; background-color:#fdf8f7; border:1px solid #f5e1e0; border-radius:12px;">
                <tr>
                    <td style="padding:16px 20px; font-size:14px; line-height:1.7;">
                        @if ($carrier)<strong>Corriere:</strong> {{ $carrier }}<br>@endif
                        <strong>Numero di tracking:</strong> {{ $trackingNumber }}
                    </td>
                </tr>
            </table>
            <x-mail.button :url="$trackingUrl ?: rtrim(config('app.frontend_url'), '/').'/ordini/'.$order->id">Segui la spedizione</x-mail.button>
        @else
            <p style="margin:0 0 16px;">Il tuo ordine è pronto! Ti contatteremo a breve per la consegna.</p>
        @endif
    @elseif ($status === 'in_lavorazione')
        <p style="margin:0 0 16px;">Abbiamo ricevuto il pagamento, grazie! Stiamo preparando il tuo ordine.</p>
    @elseif ($status === 'annullato')
        <p style="margin:0 0 16px;">Il tuo ordine è stato annullato e i prodotti sono tornati disponibili. Se non te lo aspettavi, scrivici e lo sistemiamo insieme.</p>
    @endif

    <p style="margin:0 0 16px;">Totale ordine: <strong>&euro;{{ number_format((float) $order->total, 2, ',', '.') }}</strong></p>

    @if ($order->user_id && $status !== 'evaso')
        <x-mail.button :url="rtrim(config('app.frontend_url'), '/').'/ordini/'.$order->id">Vedi il tuo ordine</x-mail.button>
    @endif

    <p style="margin:18px 0 0;">Hai domande? Rispondi a questa email o scrivici su
        <a href="{{ config('brand.whatsapp_url') }}" style="color:#8e4f5b;">WhatsApp</a>.</p>

    <p style="margin:20px 0 0;">Grazie per aver scelto F&amp;L Beauty!<br><strong>F&amp;L Beauty</strong></p>

    <x-slot:footer>
        Hai ricevuto questa email perché hai un ordine su F&amp;L Beauty.
    </x-slot:footer>
</x-mail.layout>
