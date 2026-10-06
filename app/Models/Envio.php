<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Envio extends Model
{
    protected $table = 'envio';
    protected $primaryKey = 'id_envio';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'id_venta',
        'direccion_envio',
        'ciudad',
        'fecha_envio',
        'id_estado',
        'nit_transportadora',
    ];


    public function venta()
    {
        return $this->belongsTo(\App\Models\Venta::class, 'id_venta', 'id_venta');
    }

    public function estado()
    {
        return $this->belongsTo(\App\Models\Estado::class, 'id_estado', 'id_estado');
    }

    public function transportadora()
    {
        return $this->belongsTo(\App\Models\Transportadora::class, 'nit_transportadora', 'nit');
    }
}
