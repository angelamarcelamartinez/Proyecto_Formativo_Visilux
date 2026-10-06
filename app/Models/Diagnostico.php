<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Diagnostico extends Model
{
    protected $table = 'diagnostico';
    protected $primaryKey = 'id_diagnostico';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'id_enfermedad',
        'id_formula',
        'ojo',
    ];


    public function enfermedades()
    {
        return $this->belongsTo(\App\Models\Enfermedades::class, 'id_enfermedad', 'id_enfermedad');
    }

    public function formulaMedica()
    {
        return $this->belongsTo(\App\Models\FormulaMedica::class, 'id_formula', 'id_formula');
    }
}
