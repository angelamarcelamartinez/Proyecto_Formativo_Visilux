<?php

// Modelo de la tabla "optometra": TODA la información del optómetra vive aquí (no tiene fila en "usuario").
namespace App\Models;

/**
 * El optómetra es una cuenta propia: inicia sesión con optometra.correo y optometra.contrasena (bcrypt).
 *
 * Hereda de Usuario para que el login, el bloqueo por intentos fallidos, el cambio de contraseña
 * temporal, los correos y los middlewares lo traten igual que a cualquier empleado. Para eso los
 * nombres que usa el resto del sistema se traducen a las columnas de esta tabla:
 *
 *   documento → doc_optometra     nombres  → nombre
 *   email     → correo            password → contrasena
 *   id_rol    → siempre 7 (Optómetra); no se guarda en ninguna columna.
 *
 * En consultas (where, orderBy...) use los nombres REALES de las columnas: doc_optometra, correo, nombre.
 * La traducción solo aplica al leer o escribir atributos del modelo ($optometra->email, ->update([...])).
 */
class Optometra extends Usuario
{
    // Nombre del sistema → columna real de la tabla optometra.
    public const ALIAS = [
        'documento' => 'doc_optometra',
        'nombres' => 'nombre',
        'email' => 'correo',
        'password' => 'contrasena',
    ];

    protected $table = 'optometra';
    protected $primaryKey = 'doc_optometra';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'doc_optometra', 'nombre', 'apellido', 'telefono', 'correo', 'contrasena',
        'fecha_creacion', 'ultimo_acceso', 'foto', 'tarjeta_profesional',
        'id_tipo_docu', 'id_estado', 'id_especialidad', 'nit_empresa', 'remember_token',
        // Alias (se traducen a la columna real al guardar).
        'documento', 'nombres', 'email', 'password',
    ];

    protected $hidden = ['contrasena', 'remember_token'];

    public static function rolId(): int
    {
        return (int) config('visioptica.rol_optometra', 7);
    }

    public function getAttribute($key)
    {
        if ($key === 'id_rol') {
            return static::rolId();
        }

        return parent::getAttribute(self::ALIAS[$key] ?? $key);
    }

    public function setAttribute($key, $value)
    {
        // El rol no se guarda: estar en esta tabla ya significa ser optómetra.
        if ($key === 'id_rol') {
            return $this;
        }

        return parent::setAttribute(self::ALIAS[$key] ?? $key, $value);
    }

    public function getAuthPassword()
    {
        return $this->attributes['contrasena'] ?? null;
    }

    public function getAuthPasswordName()
    {
        return 'contrasena';
    }

    /** ¿Ya tiene cuenta? Un optómetra sin contraseña está registrado pero no puede iniciar sesión. */
    public function tieneCuenta(): bool
    {
        return ! empty($this->attributes['contrasena']);
    }

    public function especialidadRel()
    {
        return $this->belongsTo(\App\Models\Especialidad::class, 'id_especialidad', 'id_especialidad');
    }
}
