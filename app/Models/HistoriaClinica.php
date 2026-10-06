<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoriaClinica extends Model
{
    protected $table = 'historia_clinica';
    protected $primaryKey = 'id_historia_clinica';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'fecha_registro',
        'tratamiento',
        'antecedentes_clinicos',
        'id_asignacioncita',
        'id_formula',
        'recomendaciones',
        'id_diagnostico',
    ];


    public function asignacionCita()
    {
        return $this->belongsTo(\App\Models\AsignacionCita::class, 'id_asignacioncita', 'id_cita');
    }

    public function formulaMedica()
    {
        return $this->belongsTo(\App\Models\FormulaMedica::class, 'id_formula', 'id_formula');
    }

    public function diagnostico()
    {
        return $this->belongsTo(\App\Models\Diagnostico::class, 'id_diagnostico', 'id_diagnostico');
    }
}
