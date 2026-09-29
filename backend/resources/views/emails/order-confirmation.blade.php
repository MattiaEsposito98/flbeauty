<x-mail.layout :subject="$subject">
    <p>Ciao {{ $order->customer_name }},</p>

    <p>abbiamo ricevuto il tuo ordine <strong>#{{ str_pad((string) $order->id, 5, '0', STR_PAD_LEFT) }}</strong>, grazie!</p>

    <p style="margin:16px 0;">
        <span style="display:inline-block; background-color:#B76E79; color:#ffffff; padding:6px 14px; border-radius:4px; font-weight:bold;">
            {{ $statusLabel }}
        </span>
    </p>

    @include('emails.partials.order-summary', ['order' => $order])

    @if($status === 'nuovo')
        <p>I prodotti sono riservati per te. Ti aggiorneremo via email quando lo stato dell'ordine cambierà.</p>
    @else
        <p>Ti aggiorneremo via email quando lo stato dell'ordine cambierà.</p>
    @endif
</x-mail.layout>
