@props(['subject' => null, 'preheader' => null])
@php
    $brand = config('brand');
    $site = rtrim(config('app.frontend_url'), '/');
    $logo = rtrim(config('app.url'), '/').'/images/email-logo.png';
@endphp
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>{{ $subject ?? $brand['name'] }}</title>
</head>
<body style="margin:0; padding:0; background-color:#faeeec; color:#3d2b2f; font-family:Helvetica, Arial, sans-serif;">
    @if ($preheader)
        <div style="display:none; max-height:0; overflow:hidden; opacity:0; font-size:1px; line-height:1px;">{{ $preheader }}</div>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#faeeec;">
        <tr>
            <td align="center" style="padding:28px 12px 36px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%; max-width:600px;">
                    {{-- Intestazione: marchio --}}
                    <tr>
                        <td align="center" style="padding:4px 0 22px;">
                            <a href="{{ $site }}" style="text-decoration:none;">
                                <img src="{{ $logo }}" width="64" height="64" alt="" style="display:block; margin:0 auto 10px; border:0; width:64px; height:64px;">
                                <span style="display:block; font-family:Georgia, 'Times New Roman', serif; font-size:28px; line-height:1.1; color:#8e4f5b; letter-spacing:0.5px;">F&amp;L</span>
                                <span style="display:block; margin-top:4px; font-size:11px; letter-spacing:5px; color:#8d6c65; text-transform:uppercase;">Beauty</span>
                            </a>
                        </td>
                    </tr>

                    {{-- Contenuto --}}
                    <tr>
                        <td style="background-color:#ffffff; border:1px solid #f5e1e0; border-radius:16px; padding:34px 32px; font-size:15px; line-height:1.65; color:#3d2b2f;">
                            {{ $slot }}
                        </td>
                    </tr>

                    {{-- Piè di pagina --}}
                    <tr>
                        <td align="center" style="padding:24px 16px 0; font-size:12px; line-height:1.7; color:#7b6468;">
                            <p style="margin:0 0 10px;">
                                @isset($footer)
                                    {{ $footer }}
                                @else
                                    Hai ricevuto questa email perché hai un account o un ordine su F&amp;L Beauty.
                                @endisset
                            </p>
                            <p style="margin:0 0 4px;">
                                <a href="{{ $brand['whatsapp_url'] }}" style="color:#8e4f5b; text-decoration:none;">WhatsApp {{ $brand['whatsapp_display'] }}</a>
                                &nbsp;·&nbsp;
                                <a href="mailto:{{ $brand['email'] }}" style="color:#8e4f5b; text-decoration:none;">{{ $brand['email'] }}</a>
                            </p>
                            <p style="margin:0 0 4px;">
                                @foreach ($brand['tiktok'] as $tiktok)
                                    <a href="{{ $tiktok['url'] }}" style="color:#8e4f5b; text-decoration:none;">TikTok {{ $tiktok['handle'] }}</a>@if (! $loop->last) &nbsp;·&nbsp; @endif
                                @endforeach
                            </p>
                            <p style="margin:10px 0 0; color:#a08c8f;">
                                &copy; {{ now()->year }} {{ $brand['name'] }} &middot; <a href="{{ $site }}" style="color:#a08c8f;">flbeauty.it</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
