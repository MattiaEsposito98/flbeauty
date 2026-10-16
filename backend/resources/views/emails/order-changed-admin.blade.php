<x-mail.layout :subject="$subject" :preheader="'Ordine #'.$number.($cancelled ? ' annullato' : ' modificato').' da '.$order->customer_name">
    <x-mail.heading>Ordine #{{ $number }} {{ $cancelled ? 'annullato dal cliente' : 'modificato dal cliente' }}</x-mail.heading>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px; background-color:#fdf8f7; border:1px solid #f5e1e0; border-radius:12px;">
        <tr>
            <td style="padding:16px 20px; font-size:14px; line-height:1.7;">
                <strong>Cliente:</strong> {{ $order->customer_name }}<br>
                <strong>Email:</strong> {{ $order->customer_email }}<br>
                @if ($order->customer_phone)
                    <strong>Telefono:</strong> {{ $order->customer_phone }}<br>
                @endif
                <strong>Stato:</strong> {{ \App\Mail\OrderStatusUpdated::STATUS_LABELS[$order->status] ?? $order->status }}
            </td>
        </tr>
    </table>

    @if ($cancelled)
        <p style="margin:0 0 16px;">Il cliente ha annullato l'ordine mentre era ancora in attesa di pagamento. I pezzi sono tornati disponibili in magazzino
            (e, se aveva usato il suo codice di benvenuto, il codice torna utilizzabile fino alla scadenza).</p>
    @else
        @include('emails.partials.order-changes', ['lines' => $lines])

        @include('emails.partials.order-summary', ['order' => $order])

        <p style="margin:0 0 6px;">Le scorte sono già aggiornate. Il cliente ha ricevuto la conferma delle modifiche: controlla il nuovo totale prima di mandargli le istruzioni di pagamento.</p>
    @endif

    <x-mail.button :url="$adminUrl">Apri l'ordine nel pannello</x-mail.button>

    <x-slot:footer>
        Notifica interna: ordine {{ $cancelled ? 'annullato' : 'modificato' }} dal cliente. Rispondendo a questa email scrivi direttamente al cliente.
    </x-slot:footer>
</x-mail.layout>
