<x-mail.layout :subject="$subject" :preheader="'Il tuo codice del '.$percent.'% è pronto: usalo entro il '.$expires.'.'">
    <x-mail.heading>Benvenuto in F&amp;L Beauty, {{ $name }}!</x-mail.heading>

    <p style="margin:0 0 16px;">Grazie per esserti registrato e per aver confermato la tua email. Per festeggiare ti regaliamo uno <strong>sconto del {{ $percent }}%</strong> sul tuo ordine.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:22px 0; background-color:#fdf8f7; border:1px dashed #d6a1a4; border-radius:12px;">
        <tr>
            <td align="center" style="padding:22px 20px;">
                <div style="font-size:13px; color:#6b5a5d; margin:0 0 8px;">Il tuo codice personale</div>
                <div style="font-family:'Courier New', Courier, monospace; font-size:26px; letter-spacing:3px; font-weight:bold; color:#8e4f5b;">{{ $code }}</div>
                <div style="font-size:13px; color:#6b5a5d; margin:10px 0 0;">Valido fino al <strong>{{ $expires }}</strong> ({{ $days }} giorni)</div>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 18px; background-color:#fdf8f7; border:1px solid #f5e1e0; border-radius:12px;">
        <tr>
            <td style="padding:18px 20px; font-size:14px; line-height:1.65;">
                <strong style="color:#8e4f5b;">Come usarlo</strong><br>
                <strong>1.</strong> Scegli i tuoi prodotti e aggiungili al carrello.<br>
                <strong>2.</strong> Al momento dell'ordine scrivi il codice nel campo <strong>«Hai un codice sconto?»</strong> e premi <strong>Applica</strong>: vedrai subito lo sconto nel riepilogo.<br>
                <strong>3.</strong> Conferma l'ordine: il codice vale su tutto il totale dei prodotti (la spedizione non è scontata).
            </td>
        </tr>
    </table>

    <p style="margin:0 0 6px; font-size:14px;">Il codice è <strong>personale</strong>: funziona solo con il tuo account e <strong>una sola volta</strong>.</p>

    <x-mail.button :url="rtrim(config('app.frontend_url'), '/')">Vai al negozio</x-mail.button>

    <p style="margin:18px 0 0;">Per qualsiasi domanda rispondi a questa email o scrivici su
        <a href="{{ config('brand.whatsapp_url') }}" style="color:#8e4f5b;">WhatsApp</a>.</p>

    <p style="margin:20px 0 0;">A presto,<br><strong>F&amp;L Beauty</strong></p>

    <x-slot:footer>
        Hai ricevuto questa email perché hai creato un account su F&amp;L Beauty e confermato il tuo indirizzo.
    </x-slot:footer>
</x-mail.layout>
