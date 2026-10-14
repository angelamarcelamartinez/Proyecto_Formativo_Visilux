<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleFormulaMedicaProcedimiento extends Model
{
    protected $table = 'detalle_formula_medica_procedimiento';
    protected $primaryKey = 'id_detalle';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'id_formula',
        'id_procedimiento',
        'cantidad',
    ];


    public function formulaMedica()
    {
        return $this->belongsTo(\App\Models\FormulaMedica::class, 'id_formula', 'id_formula');
    }

    public function procedimiento()
    {
        return $this->belongsTo(\App\Models\Procedimiento::class, 'id_procedimiento', 'id_procedimiento');
    }
}
