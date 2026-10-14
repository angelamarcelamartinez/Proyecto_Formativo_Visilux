<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Talla extends Model
{
    protected $table = 'talla';
    protected $primaryKey = 'id_talla';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'talla',
        'id_estado',
    ];


    public function estado()
    {
        return $this->belongsTo(\App\Models\Estado::class, 'id_estado', 'id_estado');
    }
}
