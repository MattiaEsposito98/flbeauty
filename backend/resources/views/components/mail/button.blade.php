@props(['url'])
{{-- Pulsante compatibile con Outlook (tabella): colore pieno del brand, testo bianco. --}}
<table role="presentation" cellpadding="0" cellspacing="0" align="center" style="margin:26px auto;">
    <tr>
        <td align="center" bgcolor="#a35c68" style="background-color:#a35c68; border-radius:999px;">
            <a href="{{ $url }}" style="display:inline-block; padding:13px 30px; font-family:Helvetica, Arial, sans-serif; font-size:15px; font-weight:bold; color:#ffffff; text-decoration:none; border-radius:999px;">{{ $slot }}</a>
        </td>
    </tr>
</table>
