<?php

// Clase de apoyo: bloqueo de cuentas por intentos fallidos de inicio de sesión.
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
// Los intentos se cuentan en la CACHÉ de Laravel (no en la tabla usuario), por eso no se agregan columnas.
class BloqueoUsuarios
{
    /** 3 fallos seguidos + el 4.º también fallido = cuenta inactivada. */
    // Límite: al llegar a 4 intentos fallidos la cuenta se inactiva.
    public const MAX_INTENTOS = 4;

    // id_estado 1 = Activo.
    public const ESTADO_ACTIVO = 1;
    // id_estado 2 = Inactivo (bloqueado).
    public const ESTADO_INACTIVO = 2;

    /** Cuánto tiempo se recuerdan los intentos fallidos si no se vuelve a intentar. */
    // Los intentos fallidos se recuerdan hasta 30 días si el usuario no vuelve a intentar.
    protected const DURACION_DIAS = 30;

    // ¿El usuario está inactivo? Lo usan el login y el middleware CuentaActiva.
    public static function estaInactivo(Usuario $usuario): bool
    {
        return (int) $usuario->id_estado === self::ESTADO_INACTIVO;
    }

    /** Intentos fallidos que lleva el usuario. */
    // Cuántos intentos fallidos lleva (0 si no hay ninguno guardado).
    public static function intentos(Usuario $usuario): int
    {
        return (int) Cache::get(self::clave($usuario), 0);
    }

    /**
     * Suma un intento fallido. Si llega al máximo, inactiva la cuenta.
     * Devuelve cuántos intentos le quedan (0 = acaba de quedar inactiva).
     */
    // Se llama cuando la contraseña es incorrecta. Devuelve cuántos intentos le quedan; 0 = acaba de quedar inactivo.
    public static function registrarFallo(Usuario $usuario): int
    {
        // Intentos anteriores + este.
        $intentos = self::intentos($usuario) + 1;

        // Si ya llegó al máximo...
        if ($intentos >= self::MAX_INTENTOS) {
            // ...inactiva la cuenta con un motivo que queda en la bitácora.
            self::inactivar($usuario, 'Cuenta inactivada por ' . self::MAX_INTENTOS . ' intentos fallidos de inicio de sesión');

            return 0;
        }

        // Si aún no llega al máximo, guarda el nuevo contador.
        Cache::put(self::clave($usuario), $intentos, now()->addDays(self::DURACION_DIAS));

        // Intentos restantes que se le muestran al usuario.
        return self::MAX_INTENTOS - $intentos;
    }

    /** Inicio de sesión correcto: se olvidan los intentos fallidos. */
    // Inicio de sesión correcto o cuenta bloqueada: se borra el contador.
    public static function limpiar(Usuario $usuario): void
    {
        Cache::forget(self::clave($usuario));
    }

    // Pone la cuenta en estado 2 (inactiva).
    public static function inactivar(Usuario $usuario, string $motivo): void
    {
        // Se recuerda el estado anterior (p. ej. "Pendiente" de un administrador
        // que aún debe cambiar su contraseña temporal) para devolvérselo al activarlo.
        // Solo si aún no estaba inactivo, recuerda su estado anterior (por ejemplo 3 = pendiente) para devolvérselo al activarlo.
        if (! self::estaInactivo($usuario)) {
            Cache::forever(self::clavePrevio($usuario), (int) $usuario->id_estado);
        }

        // Cambia el estado a inactivo...
        $usuario->id_estado = self::ESTADO_INACTIVO;
        // ...y lo guarda en la base de datos.
        $usuario->save();

        self::limpiar($usuario);
        // Bitácora: queda escrito por qué y a quién se inactivó.
        Actividad::registrar($usuario->nit_empresa, 'editar', "{$motivo}: {$usuario->email}", 'usuario', $usuario->documento);
    }

    /**
     * Activa la cuenta y le envía un correo al usuario avisándole.
     * Devuelve true si el correo se pudo enviar.
     */
    // Desbloquea: restaura el estado previo (o 1 si no hay) y avisa por correo al usuario.
    public static function activar(Usuario $usuario, string $motivo): bool
    {
        // Cache::pull lee el estado guardado y lo borra de la caché a la vez.
        $usuario->id_estado = (int) Cache::pull(self::clavePrevio($usuario), self::ESTADO_ACTIVO);
        $usuario->save();

        self::limpiar($usuario);
        Actividad::registrar($usuario->nit_empresa, 'editar', "{$motivo}: {$usuario->email}", 'usuario', $usuario->documento);

        // Envía el correo y devuelve true/false según si salió.
        return self::alReactivar($usuario);
    }

    /**
     * Siempre que una cuenta inactiva vuelve a quedar activa (botón Activar o
     * editando su estado en el formulario): se le avisa por correo al usuario.
     */
    // Envía CuentaReactivadaMail al usuario desbloqueado.
    public static function alReactivar(Usuario $usuario): bool
    {
        // Borra la marca de "solicitud de reactivación ya enviada".
        Cache::forget(self::claveSolicitud($usuario));

        try {
            // Envía el correo al usuario.
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
    // Envía a los administradores de su óptica la solicitud de activar la cuenta (máximo una vez cada 24 horas).
    public static function solicitarReactivacion(Usuario $usuario): string
    {
        // Si ya la envió en las últimas 24 horas, no la repite.
        if (Cache::has(self::claveSolicitud($usuario))) {
            return 'ya_enviada';
        }

        // Busca los correos de los administradores (rol 1) de la misma óptica que no estén inactivos.
        $correos = Usuario::where('nit_empresa', $usuario->nit_empresa)
            ->where('id_rol', 1)
            ->where('id_estado', '!=', self::ESTADO_INACTIVO)
            ->pluck('email')
            ->filter()->unique()->values()->all();

        // Si no hay a quién avisar, devuelve "sin_destinatario".
        if (empty($correos)) {
            return 'sin_destinatario';
        }

        try {
            Mail::to($correos)->send(new SolicitudReactivacionMail($usuario));
        } catch (\Throwable $e) {
            report($e);

            return 'error';
        }

        // Marca que la solicitud fue enviada durante 1 día.
        Cache::put(self::claveSolicitud($usuario), true, now()->addDay());
        Actividad::registrar($usuario->nit_empresa, 'editar', "Solicitó la activación de su cuenta: {$usuario->email}", 'usuario', $usuario->documento);

        return 'enviada';
    }

    // ¿Ya se envió una solicitud reciente?
    public static function solicitudEnviada(Usuario $usuario): bool
    {
        return Cache::has(self::claveSolicitud($usuario));
    }

    // Nombre de la clave de caché de la solicitud: reactivacion_solicitada:DOCUMENTO.
    protected static function claveSolicitud(Usuario $usuario): string
    {
        return 'reactivacion_solicitada:' . $usuario->documento;
    }

    // Clave del contador de intentos: login_fallidos:DOCUMENTO.
    protected static function clave(Usuario $usuario): string
    {
        return 'login_fallidos:' . $usuario->documento;
    }

    // Clave del estado anterior: login_estado_previo:DOCUMENTO.
    protected static function clavePrevio(Usuario $usuario): string
    {
        return 'login_estado_previo:' . $usuario->documento;
    }
}
