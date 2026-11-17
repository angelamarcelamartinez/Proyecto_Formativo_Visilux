<?php

// Este modelo está en app/Models, por eso su namespace es App\Models.
namespace App\Models;

// Model es la clase base de Laravel (Eloquent). Al extenderla, esta clase "es" la tabla empresa:
// con ella se leen, crean y actualizan filas sin escribir SQL a mano.
use Illuminate\Database\Eloquent\Model;
// Str trae funciones para texto; aquí se usa Str::slug() para crear direcciones amigables.
use Illuminate\Support\Str;

/**
 * Cada óptica cliente de VisiOptica. Su identificador es el NIT, guardado como
 * número entero sin puntos ni guion (9 dígitos + DV): 800000000-9 → 8000000009.
 */
// Una fila de la tabla "empresa" = un objeto Empresa = una óptica.
class Empresa extends Model
{
    // Nombre de la tabla en la base de datos (si no se dijera, Laravel buscaría "empresas").
    protected $table = 'empresa';
    // Llave primaria: aquí NO es "id", es el NIT.
    protected $primaryKey = 'nit';
    // El NIT no se autogenera (no es autoincremental): lo escribe la persona al registrar la óptica.
    public $incrementing = false;
    // El tipo de la llave primaria es entero (int), no texto.
    protected $keyType = 'int';
    // La tabla no tiene las columnas created_at / updated_at, así que Laravel no debe intentar llenarlas.
    public $timestamps = false;

    // Lista blanca de columnas que se pueden llenar de golpe con create() o update().
    // Protege contra que alguien envíe por el formulario un campo que no debería poder cambiar.
    protected $fillable = [
        'nit', 'nombre', 'slug', 'email', 'telefono', 'direccion',
        'ciudad', 'estado', 'prueba_usada', 'fecha_registro',
        'camara_comercio',   // Ruta del PDF del certificado de la Cámara de Comercio.
    ];

    // Conversión automática de tipos al leer de la base de datos.
    protected $casts = [
        'nit' => 'integer',          // El NIT siempre llega como número.
        'prueba_usada' => 'boolean', // 1/0 de la BD se vuelve true/false.
        'fecha_registro' => 'datetime', // Texto de fecha se vuelve objeto Carbon (se puede formatear, sumar días...).
    ];

    /**
     * La óptica que se muestra en «/»: la de VISIOPTICA_EMPRESA_PRINCIPAL. Se busca con o sin guion
     * (9000000001 / 900000000-1) y, si no existe, se usa la primera óptica registrada para no dejar el
     * sitio en blanco.
     */
    // "static" = se llama sin crear objeto: Empresa::principal(). "?self" = devuelve una Empresa o null.
    public static function principal(): ?self
    {
        // Lee el NIT de la empresa principal desde config/visioptica.php (que a su vez lo toma del .env).
        $nit = config('visioptica.empresa_principal');

        // "??" prueba en orden y se queda con la primera opción que no sea null:
        return static::find($nit)   // 1) Buscar por llave primaria (el NIT tal cual).
            // 2) Buscar quitando guiones: REPLACE quita "-" de la columna; preg_replace('/\D/','',...) deja solo
            //    los dígitos del valor configurado. El "?" es un parámetro seguro (evita inyección SQL).
            ?? static::whereRaw("REPLACE(nit, '-', '') = ?", [preg_replace('/\D/', '', (string) $nit)])->first()
            // 3) Si nada coincide, tomar la óptica más antigua registrada, para que el sitio nunca quede vacío.
            ?? static::orderBy('fecha_registro')->first();
    }

    // RELACIÓN: una empresa tiene MUCHAS licencias. Se usa como $empresa->licencias().
    // hasMany(Modelo, columna en la otra tabla, columna en esta tabla):
    // licencia.nit_empresa = empresa.nit.
    public function licencias()
    {
        return $this->hasMany(Licencia::class, 'nit_empresa', 'nit');
    }

    // RELACIÓN: una empresa tiene MUCHOS usuarios (usuario.nit_empresa = empresa.nit).
    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'nit_empresa', 'nit');
    }

    // RELACIÓN: una empresa tiene UNA página pública personalizada (hasOne = uno a uno).
    public function pagina()
    {
        return $this->hasOne(PaginaEmpresa::class, 'nit_empresa', 'nit');
    }

    /** ¿Tiene cargado el PDF de la Cámara de Comercio? */
    public function tieneCamara(): bool
    {
        // Delega en la clase CamaraComercio, que revisa si el archivo guardado realmente existe en disco.
        // $this->camara_comercio es la ruta guardada en la columna de esta empresa.
        return \App\Support\CamaraComercio::existe($this->camara_comercio);
    }

    /**
     * Se registró desde /planes y espera que el superadmin apruebe el pago.
     */
    public function estaPendiente(): bool
    {
        // true solo si la columna estado vale exactamente 'pendiente'.
        return $this->estado === 'pendiente';
    }

    // ¿El superadmin la suspendió? Una óptica suspendida no puede entrar aunque tenga licencia.
    public function estaSuspendida(): bool
    {
        return $this->estado === 'suspendida';
    }

    /**
     * La licencia que la cubre hoy (activa y con fecha_fin >= hoy).
     */
    // Devuelve la Licencia vigente o null si no hay ninguna. Es la base de Licencias::puedeIngresar().
    public function licenciaVigente(): ?Licencia
    {
        return $this->licencias()              // Empieza desde las licencias de ESTA óptica.
            ->with('plan')                     // Carga también el plan (evita una consulta extra después).
            ->where('estado', 'activa')        // Solo las activas.
            ->whereDate('fecha_inicio', '<=', today()) // Que ya hayan empezado (una renovación futura aún no cuenta).
            ->whereDate('fecha_fin', '>=', today())    // Y que no hayan terminado.
            ->orderByDesc('fecha_fin')         // Si hubiera varias, la que termina más tarde primero.
            ->first();                         // Trae solo una (o null).
    }

    /**
     * Último día cubierto contando renovaciones ya aprobadas que empiezan
     * después de la actual. Es lo que se usa para "días restantes".
     */
    // Devuelve la fecha final más lejana (objeto Carbon) o null si no tiene cobertura.
    public function coberturaHasta(): ?\Illuminate\Support\Carbon
    {
        $fin = $this->licencias()
            ->where('estado', 'activa')            // Licencias activas (incluye renovaciones que empiezan en el futuro).
            ->whereDate('fecha_fin', '>=', today()) // Que no hayan terminado.
            ->max('fecha_fin');                    // De todas ellas, la fecha_fin más lejana.

        // Si hay fecha, la convierte de texto a Carbon; si no hay ninguna, devuelve null.
        return $fin ? \Illuminate\Support\Carbon::parse($fin) : null;
    }

    // Cuántos días le quedan a la óptica (o null si no tiene cobertura).
    public function diasRestantes(): ?int
    {
        $fin = $this->coberturaHasta(); // Fecha hasta la que está cubierta.

        // diffInDays(fecha, false): diferencia en días entre hoy y $fin; "false" permite resultado negativo.
        // (int) lo convierte a número entero. Si no hay cobertura, null.
        return $fin ? (int) today()->diffInDays($fin, false) : null;
    }

    // Dirección (URL) de la página pública de la óptica.
    public function urlPagina(): string
    {
        // Si esta es la óptica principal, o no tiene slug, su página es la raíz del sitio "/".
        return $this->nit === (int) config('visioptica.empresa_principal') || ! $this->slug
            ? url('/')
            // Si no, usa la ruta con nombre 'optica.show' pasando su slug, ej. /o/optica-vision-plus.
            : route('optica.show', $this->slug);
    }

    /**
     * Genera un slug libre a partir del nombre: "Óptica Visión Plus" → "optica-vision-plus".
     */
    // NOTA: en el archivo original este comentario quedó pegado encima de nitFormateado() por error
    // y el de abajo describe esa función; el comentario de arriba en realidad corresponde a slugDisponible().
    /** NIT para mostrar: 8000000009 → "800000000-9". */
    public function nitFormateado(): string
    {
        // Usa la clase Nit para ponerle el guion antes del dígito de verificación al mostrarlo.
        return \App\Support\Nit::formatear($this->nit);
    }

    // Crea un "slug" (texto para la URL) que NO esté repetido en la tabla.
    // $exceptoNit: al editar una óptica se ignora a sí misma para no chocar con su propio slug.
    // "int|string|null" = acepta número, texto o nada.
    public static function slugDisponible(string $nombre, int|string|null $exceptoNit = null): string
    {
        // Str::slug quita tildes, pasa a minúsculas y cambia espacios por guiones. Si queda vacío, usa 'optica'.
        $base = Str::slug($nombre) ?: 'optica';
        $slug = $base; // Primer intento: el slug limpio.
        $i = 2;        // Contador para sufijos: -2, -3, ...

        // Mientras ya exista una óptica con ese slug...
        while (static::where('slug', $slug)
            // when(...) agrega esta condición solo si $exceptoNit trae valor: ignora a la propia óptica.
            ->when($exceptoNit, fn ($q) => $q->where('nit', '<>', $exceptoNit))
            ->exists()) {
            // ...prueba con base-2, base-3, etc. ($i++ usa el valor y luego lo aumenta).
            $slug = $base . '-' . $i++;
        }

        // Devuelve el primer slug libre.
        return $slug;
    }
}
