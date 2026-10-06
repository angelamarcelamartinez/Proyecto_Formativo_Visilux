<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvolucionTerapia extends Model
{
    protected $table = 'evolucion_terapia';
    protected $primaryKey = 'id_evolucion';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'id_agendamiento_procedimiento',
        'fecha',
        'descripcion_evolucion',
        'observaciones',
    ];


    public function agendamientoProcedimiento()
    {
        return $this->belongsTo(\App\Models\AgendamientoProcedimiento::class, 'id_agendamiento_procedimiento', 'id_agendamiento_procedimiento');
    }
}
