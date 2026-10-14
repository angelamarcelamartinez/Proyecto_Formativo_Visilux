<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentaMedicamento extends Model
{
    protected $table = 'venta_medicamento';
    protected $primaryKey = 'id_venta_med';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'fecha',
        'total',
    ];


    public function usuario()
    {
        return $this->belongsTo(\App\Models\Usuario::class, 'id_usuario', 'documento');
    }
}
