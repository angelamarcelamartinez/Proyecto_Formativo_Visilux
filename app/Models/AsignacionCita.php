<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsignacionCita extends Model
{
    protected $table = 'asignacion_cita';
    protected $primaryKey = 'id_cita';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'fecha_cita',
        'hora_cita',
        'observaciones',
        'id_usuario',
        'id_optometra',
        'id_tipo_cita',
        'id_estado',
        'id_motivo',
    ];


    public function usuario()
    {
        return $this->belongsTo(\App\Models\Usuario::class, 'id_usuario', 'documento');
    }

    public function optometra()
    {
        return $this->belongsTo(\App\Models\Optometra::class, 'id_optometra', 'doc_optometra');
    }

    public function tipoCita()
    {
        return $this->belongsTo(\App\Models\TipoCita::class, 'id_tipo_cita', 'id_tipo_cita');
    }

    public function estado()
    {
        return $this->belongsTo(\App\Models\Estado::class, 'id_estado', 'id_estado');
    }

    public function motivosCita()
    {
        return $this->belongsTo(\App\Models\MotivosCita::class, 'id_motivo', 'id_motivo');
    }
}
