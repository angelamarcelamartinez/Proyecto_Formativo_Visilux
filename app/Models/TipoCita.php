<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoCita extends Model
{
    protected $table = 'tipo_cita';
    protected $primaryKey = 'id_tipo_cita';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'nombre_tipo',
        'desc_tipo',
    ];

}
