<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Optometra extends Model
{
    protected $table = 'optometra';
    protected $primaryKey = 'doc_optometra';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'doc_optometra',
        'nombre',
        'apellido',
        'telefono',
        'correo',
        'contrasena',
        'fecha_creacion',
        'ultimo_acceso',
        'foto',
        'tarjeta_profesional',
        'especialidad',
        'id_tipo_docu',
        'id_estado',
        'id_especialidad',
    ];


    public function tipoDocumento()
    {
        return $this->belongsTo(\App\Models\TipoDocumento::class, 'id_tipo_docu', 'id_tipo_docu');
    }

    public function especialidad()
    {
        return $this->belongsTo(\App\Models\Especialidad::class, 'id_especialidad', 'id_especialidad');
    }
}
