<x-mail.layout :subject="$subject">
    {{-- Il contenuto scritto nell'editor dell'admin: gli stili di base lo rendono coerente --}}
    <div style="font-size:15px; line-height:1.65; color:#3d2b2f;">
        <h1 style="margin:0 0 18px; font-family:Georgia, 'Times New Roman', serif; font-size:24px; line-height:1.3; font-weight:normal; color:#3d2b2f;">{{ $subject }}</h1>
        {!! $body !!}
    </div>

    <p style="margin:24px 0 0;">A presto,<br><strong>F&amp;L Beauty</strong></p>

    <x-slot:footer>
        @if ($unsubscribeUrl)
            Ricevi questa email perché hai scelto di ricevere offerte e novità da F&amp;L Beauty.
            <a href="{{ $unsubscribeUrl }}" style="color:#7b6468;">Non vuoi più riceverle? Disiscriviti</a>
            oppure disattivale dal tuo account.
        @else
            Comunicazione di servizio relativa al tuo account o ai tuoi ordini su F&amp;L Beauty.
        @endif
    </x-slot:footer>
</x-mail.layout>
