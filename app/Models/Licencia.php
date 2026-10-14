<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Licencia extends Model
{
    protected $table = 'licencia';
    protected $primaryKey = 'id_licencia';
    public $timestamps = false;

    protected $fillable = [
        'nit_empresa', 'id_plan', 'estado', 'fecha_inicio', 'fecha_fin', 'valor',
        'referencia_pago', 'observaciones', 'fecha_solicitud', 'fecha_aprobacion', 'aviso_pago_enviado',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_solicitud' => 'datetime',
        'fecha_aprobacion' => 'datetime',
        'aviso_pago_enviado' => 'datetime',
        'valor' => 'float',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'nit_empresa', 'nit');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'id_plan', 'id_plan');
    }

    public function estaVigente(): bool
    {
        return $this->estado === 'activa'
            && $this->fecha_inicio && $this->fecha_inicio->lte(today())
            && $this->fecha_fin && $this->fecha_fin->gte(today());
    }

    public function diasRestantes(): ?int
    {
        return $this->fecha_fin ? (int) today()->diffInDays($this->fecha_fin, false) : null;
    }

    public function porVencer(): bool
    {
        $dias = $this->diasRestantes();

        return $this->estaVigente() && $dias !== null && $dias <= config('visioptica.dias_aviso');
    }
}
