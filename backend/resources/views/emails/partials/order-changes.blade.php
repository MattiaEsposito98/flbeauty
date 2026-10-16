{{-- Elenco di cosa è cambiato in un ordine ($lines: righe di testo). --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px; background-color:#fdf8f7; border:1px solid #f5e1e0; border-radius:12px;">
    <tr>
        <td style="padding:16px 20px; font-size:14px; line-height:1.7;">
            <strong style="color:#8e4f5b;">Cosa è cambiato</strong><br>
            @foreach ($lines as $line)
                &bull; {{ $line }}<br>
            @endforeach
        </td>
    </tr>
</table>
