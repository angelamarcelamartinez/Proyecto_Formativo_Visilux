<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ciudad extends Model
{
    protected $table = 'ciudad';
    protected $primaryKey = 'id_ciudad';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'nombre_ciudad',
        'id_departamento',
    ];


    public function departamento()
    {
        return $this->belongsTo(\App\Models\Departamento::class, 'id_departamento', 'id_departamento');
    }
}
