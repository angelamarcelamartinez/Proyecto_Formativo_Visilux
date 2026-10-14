@extends('emails.layout')

@section('titulo', 'Tu cuenta está activa de nuevo')

@section('contenido')
    <p style="margin:0 0 16px;">¡Hola, <strong>{{ $usuario->nombres }}</strong>!</p>

    <p style="margin:0 0 16px;">
        Tu cuenta (<strong>{{ $usuario->email }}</strong>) había sido inactivada por varios intentos fallidos
        de inicio de sesión. El administrador ya la <strong>activó de nuevo</strong>, así que puedes volver a ingresar.
    </p>

    <p style="margin:0 0 24px; text-align:center;">
        <a href="{{ url('/login') }}" style="display:inline-block; background:#7E6427; color:#FAF7F2; text-decoration:none; padding:12px 28px; border-radius:10px; font-weight:bold;">
            Iniciar sesión
        </a>
    </p>

    <p style="margin:0; font-size:13px; color:#8A8477;">
        Si no recuerdas tu contraseña, usa la opción "¿Olvidaste tu contraseña?" en la página de inicio de sesión.
        Si no fuiste tú quien intentó ingresar, te recomendamos cambiarla.
    </p>
@endsection
