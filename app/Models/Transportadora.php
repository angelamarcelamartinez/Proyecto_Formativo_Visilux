<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transportadora extends Model
{
    protected $table = 'transportadora';
    protected $primaryKey = 'nit';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'nit',
        'nombre',
        'telefono',
        'correo',
        'cobertura',
        'id_estado',
    ];

}
