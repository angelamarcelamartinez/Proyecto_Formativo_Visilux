<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medicamento extends Model
{
    protected $table = 'medicamento';
    protected $primaryKey = 'id_medicamento';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
        'lote_med',
        'precio',
        'cantidad',
        'id_estado',
    ];


    public function estado()
    {
        return $this->belongsTo(\App\Models\Estado::class, 'id_estado', 'id_estado');
    }
}
