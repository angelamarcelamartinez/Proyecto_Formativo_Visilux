<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeguimientoEnvio extends Model
{
    protected $table = 'seguimiento_envio';
    protected $primaryKey = 'id_seguimiento_envio';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'fecha',
        'descripcion',
        'id_envio',
    ];


    public function envio()
    {
        return $this->belongsTo(\App\Models\Envio::class, 'id_envio', 'id_envio');
    }
}
