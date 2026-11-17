<?php

// Modelo de la tabla "licencia": cada compra, renovación o asignación de un plan a una óptica. Una renovación es una fila NUEVA.
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Estados posibles de la columna estado: pendiente, activa, vencida, inactiva, rechazada, cancelada.
class Licencia extends Model
{
    // Tabla "licencia" (en singular).
    protected $table = 'licencia';
    // Llave primaria id_licencia.
    protected $primaryKey = 'id_licencia';
    // Sin created_at/updated_at: las fechas se manejan con fecha_solicitud, fecha_aprobacion, etc.
    public $timestamps = false;

    // Columnas que se pueden llenar con create()/update(): óptica, plan, estado, fechas, valor, referencia de pago, observaciones.
    protected $fillable = [
        'nit_empresa', 'id_plan', 'estado', 'fecha_inicio', 'fecha_fin', 'valor',
        'referencia_pago', 'observaciones', 'fecha_solicitud', 'fecha_aprobacion', 'aviso_pago_enviado',
    ];

    // Fechas se convierten a objetos Carbon (se pueden comparar/formatear) y valor a decimal.
    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_solicitud' => 'datetime',
        'fecha_aprobacion' => 'datetime',
        'aviso_pago_enviado' => 'datetime',
        'valor' => 'float',
    ];

    // Relación inversa: cada licencia pertenece a una óptica (licencia.nit_empresa = empresa.nit).
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'nit_empresa', 'nit');
    }

    // Cada licencia pertenece a un plan.
    public function plan()
    {
        return $this->belongsTo(Plan::class, 'id_plan', 'id_plan');
    }

    // Vigente = estado activa Y hoy está entre fecha_inicio y fecha_fin.
    public function estaVigente(): bool
    {
        // lte = menor o igual; gte = mayor o igual. Se verifica que las fechas existan antes de compararlas.
        return $this->estado === 'activa'
            && $this->fecha_inicio && $this->fecha_inicio->lte(today())
            && $this->fecha_fin && $this->fecha_fin->gte(today());
    }

    // Días que faltan hasta fecha_fin (negativo si ya pasó, null si no tiene fecha).
    public function diasRestantes(): ?int
    {
        return $this->fecha_fin ? (int) today()->diffInDays($this->fecha_fin, false) : null;
    }

    // ¿Está vigente y le quedan días <= dias_aviso (30, definido en config/visioptica.php)?
    public function porVencer(): bool
    {
        $dias = $this->diasRestantes();

        return $this->estaVigente() && $dias !== null && $dias <= config('visioptica.dias_aviso');
    }
}
