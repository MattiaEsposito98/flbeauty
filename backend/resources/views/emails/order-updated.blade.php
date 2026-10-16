<x-mail.layout :subject="$subject" :preheader="'Il tuo ordine #'.$number.' è cambiato: nuovo totale '.number_format((float) $order->total, 2, ',', '.').' €'">
    <x-mail.heading>{{ $byCustomer ? 'Hai modificato il tuo ordine' : 'Il tuo ordine è stato aggiornato' }} #{{ $number }}</x-mail.heading>

    <p style="margin:0 0 16px;">
        @if ($byCustomer)
            Abbiamo registrato le modifiche che hai fatto al tuo ordine. Questo è il riepilogo aggiornato.
        @else
            Abbiamo aggiornato il tuo ordine. Questo è il riepilogo con le modifiche.
        @endif
    </p>

    @include('emails.partials.order-changes', ['lines' => $lines])

    @include('emails.partials.order-summary', ['order' => $order])

    @if ($order->status === 'nuovo')
        <p style="margin:0 0 16px;">L'importo da pagare è ora <strong>&euro;{{ number_format((float) $order->total, 2, ',', '.') }}</strong>.
            Ti scriviamo su <strong>WhatsApp</strong> con le istruzioni per il pagamento: sul sito non devi pagare nulla.</p>
    @endif

    @if ($order->user_id)
        <x-mail.button :url="rtrim(config('app.frontend_url'), '/').'/ordini/'.$order->id">Vedi il tuo ordine</x-mail.button>
    @endif

    <p style="margin:18px 0 0;">Per qualsiasi domanda rispondi a questa email o scrivici su
        <a href="{{ config('brand.whatsapp_url') }}" style="color:#8e4f5b;">WhatsApp</a>.</p>

    <p style="margin:20px 0 0;">A presto,<br><strong>F&amp;L Beauty</strong></p>

    <x-slot:footer>
        Hai ricevuto questa email perché il tuo ordine su F&amp;L Beauty è stato modificato.
    </x-slot:footer>
</x-mail.layout>
