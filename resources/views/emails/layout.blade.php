{{-- Marco común de los correos de VisiOptica. Uso: @extends('emails.layout') con @section('titulo') y @section('contenido') --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>@yield('titulo')</title>
</head>
<body style="margin:0; padding:0; background:#FAF7F2; font-family: Arial, Helvetica, sans-serif; color:#2E2A22;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FAF7F2; padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px; width:100%; background:#ffffff; border:1px solid #EFE2C4; border-radius:16px; overflow:hidden;">
                    <tr>
                        <td style="background:#2E2A22; padding:28px 32px; color:#FAF7F2;">
                            <p style="margin:0; font-size:12px; letter-spacing:2px; color:#C8AD6C;">VISIOPTICA</p>
                            <h1 style="margin:10px 0 0; font-family: Georgia, serif; font-weight:normal; font-size:24px;">@yield('titulo')</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px; font-size:15px; line-height:1.6;">
                            @yield('contenido')
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
