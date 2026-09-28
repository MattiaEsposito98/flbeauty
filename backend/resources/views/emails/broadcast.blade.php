<x-mail.layout :subject="$subject">
    {!! $body !!}

    <x-slot:footer>
        @if ($unsubscribeUrl)
            Ricevi questa email perché hai scelto di ricevere offerte e novità da F&amp;L Beauty.
            <a href="{{ $unsubscribeUrl }}" style="color:#8a8a8a;">Non vuoi più riceverle? Disiscriviti</a>
            oppure disattivale dal tuo account.
        @else
            Comunicazione di servizio relativa al tuo account o ai tuoi ordini su F&amp;L Beauty.
        @endif
    </x-slot:footer>
</x-mail.layout>
