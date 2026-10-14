@extends('emails.layout')

@section('titulo', 'Tu plan está activo')

@section('contenido')
    <p style="margin:0 0 16px;">
        Confirmamos el plan <strong>{{ $licencia->plan->nombre }}</strong> de <strong>{{ $empresa->nombre }}</strong>.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F7F1E1; border-radius:12px; margin:0 0 20px;">
        <tr>
            <td style="padding:16px 20px; font-size:14px;">
                <p style="margin:0 0 6px;"><span style="color:#8A8477;">Desde:</span> <strong>{{ $licencia->fecha_inicio->format('d/m/Y') }}</strong></p>
                <p style="margin:0;"><span style="color:#8A8477;">Hasta:</span> <strong>{{ $licencia->fecha_fin->format('d/m/Y') }}</strong></p>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 24px; text-align:center;">
        <a href="{{ url('/login') }}" style="display:inline-block; background:#7E6427; color:#FAF7F2; text-decoration:none; padding:12px 28px; border-radius:10px; font-weight:bold;">
            Entrar al panel
        </a>
    </p>
@endsection
