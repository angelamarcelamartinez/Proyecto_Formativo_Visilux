<?php

// Modelo de la tabla "plan": cada plan que vende VisiOptica (prueba gratis, 1 mes, 6 meses...).
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Extender Model permite leer y guardar filas con Plan::where(...), Plan::find(...), etc.
class Plan extends Model
{
    // Nombre de la tabla (si no se indica, Laravel buscaría "plans").
    protected $table = 'plan';
    // La llave primaria se llama id_plan (no "id").
    protected $primaryKey = 'id_plan';
    // La tabla no tiene created_at / updated_at.
    public $timestamps = false;

    // Columnas permitidas para asignación masiva (create/update). El superadmin las edita en Planes.
    protected $fillable = ['nombre', 'meses', 'precio', 'es_prueba', 'destacado', 'activo'];

    // Convierte automáticamente los tipos: precio decimal, meses entero y las demás columnas a true/false.
    protected $casts = [
        'precio' => 'float',
        'meses' => 'integer',
        'es_prueba' => 'boolean',
        'destacado' => 'boolean',
        'activo' => 'boolean',
    ];

    // Relación: un plan tiene muchas licencias (licencia.id_plan = plan.id_plan).
    public function licencias()
    {
        return $this->hasMany(Licencia::class, 'id_plan', 'id_plan');
    }

    // Texto para mostrar: "$120.000" o "Gratis" si el precio es 0.
    public function precioFormateado(): string
    {
        // number_format(valor, 0 decimales, ',' decimal, '.' miles).
        return $this->precio > 0 ? '$' . number_format($this->precio, 0, ',', '.') : 'Gratis';
    }
}
