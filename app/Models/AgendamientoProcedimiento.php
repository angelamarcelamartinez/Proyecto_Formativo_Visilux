<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgendamientoProcedimiento extends Model
{
    protected $table = 'agendamiento_procedimiento';
    protected $primaryKey = 'id_agendamiento_procedimiento';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'fecha_inicio',
        'fecha_final',
        'id_tipo_terapia',
        'id_formula',
        'tipo',
        'estado',
        'id_historia_clinica',
    ];


    public function tipoTerapia()
    {
        return $this->belongsTo(\App\Models\TipoTerapia::class, 'id_tipo_terapia', 'id_tipo_terapia');
    }

    public function formulaMedica()
    {
        return $this->belongsTo(\App\Models\FormulaMedica::class, 'id_formula', 'id_formula');
    }

    public function historiaClinica()
    {
        return $this->belongsTo(\App\Models\HistoriaClinica::class, 'id_historia_clinica', 'id_historia_clinica');
    }
}
