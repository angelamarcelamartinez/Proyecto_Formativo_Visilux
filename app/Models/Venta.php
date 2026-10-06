<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $table = 'venta';
    protected $primaryKey = 'id_venta';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'imagen_formula',
        'fecha_venta',
        'impuestos',
        'descuento',
        'total',
        'observaciones',
        'id_usuario',
        'id_estado',
        'id_metodo_compra',
    ];


    public function usuario()
    {
        return $this->belongsTo(\App\Models\Usuario::class, 'id_usuario', 'documento');
    }

    public function estado()
    {
        return $this->belongsTo(\App\Models\Estado::class, 'id_estado', 'id_estado');
    }

    public function metodoCompra()
    {
        return $this->belongsTo(\App\Models\MetodoCompra::class, 'id_metodo_compra', 'id_metodo');
    }
}
