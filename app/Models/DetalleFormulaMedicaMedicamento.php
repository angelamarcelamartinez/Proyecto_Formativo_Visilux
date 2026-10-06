<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleFormulaMedicaMedicamento extends Model
{
    protected $table = 'detalle_formula_medica_medicamento';
    protected $primaryKey = 'id_detalle';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'id_formula',
        'id_medicamento',
        'cantidad',
        'dosis',
    ];


    public function formulaMedica()
    {
        return $this->belongsTo(\App\Models\FormulaMedica::class, 'id_formula', 'id_formula');
    }

    public function medicamento()
    {
        return $this->belongsTo(\App\Models\Medicamento::class, 'id_medicamento', 'id_medicamento');
    }
}
