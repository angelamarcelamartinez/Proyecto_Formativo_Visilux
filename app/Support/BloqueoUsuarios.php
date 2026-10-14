<?php

namespace App\Support;

use App\Mail\CuentaReactivadaMail;
use App\Mail\SolicitudReactivacionMail;
use App\Models\Actividad;
use App\Models\Usuario;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

/**
 * Inactivación de cuentas por intentos fallidos de inicio de sesión.
 *
 * - Cada contraseña incorrecta para un correo registrado suma un intento.
 * - Al llegar a MAX_INTENTOS seguidos, el usuario pasa a "Inactivo" (id_estado 2)
 *   y no puede entrar hasta que un administrador lo active desde el panel.
 * - Un inicio de sesión correcto reinicia el contador.
 *
 * El contador se guarda en la caché de Laravel (tabla `cache`), así que no
 * hace falta agregar columnas a la tabla `usuario`.
 */
class BloqueoUsuarios
{
    /** 3 fallos seguidos + el 4.º también fallido = cuenta inactivada. */
    public const MAX_INTENTOS = 4;

    public const ESTADO_ACTIVO = 1;
    public const ESTADO_INACTIVO = 2;

    /** Cuánto tiempo se recuerdan los intentos fallidos si no se vuelve a intentar. */
    protected const DURACION_DIAS = 30;

    public static function estaInactivo(Usuario $usuario): bool
    {
        return (int) $usuario->id_estado === self::ESTADO_INACTIVO;
    }

    /** Intentos fallidos que lleva el usuario. */
    public static function intentos(Usuario $usuario): int
    {
        return (int) Cache::get(self::clave($usuario), 0);
    }

    /**
     * Suma un intento fallido. Si llega al máximo, inactiva la cuenta.
     * Devuelve cuántos intentos le quedan (0 = acaba de quedar inactiva).
     */
    public static function registrarFallo(Usuario $usuario): int
    {
        $intentos = self::intentos($usuario) + 1;

        if ($intentos >= self::MAX_INTENTOS) {
            self::inactivar($usuario, 'Cuenta inactivada por ' . self::MAX_INTENTOS . ' intentos fallidos de inicio de sesión');

            return 0;
        }

        Cache::put(self::clave($usuario), $intentos, now()->addDays(self::DURACION_DIAS));

        return self::MAX_INTENTOS - $intentos;
    }

    /** Inicio de sesión correcto: se olvidan los intentos fallidos. */
    public static function limpiar(Usuario $usuario): void
    {
        Cache::forget(self::clave($usuario));
    }

    public static function inactivar(Usuario $usuario, string $motivo): void
    {
        // Se recuerda el estado anterior (p. ej. "Pendiente" de un administrador
        // que aún debe cambiar su contraseña temporal) para devolvérselo al activarlo.
        if (! self::estaInactivo($usuario)) {
            Cache::forever(self::clavePrevio($usuario), (int) $usuario->id_estado);
        }

        $usuario->id_estado = self::ESTADO_INACTIVO;
        $usuario->save();

        self::limpiar($usuario);
        Actividad::registrar($usuario->nit_empresa, 'editar', "{$motivo}: {$usuario->email}", 'usuario', $usuario->documento);
    }

    /**
     * Activa la cuenta y le envía un correo al usuario avisándole.
     * Devuelve true si el correo se pudo enviar.
     */
    public static function activar(Usuario $usuario, string $motivo): bool
    {
        $usuario->id_estado = (int) Cache::pull(self::clavePrevio($usuario), self::ESTADO_ACTIVO);
        $usuario->save();

        self::limpiar($usuario);
        Actividad::registrar($usuario->nit_empresa, 'editar', "{$motivo}: {$usuario->email}", 'usuario', $usuario->documento);

        return self::alReactivar($usuario);
    }

    /**
     * Siempre que una cuenta inactiva vuelve a quedar activa (botón Activar o
     * editando su estado en el formulario): se le avisa por correo al usuario.
     */
    public static function alReactivar(Usuario $usuario): bool
    {
        Cache::forget(self::claveSolicitud($usuario));

        try {
            Mail::to($usuario->email)->send(new CuentaReactivadaMail($usuario));

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * Botón "Enviar tu solicitud": le envía un correo a los administradores de
     * la óptica del usuario pidiendo que activen su cuenta.
     *
     * Devuelve: 'enviada', 'ya_enviada' (ya la había enviado en las últimas
     * 24 horas), 'sin_destinatario' o 'error'.
     */
    public static function solicitarReactivacion(Usuario $usuario): string
    {
        if (Cache::has(self::claveSolicitud($usuario))) {
            return 'ya_enviada';
        }

        $correos = Usuario::where('nit_empresa', $usuario->nit_empresa)
            ->where('id_rol', 1)
            ->where('id_estado', '!=', self::ESTADO_INACTIVO)
            ->pluck('email')
            ->filter()->unique()->values()->all();

        if (empty($correos)) {
            return 'sin_destinatario';
        }

        try {
            Mail::to($correos)->send(new SolicitudReactivacionMail($usuario));
        } catch (\Throwable $e) {
            report($e);

            return 'error';
        }

        Cache::put(self::claveSolicitud($usuario), true, now()->addDay());
        Actividad::registrar($usuario->nit_empresa, 'editar', "Solicitó la activación de su cuenta: {$usuario->email}", 'usuario', $usuario->documento);

        return 'enviada';
    }

    public static function solicitudEnviada(Usuario $usuario): bool
    {
        return Cache::has(self::claveSolicitud($usuario));
    }

    protected static function claveSolicitud(Usuario $usuario): string
    {
        return 'reactivacion_solicitada:' . $usuario->documento;
    }

    protected static function clave(Usuario $usuario): string
    {
        return 'login_fallidos:' . $usuario->documento;
    }

    protected static function clavePrevio(Usuario $usuario): string
    {
        return 'login_estado_previo:' . $usuario->documento;
    }
}
