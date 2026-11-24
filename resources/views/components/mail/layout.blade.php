<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;background-color:#f8f4ef;color:#342c27;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f8f4ef;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="padding:28px 32px;background:#ead8c8;text-align:center;">
                            <span style="font-size:26px;font-weight:700;letter-spacing:1px;color:#6a4b3b;">Bébito</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;font-size:16px;line-height:1.6;">
                            {{ $slot }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px;background:#f8f4ef;color:#786c64;font-size:13px;text-align:center;">
                            Made with care for your little ones.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
