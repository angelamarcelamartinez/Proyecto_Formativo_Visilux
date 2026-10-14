@extends('emails.layout')

@section('titulo', 'Solicitud de activación de cuenta')

@section('contenido')
    <p style="margin:0 0 16px;">
        Un usuario de tu óptica quedó <strong>inactivo</strong> por varios intentos fallidos de inicio de sesión
        y está pidiendo que actives su cuenta de nuevo.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F7F1E1; border-radius:12px; margin:0 0 20px;">
        <tr>
            <td style="padding:16px 20px; font-size:14px;">
                <p style="margin:0 0 6px;"><span style="color:#8A8477;">Nombre:</span> <strong>{{ trim($usuario->nombres . ' ' . $usuario->apellido) }}</strong></p>
                <p style="margin:0 0 6px;"><span style="color:#8A8477;">Documento:</span> <strong>{{ $usuario->documento }}</strong></p>
                <p style="margin:0 0 6px;"><span style="color:#8A8477;">Correo:</span> <strong>{{ $usuario->email }}</strong></p>
                <p style="margin:0;"><span style="color:#8A8477;">Fecha de la solicitud:</span> <strong>{{ now()->format('d/m/Y h:i a') }}</strong></p>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 24px; text-align:center;">
        <a href="{{ route('admin.crud.index', ['table' => 'usuario', 'q' => $usuario->documento]) }}"
           style="display:inline-block; background:#7E6427; color:#FAF7F2; text-decoration:none; padding:12px 28px; border-radius:10px; font-weight:bold;">
            Ir a activar el usuario
        </a>
    </p>

    <p style="margin:0; font-size:13px; color:#8A8477;">
        En el panel de administración, en <strong>Usuarios</strong>, usa el botón <strong>Activar</strong> de este usuario.
        Al activarlo le llegará un correo avisándole que ya puede ingresar.
        Si no reconoces esta solicitud, puedes dejar la cuenta inactiva.
    </p>
@endsection
