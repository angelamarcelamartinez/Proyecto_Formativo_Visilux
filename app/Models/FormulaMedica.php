<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormulaMedica extends Model
{
    protected $table = 'formula_medica';
    protected $primaryKey = 'id_formula';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'documento_paciente',
        'fecha_formula',
        'od_esfera',
        'od_cilindro',
        'od_eje',
        'od_adc',
        'od_dist_pupilar',
        'od_av_lejos',
        'od_av_cerca',
        'oi_esfera',
        'oi_cilindro',
        'oi_eje',
        'oi_adc',
        'oi_dist_pupilar',
        'oi_av_lejos',
        'oi_av_cerca',
        'correccion',
        'forma_uso',
        'numero_dispositivos',
        'proximo_control',
        'recomendaciones',
    ];


    public function usuario()
    {
        return $this->belongsTo(\App\Models\Usuario::class, 'documento_paciente', 'documento');
    }
}
