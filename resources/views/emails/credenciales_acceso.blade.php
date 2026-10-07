<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tu cuenta en VisiOptica</title>
</head>
<body style="margin:0; padding:0; background:#FAF7F2; font-family: Arial, Helvetica, sans-serif; color:#2E2A22;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FAF7F2; padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px; width:100%; background:#ffffff; border:1px solid #EFE2C4; border-radius:16px; overflow:hidden;">
                    <tr>
                        <td style="background:#2E2A22; padding:28px 32px; color:#FAF7F2;">
                            <p style="margin:0; font-size:12px; letter-spacing:2px; color:#C8AD6C;">VISIOPTICA</p>
                            <h1 style="margin:10px 0 0; font-family: Georgia, serif; font-weight:normal; font-size:26px;">
                                Bienvenidos, {{ $empresa->nombre }}
                            </h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px; font-size:15px; line-height:1.6;">
                            <p style="margin:0 0 16px;">
                                Aprobamos el registro de <strong>{{ $empresa->nombre }}</strong>.
                                El plan <strong>{{ $licencia->plan->nombre }}</strong> está activo
                                del {{ $licencia->fecha_inicio->format('d/m/Y') }} al {{ $licencia->fecha_fin->format('d/m/Y') }}.
                            </p>

                            <p style="margin:0 0 8px;">
                                Estos son los datos para entrar al panel de administración. El usuario es el correo de
                                <strong>{{ $admin->nombres }} {{ $admin->apellido }}</strong>, administrador de la óptica:
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F7F1E1; border-radius:12px; margin:0 0 20px;">
                                <tr>
                                    <td style="padding:16px 20px; font-size:14px;">
                                        <p style="margin:0 0 6px;"><span style="color:#8A8477;">Usuario (correo del administrador):</span> <strong>{{ $admin->email }}</strong></p>
                                        <p style="margin:0;"><span style="color:#8A8477;">Contraseña temporal:</span> <strong style="font-family: Consolas, monospace; font-size:16px;">{{ $password }}</strong></p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 24px; text-align:center;">
                                <a href="{{ url('/login') }}" style="display:inline-block; background:#7E6427; color:#FAF7F2; text-decoration:none; padding:12px 28px; border-radius:10px; font-weight:bold;">
                                    Entrar al panel
                                </a>
                            </p>

                            <p style="margin:0 0 12px; background:#FFF6DD; border-radius:10px; padding:12px 14px; font-size:14px;">
                                <strong>Importante:</strong> al entrar por primera vez el sistema le pedirá al administrador
                                crear una contraseña nueva. La temporal solo sirve para ese primer ingreso.
                            </p>

                            <p style="margin:0 0 12px;">
                                La página pública de tu óptica ya está en
                                <a href="{{ $empresa->urlPagina() }}" style="color:#7E6427;">{{ $empresa->urlPagina() }}</a>.
                            </p>
                            <p style="margin:0; font-size:13px; color:#8A8477;">
                                Por seguridad, no compartas este correo. Si no solicitaste esta cuenta, ignóralo.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
