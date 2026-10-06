<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoTerapia extends Model
{
    protected $table = 'tipo_terapia';
    protected $primaryKey = 'id_tipo_terapia';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'nomtipo_terp',
        'descripcion',
    ];

}
