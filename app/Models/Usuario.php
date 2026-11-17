<?php

// Modelo de la tabla "usuario": todas las personas (superadmin, administradores, empleados y clientes).
namespace App\Models;

// Authenticatable: clase base que permite iniciar sesión con este modelo.
use Illuminate\Foundation\Auth\User as Authenticatable;
// Notifiable: permite enviarle notificaciones/correos al usuario.
use Illuminate\Notifications\Notifiable;

// El tipo de usuario lo decide id_rol (1 admin, 6 superadmin, etc.).
class Usuario extends Authenticatable
{
    use Notifiable;

    // Tabla "usuario".
    protected $table = 'usuario';
    // La llave primaria es el documento (no un id autoincremental).
    protected $primaryKey = 'documento';
    // El documento no se autogenera.
    public $incrementing = false;
    // Es un número entero.
    protected $keyType = 'int';
    // Sin created_at/updated_at.
    public $timestamps = false;

    // Columnas que se pueden llenar de golpe con create()/update().
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
        'nit_empresa',
        'remember_token'
    ];

    // Columnas que nunca se muestran al convertir el usuario a JSON/arreglo.
    protected $hidden = [
        'password',
    ];

    /**
     * Laravel busca "password" por defecto; en esta base de datos
     * la columna se llama "contrasena", así que la mapeamos aquí.
     */
    // Le dice a Laravel de dónde leer la contraseña para comparar al iniciar sesión.
    public function getAuthPassword()
    {
        return $this->password;
    }

    // Nombre de la columna de contraseña: "password".
    public function getAuthPasswordName()
    {
        return 'password';
    }

    /**
     * Correo de recuperación de contraseña en español.
     */
    // Envía el correo de recuperación de contraseña en español.
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\RestablecerContrasena($token));
    }

    /**
     * Un administrador cuya óptica ya fue aprobada pero que todavía no ha elegido su
     * contraseña conserva el estado "Pendiente" (id_estado 3): entra con la temporal
     * y está obligado a cambiarla. Al cambiarla pasa a "Activo" (id_estado 1).
     * Así no hace falta ninguna columna extra en la base de datos.
     */
    // Atributo calculado: $usuario->debe_cambiar_password. Es verdadero si es admin/empleado con id_estado 3 (pendiente, contraseña temporal).
    public function getDebeCambiarPasswordAttribute(): bool
    {
        // Administradores y empleados (vendedor, recepcionista, auditor...). Los clientes no usan contraseña temporal.
        // in_array comprueba que el rol sea admin (1) o empleado (3 vendedor, 4 recepcionista, 5 auditor, 7 optómetra) Y que el estado sea 3.
        return in_array((int) $this->id_rol, [(int) config('visioptica.rol_admin'), 3, 4, 5, 7], true)
            && (int) $this->id_estado === 3;
    }

    // ¿Es administrador de una óptica? (id_rol = 1).
    public function esAdministrador(): bool
    {
        return (int) $this->id_rol === 1;
    }

    // Página de inicio según el rol: cada uno entra a su propio panel.
    public function rutaInicio(): string
    {
        if ($this->esSuperadmin()) {
            return '/superadmin';
        }
        if ($this->esAdministrador()) {
            return '/admin';
        }
        if ((int) $this->id_rol === (int) config('visioptica.rol_recepcionista')) {
            return '/recepcion';
        }
        if ((int) $this->id_rol === (int) config('visioptica.rol_optometra')) {
            return '/optometra';
        }

        return '/';
    }

    // ¿Es el superadmin? Compara con config('visioptica.rol_superadmin') = 6.
    public function esSuperadmin(): bool
    {
        return (int) $this->id_rol === (int) config('visioptica.rol_superadmin');
    }

    /**
     * Óptica a la que pertenece (el superadmin no tiene).
     */
    // Relación: la óptica a la que pertenece (usuario.nit_empresa = empresa.nit).
    public function empresa()
    {
        return $this->belongsTo(\App\Models\Empresa::class, 'nit_empresa', 'nit');
    }

    // Relación: su rol.
    public function rol()
    {
        return $this->belongsTo(\App\Models\Rol::class, 'id_rol', 'id_rol');
    }

    // Relación: tipo de documento (cédula, NIT...).
    public function tipoDocumento()
    {
        return $this->belongsTo(\App\Models\TipoDocumento::class, 'id_tipo_docu', 'id_tipo_docu');
    }

    // Relación: su ciudad.
    public function ciudad()
    {
        return $this->belongsTo(\App\Models\Ciudad::class, 'id_ciudad', 'id_ciudad');
    }
}
