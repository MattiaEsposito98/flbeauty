@php $number = str_pad((string) $order->id, 5, '0', STR_PAD_LEFT); @endphp
<x-mail.layout :subject="$subject" :preheader="'Abbiamo ricevuto il tuo ordine #'.$number.'. Grazie!'">
    <x-mail.heading>Grazie per il tuo ordine, {{ $order->customer_name }}!</x-mail.heading>

    <p style="margin:0 0 16px;">Abbiamo ricevuto l'ordine <strong>#{{ $number }}</strong>.</p>

    <p style="margin:0 0 22px;"><x-mail.status :status="$status" :label="$statusLabel" /></p>

    @include('emails.partials.order-summary', ['order' => $order])

    @if ($status === 'nuovo')
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:8px 0 18px; background-color:#fdf8f7; border:1px solid #f5e1e0; border-radius:12px;">
            <tr>
                <td style="padding:18px 20px; font-size:14px; line-height:1.65;">
                    <strong style="color:#8e4f5b;">Cosa succede adesso</strong><br>
                    <strong>1.</strong> I prodotti sono <strong>riservati per te</strong>.<br>
                    <strong>2.</strong> Ti scriviamo su <strong>WhatsApp</strong> con le istruzioni per il pagamento: sul sito non devi pagare nulla.<br>
                    <strong>3.</strong> L'ordine è <strong>confermato quando riceviamo il pagamento</strong>: poi lo prepariamo e lo spediamo.
                </td>
            </tr>
        </table>
    @else
        <p style="margin:0 0 16px;">Ti aggiorneremo via email quando lo stato dell'ordine cambierà.</p>
    @endif

    @if ($order->user_id)
        <x-mail.button :url="rtrim(config('app.frontend_url'), '/').'/ordini/'.$order->id">Vedi il tuo ordine</x-mail.button>
    @endif

    <p style="margin:18px 0 0;">Per qualsiasi domanda rispondi a questa email o scrivici su
        <a href="{{ config('brand.whatsapp_url') }}" style="color:#8e4f5b;">WhatsApp</a>.</p>

    <p style="margin:20px 0 0;">A presto,<br><strong>F&amp;L Beauty</strong></p>

    <x-slot:footer>
        Hai ricevuto questa email perché hai effettuato un ordine su F&amp;L Beauty.
    </x-slot:footer>
</x-mail.layout>
