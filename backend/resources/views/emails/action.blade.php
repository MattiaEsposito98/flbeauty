{{-- Email "azione" (verifica account, reset password): saluto, testo, pulsante, avvertenze. --}}
<x-mail.layout :subject="$subject" :preheader="$preheader ?? null">
    <x-mail.heading>{{ $greeting }}</x-mail.heading>

    @foreach ($introLines as $line)
        <p style="margin:0 0 14px;">{{ $line }}</p>
    @endforeach

    <x-mail.button :url="$actionUrl">{{ $actionText }}</x-mail.button>

    @foreach ($outroLines as $line)
        <p style="margin:0 0 12px; color:#7b6468; font-size:14px;">{{ $line }}</p>
    @endforeach

    <p style="margin:22px 0 0; padding-top:18px; border-top:1px solid #f5e1e0; font-size:12px; line-height:1.6; color:#7b6468;">
        Se il pulsante non funziona, copia e incolla questo indirizzo nel browser:<br>
        <a href="{{ $actionUrl }}" style="color:#8e4f5b; word-break:break-all;">{{ $actionUrl }}</a>
    </p>

    <p style="margin:20px 0 0;">A presto,<br><strong>F&amp;L Beauty</strong></p>

    <x-slot:footer>
        Hai ricevuto questa email perché è stata usata questa casella su F&amp;L Beauty.
    </x-slot:footer>
</x-mail.layout>
