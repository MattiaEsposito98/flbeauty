<x-mail.layout :subject="$subject">
    <p>Ciao {{ $order->customer_name }},</p>

    <p>abbiamo ricevuto il tuo ordine <strong>#{{ str_pad((string) $order->id, 5, '0', STR_PAD_LEFT) }}</strong>, grazie!</p>

    <p style="margin:16px 0;">
        <span style="display:inline-block; background-color:#B76E79; color:#ffffff; padding:6px 14px; border-radius:4px; font-weight:bold;">
            In attesa di pagamento
        </span>
    </p>

    @include('emails.partials.order-summary', ['order' => $order])

    <p>I prodotti sono riservati per te. Ti aggiorneremo via email quando lo stato dell'ordine cambierà.</p>
</x-mail.layout>
