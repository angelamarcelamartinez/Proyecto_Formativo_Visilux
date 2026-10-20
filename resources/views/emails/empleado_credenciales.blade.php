@extends('emails.layout')

@section('titulo', $restablecida ? 'Nueva contraseña temporal' : 'Tu cuenta está lista')

@section('contenido')
    <p style="margin:0 0 16px;">
        Hola <strong>{{ $empleado->nombres }}</strong>,
        @if ($restablecida)
            el administrador de <strong>{{ $empresa->nombre }}</strong> generó una contraseña temporal nueva para tu cuenta.
        @else
            el administrador de <strong>{{ $empresa->nombre }}</strong> creó tu cuenta con el rol de <strong>{{ $rol }}</strong>.
        @endif
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F7F1E1; border-radius:12px; margin:0 0 20px;">
        <tr>
            <td style="padding:16px 20px; font-size:14px;">
                <p style="margin:0 0 6px;"><span style="color:#8A8477;">Usuario (tu correo):</span> <strong>{{ $empleado->email }}</strong></p>
                <p style="margin:0;"><span style="color:#8A8477;">Contraseña temporal:</span> <strong style="font-family: Consolas, monospace; font-size:16px;">{{ $password }}</strong></p>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 24px; text-align:center;">
        <a href="{{ url('/login') }}" style="display:inline-block; background:#7E6427; color:#FAF7F2; text-decoration:none; padding:12px 28px; border-radius:10px; font-weight:bold;">
            Iniciar sesión
        </a>
    </p>

    <p style="margin:0 0 12px; background:#FFF6DD; border-radius:10px; padding:12px 14px; font-size:14px;">
        <strong>Importante:</strong> al entrar por primera vez el sistema te pedirá crear una contraseña nueva.
        La temporal solo sirve para ese primer ingreso.
    </p>
    <p style="margin:0; font-size:13px; color:#8A8477;">
        Por seguridad no compartas este correo. Si no esperabas esta cuenta, avisa al administrador de tu óptica.
    </p>
@endsection
