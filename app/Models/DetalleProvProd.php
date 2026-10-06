<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleProvProd extends Model
{
    protected $table = 'detalle_prov_prod';
    protected $primaryKey = 'id_proveedor_producto';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'precio_ref',
        'tiempo_entrega',
        'nit_prov',
        'id_producto',
    ];


    public function proveedor()
    {
        return $this->belongsTo(\App\Models\Proveedor::class, 'nit_prov', 'nit');
    }

    public function producto()
    {
        return $this->belongsTo(\App\Models\Producto::class, 'id_producto', 'id_producto');
    }
}
