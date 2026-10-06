<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetodoCompra extends Model
{
    protected $table = 'metodo_compra';
    protected $primaryKey = 'id_metodo';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'nombre_metodo',
        'descripcion',
        'id_estado',
    ];


    public function estado()
    {
        return $this->belongsTo(\App\Models\Estado::class, 'id_estado', 'id_estado');
    }
}
