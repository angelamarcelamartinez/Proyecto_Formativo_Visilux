<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'producto';
    protected $primaryKey = 'id_producto';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'id_producto',
        'nombre_producto',
        'descripcion',
        'precio',
        'imagen',
        'id_marca',
        'id_modelo',
        'id_talla',
        'color',
        'fecha_vencimiento',
        'id_categoria',
        'id_estado',
        'id_tipo_pro',
    ];

    /**
     * Solo los productos activos (id_estado = 1) deben verse en el
     * catálogo público -- este scope evita repetir el where en cada
     * consulta del ProductoController.
     */
    public function scopeActivos($query)
    {
        return $query->where('id_estado', 1);
    }


    public function marca()
    {
        return $this->belongsTo(\App\Models\Marca::class, 'id_marca', 'id_marca');
    }

    public function modelo()
    {
        return $this->belongsTo(\App\Models\Modelo::class, 'id_modelo', 'id_modelo');
    }

    public function talla()
    {
        return $this->belongsTo(\App\Models\Talla::class, 'id_talla', 'id_talla');
    }

    public function categoriaProducto()
    {
        return $this->belongsTo(\App\Models\CategoriaProducto::class, 'id_categoria', 'id_categoria');
    }

    public function tipoProducto()
    {
        return $this->belongsTo(\App\Models\TipoProducto::class, 'id_tipo_pro', 'id_tipo_pro');
    }
}
