<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Actividad extends Model
{
    protected $table = 'actividad';
    protected $primaryKey = 'id_actividad';
    public $timestamps = false;

    protected $fillable = ['nit_empresa', 'documento', 'accion', 'tabla', 'id_registro', 'descripcion', 'fecha'];

    protected $casts = ['fecha' => 'datetime'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'documento', 'documento');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'nit_empresa', 'nit');
    }

    /**
     * Deja constancia de una acción. Nunca rompe el flujo si falla.
     */
    public static function registrar(?string $nit, string $accion, string $descripcion, ?string $tabla = null, $idRegistro = null): void
    {
        try {
            static::create([
                'nit_empresa' => $nit,
                'documento' => Auth::id(),
                'accion' => mb_substr($accion, 0, 30),
                'tabla' => $tabla,
                'id_registro' => $idRegistro !== null ? (string) $idRegistro : null,
                'descripcion' => mb_substr($descripcion, 0, 255),
                'fecha' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
