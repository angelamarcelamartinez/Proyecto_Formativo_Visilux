<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MotivosCita extends Model
{
    protected $table = 'motivos_cita';
    protected $primaryKey = 'id_motivo';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'nombre_motivo',
        'descripcion',
    ];

}
