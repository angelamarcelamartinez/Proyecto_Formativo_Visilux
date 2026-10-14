<x-mail::message>
{{-- Logo o Encabezamiento Corporativo --}}
# ¡Hola!

Has recibido este correo porque se solicitó un restablecimiento de contraseña para tu cuenta en **Visilux**.

{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Action Button --}}
@isset($actionText)
<?php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
?>
<x-mail::button :url="$actionUrl" :color="$color">
Restablecer mi contraseña
</x-mail::button>
@endisset

{{-- Outro Lines --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

Si no solicitaste este cambio, puedes ignorar este mensaje con total tranquilidad; tu contraseña actual seguirá siendo la misma.

{{-- Salutation --}}
Atentamente,<br>
**Equipo VisiOptica**

{{-- Subcopy --}}
@isset($actionText)
<x-slot:subcopy>
Si tienes problemas para hacer clic en el botón "Restablecer mi contraseña", copia y pega el siguiente enlace en tu navegador web:
<span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::message>