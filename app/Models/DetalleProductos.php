<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleProductos extends Model
{
    protected $table = 'detalle_productos';
    protected $primaryKey = 'id_detalle_producto';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'id_producto',
        'cantidad',
        'precio_unitario',
        'id_venta',
    ];


    public function producto()
    {
        return $this->belongsTo(\App\Models\Producto::class, 'id_producto', 'id_producto');
    }

    public function venta()
    {
        return $this->belongsTo(\App\Models\Venta::class, 'id_venta', 'id_venta');
    }
}
