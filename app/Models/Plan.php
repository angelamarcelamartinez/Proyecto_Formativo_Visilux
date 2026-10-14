<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'plan';
    protected $primaryKey = 'id_plan';
    public $timestamps = false;

    protected $fillable = ['nombre', 'meses', 'precio', 'es_prueba', 'destacado', 'activo'];

    protected $casts = [
        'precio' => 'float',
        'meses' => 'integer',
        'es_prueba' => 'boolean',
        'destacado' => 'boolean',
        'activo' => 'boolean',
    ];

    public function licencias()
    {
        return $this->hasMany(Licencia::class, 'id_plan', 'id_plan');
    }

    public function precioFormateado(): string
    {
        return $this->precio > 0 ? '$' . number_format($this->precio, 0, ',', '.') : 'Gratis';
    }
}
