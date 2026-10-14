<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsuariosNoRegistrados extends Model
{
    protected $table = 'usuarios_no_registrados';
    protected $primaryKey = 'id_usuario_nr';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'documento',
        'correo',
        'nombre',
        'telefono',
        'motivo_cita',
        'fecha_registro',
    ];

}
