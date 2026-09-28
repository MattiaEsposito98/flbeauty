<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? config('app.name') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f6f1f0; font-family: Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f6f1f0; padding:32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden;">
                    <tr>
                        <td style="background-color:#B76E79; padding:24px 32px;">
                            <span style="color:#ffffff; font-size:20px; font-weight:bold;">F&amp;L Beauty</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px; color:#2b2b2b; font-size:15px; line-height:1.6;">
                            {{ $slot }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px; background-color:#f6f1f0; color:#8a8a8a; font-size:12px;">
                            @isset($footer)
                                {{ $footer }}
                            @else
                                Hai ricevuto questa email perché sei registrato su F&amp;L Beauty.
                            @endisset
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
