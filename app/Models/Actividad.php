<?php

// Modelo de la tabla "actividad": la bitácora. Guarda quién hizo qué y cuándo (licencias, bloqueos, credenciales).
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// Auth: para saber qué usuario tiene la sesión iniciada.
use Illuminate\Support\Facades\Auth;

// Se usa siempre con Actividad::registrar(...), sin crear el objeto a mano.
class Actividad extends Model
{
    // Tabla "actividad".
    protected $table = 'actividad';
    // Llave primaria id_actividad.
    protected $primaryKey = 'id_actividad';
    // Maneja su propia columna "fecha".
    public $timestamps = false;

    // Columnas permitidas: óptica, usuario, acción, tabla afectada, id del registro, descripción y fecha.
    protected $fillable = ['nit_empresa', 'documento', 'doc_optometra', 'accion', 'tabla', 'id_registro', 'descripcion', 'fecha'];

    protected $casts = ['fecha' => 'datetime'];

    // Relación: la actividad pertenece al usuario que la hizo (por documento).
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'documento', 'documento');
    }

    // Relación: la actividad pertenece a una óptica.
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'nit_empresa', 'nit');
    }

    /**
     * Deja constancia de una acción. Nunca rompe el flujo si falla.
     */
    // Parámetros: NIT, tipo de acción, descripción, tabla afectada (opcional) e id del registro afectado (opcional).
    public static function registrar(?string $nit, string $accion, string $descripcion, ?string $tabla = null, $idRegistro = null): void
    {
        // try/catch: si guardar la bitácora falla, NO debe romper la operación principal (por eso nunca lanza error).
        try {
            static::create([
                'nit_empresa' => $nit,
                // Quién lo hizo: el usuario logueado (null si no hay sesión).
                // (el optómetra no está en `usuario`: lo suyo queda en doc_optometra).
                'documento' => Auth::user() instanceof Optometra ? null : Auth::id(),
                'doc_optometra' => Auth::user() instanceof Optometra ? Auth::id() : null,
                // mb_substr corta el texto a 30 caracteres para que quepa en la columna.
                'accion' => mb_substr($accion, 0, 30),
                'tabla' => $tabla,
                // Se guarda como texto; si no hay id queda null.
                'id_registro' => $idRegistro !== null ? (string) $idRegistro : null,
                // Máximo 255 caracteres.
                'descripcion' => mb_substr($descripcion, 0, 255),
                'fecha' => now(),
            ]);
        } catch (\Throwable $e) {
            // Si falla, solo deja el error en storage/logs para revisarlo.
            report($e);
        }
    }
}
