<?php

namespace App\Http\Controllers\Optometra;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Support\Optometria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Simulador visual (MSD Ophthalmics · Vision Simulator, Flash 8).
 *
 * El programa original (VS-MSD-ES.exe) es un proyector de Flash: de él se extrajo el menú
 * (visionsimulator.swf) y se usa la carpeta swf/ tal cual. En el navegador lo reproduce
 * Ruffle, un emulador de Flash en WebAssembly (public/assets/ruffle), sin plugins.
 *
 * Con ?paciente= y/o ?historia= la pantalla muestra el contexto del paciente, resalta las
 * condiciones de su diagnóstico y permite dejar constancia de la explicación (simulacion_visual).
 */
class SimuladorController extends Controller
{
    public function index(Request $request): View
    {
        $paciente = null;
        $historia = null;
        $condiciones = collect();

        if ($request->filled('historia')) {
            $historia = Optometria::historia((int) $request->get('historia'));
            $paciente = Optometria::paciente((string) $historia->id_usuario);
            $condiciones = Optometria::diagnosticos($historia->id_formula)
                ->map(fn ($d) => Optometria::condicionSimulador($d->codigo, $d->nombre_enfer))
                ->filter()->unique()->values();
        } elseif ($request->filled('paciente')) {
            $paciente = Optometria::paciente((string) $request->get('paciente'));
            abort_unless($paciente, 404, 'Ese paciente no pertenece a tu óptica.');
        }

        $catalogo = config('simulador.condiciones');
        $seleccion = $request->get('condicion');
        if (! isset($catalogo[$seleccion])) {
            $seleccion = $condiciones->first();
        }

        $recientes = Optometria::tieneSimulaciones()
            ? DB::table('simulacion_visual as s')
                ->leftJoin('usuario as pac', 'pac.documento', '=', 's.documento_paciente')
                ->where('s.id_optometra', Optometria::doc())
                ->orderByDesc('s.fecha')
                ->limit(8)
                ->get(['s.*', 'pac.nombres', 'pac.apellido'])
            : collect();

        return view('optometra.simulador.index', [
            'paciente' => $paciente,
            'historia' => $historia,
            'condiciones' => $condiciones,
            'catalogo' => $catalogo,
            'escenas' => config('simulador.escenas'),
            'seleccion' => $seleccion,
            'recientes' => $recientes,
            'disponible' => is_file(config('simulador.carpeta') . '/' . config('simulador.principal')),
            'tablaLista' => Optometria::tieneSimulaciones(),
        ]);
    }

    /** Deja constancia en la historia de que se le explicó la condición al paciente con el simulador. */
    public function registrar(Request $request): RedirectResponse
    {
        if (! Optometria::tieneSimulaciones()) {
            return back()->withErrors(['general' => 'Falta ejecutar el script SQL del módulo del optómetra (tabla simulacion_visual).']);
        }

        $data = $request->validate([
            'condicion' => ['required', Rule::in(array_keys(config('simulador.condiciones')))],
            'escena' => ['nullable', 'integer', Rule::in(array_keys(config('simulador.escenas')))],
            'documento_paciente' => ['nullable', 'string', 'max:20'],
            'id_historia_clinica' => ['nullable', 'integer'],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ]);

        // Solo pacientes e historias de la óptica.
        if (! empty($data['id_historia_clinica'])) {
            $h = Optometria::historia((int) $data['id_historia_clinica']);
            $data['documento_paciente'] = $h->id_usuario;
        } elseif (! empty($data['documento_paciente'])) {
            abort_unless(Optometria::paciente((string) $data['documento_paciente']), 404);
        }

        DB::table('simulacion_visual')->insert([
            'id_optometra' => Optometria::doc(),
            'documento_paciente' => $data['documento_paciente'] ?? null,
            'id_historia_clinica' => $data['id_historia_clinica'] ?? null,
            'condicion' => $data['condicion'],
            'escena' => $data['escena'] ?? null,
            'observaciones' => $data['observaciones'] ?? null,
            'fecha' => now(),
        ]);

        $nombre = config("simulador.condiciones.{$data['condicion']}.nombre");
        Actividad::registrar(Auth::user()->nit_empresa, 'crear', "Simulador visual: {$nombre}", 'simulacion_visual', $data['documento_paciente'] ?? null);

        return back()->with('success', "Quedó registrado que se explicó «{$nombre}» con el simulador.");
    }

    /**
     * Entrega un .swf del simulador (solo con sesión de optómetra). Ruffle pide primero
     * visionsimulator.swf y luego, por rutas relativas, swf/intro.swf, swf/m1_glc.swf, etc.
     */
    public function archivo(string $ruta): BinaryFileResponse
    {
        $base = realpath(config('simulador.carpeta'));
        $archivo = $base ? realpath($base . '/' . $ruta) : false;

        abort_unless($archivo && str_starts_with($archivo, $base . DIRECTORY_SEPARATOR) && is_file($archivo), 404);

        $respuesta = response()->file($archivo, [
            'Content-Type' => 'application/x-shockwave-flash',
            'Cache-Control' => 'private, max-age=604800',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $respuesta->setAutoLastModified();

        return $respuesta;
    }
}
