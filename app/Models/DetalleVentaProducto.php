<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleVentaProducto extends Model
{
    protected $table = 'detalle_venta_producto';
    protected $primaryKey = 'id_detalle_venta_producto';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'cantidad',
        'subtotal',
        'id_venta',
        'id_producto',
    ];


    public function venta()
    {
        return $this->belongsTo(\App\Models\Venta::class, 'id_venta', 'id_venta');
    }

    public function producto()
    {
        return $this->belongsTo(\App\Models\Producto::class, 'id_producto', 'id_producto');
    }
}
