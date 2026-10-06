<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuario';
    protected $primaryKey = 'documento';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'documento',
        'nombres',
        'apellido',
        'telefono',
        'email',
        'password',
        'fecha_creacion',
        'ultimo_acceso',
        'foto',
        'id_rol',
        'id_tipo_docu',
        'id_estado',
        'id_ciudad',
        'remember_token'
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Laravel busca "password" por defecto; en esta base de datos
     * la columna se llama "contrasena", así que la mapeamos aquí.
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    public function getAuthPasswordName()
    {
        return 'password';
    }

    /**
     * Correo de recuperación de contraseña en español.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\RestablecerContrasena($token));
    }

    public function esAdministrador(): bool
    {
        return (int) $this->id_rol === 1;
    }

    public function rol()
    {
        return $this->belongsTo(\App\Models\Rol::class, 'id_rol', 'id_rol');
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(\App\Models\TipoDocumento::class, 'id_tipo_docu', 'id_tipo_docu');
    }

    public function ciudad()
    {
        return $this->belongsTo(\App\Models\Ciudad::class, 'id_ciudad', 'id_ciudad');
    }
}
