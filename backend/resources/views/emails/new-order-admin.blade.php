<x-mail.layout :subject="$subject">
    <p>È arrivato un nuovo ordine dal sito: <strong>#{{ str_pad((string) $order->id, 5, '0', STR_PAD_LEFT) }}</strong>.</p>

    <p style="margin: 0 0 16px;">
        <strong>Cliente:</strong> {{ $order->customer_name }}<br>
        <strong>Email:</strong> {{ $order->customer_email }}<br>
        @if ($order->customer_phone)
            <strong>Telefono:</strong> {{ $order->customer_phone }}<br>
        @endif
        <strong>Data:</strong> {{ $order->created_at?->timezone('Europe/Rome')->format('d/m/Y H:i') }}
    </p>

    @include('emails.partials.order-summary', ['order' => $order])

    <p>I prodotti sono già stati scalati dal magazzino. Quando arriva il pagamento metti l'ordine
        "In lavorazione"; se non va a buon fine annullalo e le quantità torneranno disponibili.</p>

    <p style="margin: 24px 0;">
        <a href="{{ $adminUrl }}" style="display:inline-block; background-color:#B76E79; color:#ffffff; padding:10px 18px; border-radius:6px; text-decoration:none; font-weight:bold;">
            Apri l'ordine nel pannello
        </a>
    </p>
</x-mail.layout>
