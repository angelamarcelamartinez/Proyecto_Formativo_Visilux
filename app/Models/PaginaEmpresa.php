<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Contenido editable de la página pública de una óptica.
 */
class PaginaEmpresa extends Model
{
    protected $table = 'pagina_empresa';
    protected $primaryKey = 'nit_empresa';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    public const IMAGENES = ['logo', 'hero_imagen', 'serv1_imagen', 'serv2_imagen', 'prof_imagen'];

    protected $guarded = [];

    protected $casts = ['actualizado' => 'datetime'];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'nit_empresa', 'nit');
    }

    /**
     * Devuelve la página de la empresa; si no existe la crea con los textos por defecto.
     */
    public static function deEmpresa(Empresa $empresa): self
    {
        $contacto = $empresa->nit === config('visioptica.empresa_principal')
            ? config('visioptica.contacto_principal')
            : [
                'telefono' => $empresa->telefono,
                'email' => $empresa->email,
                'direccion' => trim(($empresa->direccion ? $empresa->direccion . ', ' : '') . ($empresa->ciudad ?? ''), ', ') ?: null,
            ];

        return static::firstOrCreate(
            ['nit_empresa' => $empresa->nit],
            array_merge(config('visioptica.pagina_por_defecto'), $contacto, ['actualizado' => now()])
        );
    }

    /**
     * URL de una imagen: la subida por la óptica o, si no hay, la de ejemplo.
     */
    public function imagen(string $campo): string
    {
        $ruta = $this->{$campo} ?: config("visioptica.imagenes_por_defecto.{$campo}");

        return asset($ruta);
    }

    /**
     * Convierte un campo de texto con varias líneas en un arreglo, sin líneas vacías.
     */
    public function lineas(string $campo): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $this->{$campo}))
            ->map(fn ($l) => trim($l))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Credenciales "Título | Institución" como [['titulo' => ..., 'lugar' => ...], ...]
     */
    public function credenciales(): array
    {
        return collect($this->lineas('prof_credenciales'))
            ->map(function ($linea) {
                [$titulo, $lugar] = array_pad(array_map('trim', explode('|', $linea, 2)), 2, '');
                return ['titulo' => $titulo, 'lugar' => $lugar];
            })
            ->all();
    }
}
