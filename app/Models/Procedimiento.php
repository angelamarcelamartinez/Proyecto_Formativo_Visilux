<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Procedimiento extends Model
{
    protected $table = 'procedimiento';
    protected $primaryKey = 'id_procedimiento';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
        'id_categoria_servicio',
    ];


    public function categoriaServicio()
    {
        return $this->belongsTo(\App\Models\CategoriaServicio::class, 'id_categoria_servicio', 'id_categoria_servicio');
    }
}
