<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Cada óptica cliente de VisiOptica. Su identificador es el NIT.
 */
class Empresa extends Model
{
    protected $table = 'empresa';
    protected $primaryKey = 'nit';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'nit', 'nombre', 'slug', 'email', 'telefono', 'direccion',
        'ciudad', 'estado', 'prueba_usada', 'fecha_registro',
        'camara_comercio',
    ];

    protected $casts = [
        'prueba_usada' => 'boolean',
        'fecha_registro' => 'datetime',
    ];

    public function licencias()
    {
        return $this->hasMany(Licencia::class, 'nit_empresa', 'nit');
    }

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'nit_empresa', 'nit');
    }

    public function pagina()
    {
        return $this->hasOne(PaginaEmpresa::class, 'nit_empresa', 'nit');
    }

    /** ¿Tiene cargado el PDF de la Cámara de Comercio? */
    public function tieneCamara(): bool
    {
        return \App\Support\CamaraComercio::existe($this->camara_comercio);
    }

    /**
     * Se registró desde /planes y espera que el superadmin apruebe el pago.
     */
    public function estaPendiente(): bool
    {
        return $this->estado === 'pendiente';
    }

    public function estaSuspendida(): bool
    {
        return $this->estado === 'suspendida';
    }

    /**
     * La licencia que la cubre hoy (activa y con fecha_fin >= hoy).
     */
    public function licenciaVigente(): ?Licencia
    {
        return $this->licencias()
            ->with('plan')
            ->where('estado', 'activa')
            ->whereDate('fecha_inicio', '<=', today())
            ->whereDate('fecha_fin', '>=', today())
            ->orderByDesc('fecha_fin')
            ->first();
    }

    /**
     * Último día cubierto contando renovaciones ya aprobadas que empiezan
     * después de la actual. Es lo que se usa para "días restantes".
     */
    public function coberturaHasta(): ?\Illuminate\Support\Carbon
    {
        $fin = $this->licencias()
            ->where('estado', 'activa')
            ->whereDate('fecha_fin', '>=', today())
            ->max('fecha_fin');

        return $fin ? \Illuminate\Support\Carbon::parse($fin) : null;
    }

    public function diasRestantes(): ?int
    {
        $fin = $this->coberturaHasta();

        return $fin ? (int) today()->diffInDays($fin, false) : null;
    }

    public function urlPagina(): string
    {
        return $this->nit === config('visioptica.empresa_principal') || ! $this->slug
            ? url('/')
            : route('optica.show', $this->slug);
    }

    /**
     * Genera un slug libre a partir del nombre: "Óptica Visión Plus" → "optica-vision-plus".
     */
    public static function slugDisponible(string $nombre, ?string $exceptoNit = null): string
    {
        $base = Str::slug($nombre) ?: 'optica';
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)
            ->when($exceptoNit, fn ($q) => $q->where('nit', '<>', $exceptoNit))
            ->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
