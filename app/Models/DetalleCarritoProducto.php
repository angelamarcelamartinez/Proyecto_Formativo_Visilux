<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleCarritoProducto extends Model
{
    protected $table = 'detalle_carrito_producto';
    protected $primaryKey = 'id_detalle_carr_pro';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'cantidad',
        'precio_unitario',
        'subtotal',
        'id_carrito',
        'id_producto',
    ];


    public function carrito()
    {
        return $this->belongsTo(\App\Models\Carrito::class, 'id_carrito', 'id_carrito');
    }

    public function producto()
    {
        return $this->belongsTo(\App\Models\Producto::class, 'id_producto', 'id_producto');
    }
}
