@extends('emails.layout')

@section('titulo')
    @if ($dias <= 0)
        Tu licencia vence hoy
    @else
        Tu licencia vence en {{ $dias }} {{ $dias === 1 ? 'día' : 'días' }}
    @endif
@endsection

@section('contenido')
    <p style="margin:0 0 16px;">
        El plan <strong>{{ $licencia->plan->nombre }}</strong> de <strong>{{ $empresa->nombre }}</strong>
        termina el <strong>{{ $licencia->fecha_fin->format('d/m/Y') }}</strong>.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $dias <= 7 ? '#FDECEC' : '#FFF6E0' }}; border-radius:12px; margin:0 0 20px;">
        <tr>
            <td style="padding:16px 20px; font-size:14px;">
                Desde el día siguiente ningún administrador podrá entrar al panel y la página web de la óptica
                dejará de mostrarse. Tus datos no se borran: vuelven a estar disponibles apenas renueves.
            </td>
        </tr>
    </table>

    <p style="margin:0 0 24px;">
        Si renuevas antes del vencimiento, el plan nuevo empieza cuando termine el actual: no pierdes ningún día.
    </p>

    <p style="margin:0 0 24px; text-align:center;">
        <a href="{{ route('renovar.create', ['nit' => $empresa->nit]) }}" style="display:inline-block; background:#7E6427; color:#FAF7F2; text-decoration:none; padding:12px 28px; border-radius:10px; font-weight:bold;">
            Renovar mi plan
        </a>
    </p>

    <p style="margin:0; font-size:13px; color:#8A8477;">
        También puedes renovar desde tu panel, en «Mi licencia», mientras el plan siga vigente.
    </p>
@endsection
