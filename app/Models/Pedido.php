<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $table = 'pedido';
    protected $primaryKey = 'id_pedido';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'fecha_pedido',
        'fecha_entrega_estimada',
        'total_pedido',
        'observaciones',
        'id_usuario',
        'id_proveedor',
        'id_estado',
    ];


    public function proveedor()
    {
        return $this->belongsTo(\App\Models\Proveedor::class, 'id_proveedor', 'nit');
    }
}
