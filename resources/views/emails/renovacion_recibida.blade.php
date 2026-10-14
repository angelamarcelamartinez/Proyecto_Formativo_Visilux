@extends('emails.layout')

@section('titulo', 'Recibimos tu pago')

@section('contenido')
    <p style="margin:0 0 16px;">
        Recibimos el pago de la renovación del plan <strong>{{ $licencia->plan->nombre }}</strong>
        de <strong>{{ $empresa->nombre }}</strong> por <strong>{{ $licencia->plan->precioFormateado() }}</strong>.
    </p>

    <p style="margin:0 0 16px;">
        En cuanto el equipo de VisiOptica acepte la renovación, el plan queda activo y todos los usuarios de la óptica
        pueden volver a entrar con normalidad. Te enviaremos otro correo cuando eso ocurra.
    </p>

    <p style="margin:0 0 20px; background:#F7F1E1; border-radius:10px; padding:12px 14px; font-size:14px;">
        <strong>Tus credenciales no cambian:</strong> el administrador sigue entrando con el mismo correo y la misma contraseña que ya tenía.
        Referencia del pago: <strong>{{ $licencia->referencia_pago }}</strong>.
    </p>
@endsection
