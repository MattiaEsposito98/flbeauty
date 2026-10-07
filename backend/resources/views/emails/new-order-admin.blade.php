@php $number = str_pad((string) $order->id, 5, '0', STR_PAD_LEFT); @endphp
<x-mail.layout :subject="$subject" :preheader="'Nuovo ordine #'.$number.' da '.$order->customer_name">
    <x-mail.heading>Nuovo ordine #{{ $number }}</x-mail.heading>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px; background-color:#fdf8f7; border:1px solid #f5e1e0; border-radius:12px;">
        <tr>
            <td style="padding:16px 20px; font-size:14px; line-height:1.7;">
                <strong>Cliente:</strong> {{ $order->customer_name }}<br>
                <strong>Email:</strong> {{ $order->customer_email }}<br>
                @if ($order->customer_phone)
                    <strong>Telefono:</strong> {{ $order->customer_phone }}<br>
                @endif
                <strong>Data:</strong> {{ $order->created_at?->timezone('Europe/Rome')->format('d/m/Y H:i') }}
            </td>
        </tr>
    </table>

    @include('emails.partials.order-summary', ['order' => $order])

    <p style="margin:0 0 6px;">I prodotti sono già stati scalati dal magazzino. Quando arriva il pagamento metti l'ordine
        <strong>In lavorazione</strong>; se non va a buon fine annullalo e le quantità torneranno disponibili.</p>

    <x-mail.button :url="$adminUrl">Apri l'ordine nel pannello</x-mail.button>

    <x-slot:footer>
        Notifica interna: nuovo ordine ricevuto dal sito. Rispondendo a questa email scrivi direttamente al cliente.
    </x-slot:footer>
</x-mail.layout>
