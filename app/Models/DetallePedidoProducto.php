<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetallePedidoProducto extends Model
{
    protected $table = 'detalle_pedido_producto';
    protected $primaryKey = 'id_detalle_pe_pro';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'cantidad',
        'precio_unitario',
        'subtotal',
        'id_producto',
        'id_pedido',
        'id_proveedor_producto',
    ];


    public function producto()
    {
        return $this->belongsTo(\App\Models\Producto::class, 'id_producto', 'id_producto');
    }

    public function pedido()
    {
        return $this->belongsTo(\App\Models\Pedido::class, 'id_pedido', 'id_pedido');
    }

    public function detalleProvProd()
    {
        return $this->belongsTo(\App\Models\DetalleProvProd::class, 'id_proveedor_producto', 'id_proveedor_producto');
    }
}
