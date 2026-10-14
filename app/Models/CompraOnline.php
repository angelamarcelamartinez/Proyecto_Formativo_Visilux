<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompraOnline extends Model
{
    protected $table = 'compra_online';
    protected $primaryKey = 'id_compra_on';
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
        'id_usuario_nr',
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

    public function usuariosNoRegistrados()
    {
        return $this->belongsTo(\App\Models\UsuariosNoRegistrados::class, 'id_usuario_nr', 'id_usuario_nr');
    }
}
