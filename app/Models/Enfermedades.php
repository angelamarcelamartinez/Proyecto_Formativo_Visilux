<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enfermedades extends Model
{
    protected $table = 'enfermedades';
    protected $primaryKey = 'id_enfermedad';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'nombre_enfer',
        'descripcion',
        'codigo',
    ];

}
