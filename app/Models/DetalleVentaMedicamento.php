<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleVentaMedicamento extends Model
{
    protected $table = 'detalle_venta_medicamento';
    protected $primaryKey = 'id_detalle_venta_med';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'id_venta_med',
        'id_medicamento',
        'cantidad',
        'precio_unitario',
        'dosis',
    ];


    public function ventaMedicamento()
    {
        return $this->belongsTo(\App\Models\VentaMedicamento::class, 'id_venta_med', 'id_venta_med');
    }

    public function medicamento()
    {
        return $this->belongsTo(\App\Models\Medicamento::class, 'id_medicamento', 'id_medicamento');
    }
}
