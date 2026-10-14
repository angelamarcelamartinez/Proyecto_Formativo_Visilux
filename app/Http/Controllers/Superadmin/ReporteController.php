<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Models\Empresa;
use App\Models\Licencia;
use App\Support\Licencias;
use App\Support\SimplePdf;
use App\Support\SimpleXlsx;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Reportes del superadmin: ingresos por licencias, ópticas, actividad y las
 * ópticas que requieren atención (desde aquí se mandan los avisos).
 * Los reportes de ingresos y de ópticas se pueden bajar en Excel y en PDF.
 */
class ReporteController extends Controller
{
    private const ETIQUETAS_ESTADO = [
        'vigente' => 'Vigente',
        'por_vencer' => 'Por vencer',
        'vencida' => 'Vencida',
        'sin_licencia' => 'Sin licencia',
        'pendiente_pago' => 'Esperando aprobación',
        'suspendida' => 'Suspendida',
    ];

    public function index(Request $request): View
    {
        Licencias::marcarVencidas();

        // ---- Ópticas (todas, para el reporte y para "requieren atención")
        $todas = DB::table('v_estado_licencias')->orderBy('empresa')->get();
        $conteos = $todas->countBy('estado_licencia');

        $estado = $this->estadoValido($request->get('estado'));
        $opticas = $estado === 'todas' ? $todas : $todas->where('estado_licencia', $estado)->values();

        $alertas = $todas
            ->whereIn('estado_licencia', ['por_vencer', 'vencida', 'sin_licencia'])
            ->sortBy(fn ($o) => $o->dias_restantes ?? -9999)
            ->values();

        // ---- Ingresos por licencias
        [$desde, $hasta] = $this->rangoMeses($request);
        $pagos = $this->pagosDelRango($desde, $hasta);
        $porMes = $pagos->groupBy(fn ($l) => $l->fecha_aprobacion->format('Y-m'));

        $meses = [];
        for ($m = $desde->copy(); $m->lte($hasta); $m->addMonth()) {
            $clave = $m->format('Y-m');
            $grupo = $porMes[$clave] ?? collect();
            $meses[] = [
                'clave' => $clave,
                'etiqueta' => ucfirst($m->copy()->locale('es')->translatedFormat('F Y')),
                'corta' => ucfirst(str_replace('.', '', $m->copy()->locale('es')->translatedFormat('M'))),
                'pagos' => $grupo->count(),
                'total' => (float) $grupo->sum('valor'),
            ];
        }

        $totalIngresos = (float) $pagos->sum('valor');

        // ---- Actividad (todas las ópticas), con filtros
        $nitActividad = (string) $request->get('nit', '');
        $accion = (string) $request->get('accion', '');

        $actividad = Actividad::with(['empresa', 'usuario'])
            ->when($nitActividad !== '', fn ($q) => $q->where('nit_empresa', $nitActividad))
            ->when($accion !== '', fn ($q) => $q->where('accion', $accion))
            ->orderByDesc('fecha')
            ->paginate(15, ['*'], 'act')
            ->withQueryString()
            ->fragment('actividad');

        return view('superadmin.reportes.index', [
            'alertas' => $alertas,
            'opticas' => $opticas,
            'conteos' => $conteos,
            'totalOpticas' => $todas->count(),
            'estado' => $estado,
            'desde' => $desde->format('Y-m'),
            'hasta' => $hasta->format('Y-m'),
            'meses' => $meses,
            'grafica' => [
                'labels' => collect($meses)->pluck('corta')->values(),
                'data' => collect($meses)->pluck('total')->values(),
            ],
            'totalIngresos' => $totalIngresos,
            'cantidadPagos' => $pagos->count(),
            'actividad' => $actividad,
            'listaOpticas' => Empresa::orderBy('nombre')->get(['nit', 'nombre']),
            'acciones' => Actividad::select('accion')->distinct()->orderBy('accion')->pluck('accion'),
            'nitActividad' => $nitActividad,
            'accion' => $accion,
            'etiquetas' => self::ETIQUETAS_ESTADO,
        ]);
    }

    /**
     * Descarga un reporte: /superadmin/reportes/{opticas|ingresos}/{excel|pdf}
     */
    public function descargar(Request $request, string $reporte, string $formato): Response|BinaryFileResponse
    {
        abort_unless(in_array($reporte, ['opticas', 'ingresos'], true) && in_array($formato, ['excel', 'pdf'], true), 404);

        Licencias::marcarVencidas();

        $datos = $reporte === 'ingresos' ? $this->datosIngresos($request) : $this->datosOpticas($request);
        $archivo = Str::slug($datos['archivo']) . '_' . now()->format('Y-m-d_His');

        if ($formato === 'pdf') {
            $pdf = SimplePdf::build($datos['titulo'], $datos['subtitulo'], $datos['encabezados'], $datos['filas'], $datos['resumen'], $datos['alinear']);

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $archivo . '.pdf"',
            ]);
        }

        try {
            $ruta = SimpleXlsx::build($datos['hoja'], $datos['encabezados'], $datos['filas_excel']);

            return response()->download($ruta, $archivo . '.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\RuntimeException $e) {
            // Plan B si el servidor no tiene la extensión zip: CSV que Excel abre igual
            $csv = "\xEF\xBB\xBF" . implode(';', $datos['encabezados']) . "\n";
            foreach ($datos['filas_excel'] as $fila) {
                $csv .= implode(';', array_map(fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"', $fila)) . "\n";
            }

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $archivo . '.csv"',
            ]);
        }
    }

    // ------------------------------------------------------------- datos

    /** Reporte de ingresos: un renglón por pago aprobado en el rango de meses. */
    protected function datosIngresos(Request $request): array
    {
        [$desde, $hasta] = $this->rangoMeses($request);
        $pagos = $this->pagosDelRango($desde, $hasta)->sortByDesc('fecha_aprobacion')->values();

        $total = (float) $pagos->sum('valor');
        $periodo = $this->mesTexto($desde) . ' a ' . $this->mesTexto($hasta);

        $filas = [];
        $filasExcel = [];
        foreach ($pagos as $l) {
            $base = [
                $l->fecha_aprobacion->format('d/m/Y'),
                $l->empresa->nombre ?? $l->nit_empresa,
                $l->nit_empresa,
                $l->plan->nombre ?? '—',
                $l->fecha_inicio ? $l->fecha_inicio->format('d/m/Y') . ' - ' . $l->fecha_fin?->format('d/m/Y') : '—',
                $l->referencia_pago ?: '—',
            ];
            $filas[] = array_merge($base, ['$' . number_format($l->valor, 0, ',', '.')]);
            $filasExcel[] = array_merge($base, [(float) $l->valor]);
        }

        // Fila de total al final
        $filas[] = ['', '', '', '', '', 'TOTAL', '$' . number_format($total, 0, ',', '.')];
        $filasExcel[] = ['', '', '', '', '', 'TOTAL', $total];

        return [
            'archivo' => 'ingresos-por-licencias',
            'hoja' => 'Ingresos',
            'titulo' => 'Ingresos por licencias',
            'subtitulo' => 'Pagos aprobados · ' . $periodo,
            'encabezados' => ['Fecha de pago', 'Óptica', 'NIT', 'Plan', 'Periodo de la licencia', 'Referencia', 'Valor'],
            'filas' => $filas,
            'filas_excel' => $filasExcel,
            'alinear' => [6 => 'R'],
            'resumen' => [
                'Periodo' => $periodo,
                'Pagos aprobados' => (string) $pagos->count(),
                'Total ingresos' => '$' . number_format($total, 0, ',', '.'),
            ],
        ];
    }

    /** Reporte de ópticas: plan, vencimiento y estado de cada una. */
    protected function datosOpticas(Request $request): array
    {
        $estado = $this->estadoValido($request->get('estado'));
        $todas = DB::table('v_estado_licencias')->orderBy('empresa')->get();
        $opticas = $estado === 'todas' ? $todas : $todas->where('estado_licencia', $estado)->values();

        $filas = [];
        $filasExcel = [];
        foreach ($opticas as $o) {
            $dias = $o->dias_restantes !== null ? (int) $o->dias_restantes : null;
            $comun = [
                $o->empresa,
                $o->nit,
                $o->email,
                $o->plan ?? 'Sin plan',
                $o->fecha_inicio ? Carbon::parse($o->fecha_inicio)->format('d/m/Y') : '—',
                $o->fecha_fin ? Carbon::parse($o->fecha_fin)->format('d/m/Y') : '—',
            ];
            $estadoTxt = self::ETIQUETAS_ESTADO[$o->estado_licencia] ?? ucfirst((string) $o->estado_licencia);

            $filas[] = array_merge($comun, [$dias === null ? '—' : (string) $dias, (string) $o->usuarios_registrados, $estadoTxt]);
            $filasExcel[] = array_merge($comun, [$dias, (int) $o->usuarios_registrados, $estadoTxt]);
        }

        $filtro = $estado === 'todas' ? 'Todas las ópticas' : 'Estado: ' . (self::ETIQUETAS_ESTADO[$estado] ?? $estado);

        return [
            'archivo' => 'reporte-de-opticas',
            'hoja' => 'Ópticas',
            'titulo' => 'Reporte de ópticas',
            'subtitulo' => $filtro . ' · ' . now()->format('d/m/Y'),
            'encabezados' => ['Óptica', 'NIT', 'Correo', 'Plan', 'Inicio', 'Vence', 'Días restantes', 'Usuarios', 'Estado'],
            'filas' => $filas,
            'filas_excel' => $filasExcel,
            'alinear' => [6 => 'R', 7 => 'R'],
            'resumen' => [
                'Ópticas en el reporte' => (string) $opticas->count(),
                'Vigentes' => (string) $opticas->where('estado_licencia', 'vigente')->count(),
                'Por vencer' => (string) $opticas->where('estado_licencia', 'por_vencer')->count(),
                'Vencidas o sin licencia' => (string) $opticas->whereIn('estado_licencia', ['vencida', 'sin_licencia'])->count(),
            ],
        ];
    }

    // --------------------------------------------------------- utilidades

    /** Pagos aprobados (con valor) entre el primer día de $desde y el último de $hasta. */
    protected function pagosDelRango(Carbon $desde, Carbon $hasta)
    {
        return Licencia::with(['empresa', 'plan'])
            ->whereIn('estado', ['activa', 'vencida'])
            ->whereNotNull('fecha_aprobacion')
            ->where('valor', '>', 0)
            ->whereBetween('fecha_aprobacion', [$desde->copy()->startOfMonth(), $hasta->copy()->endOfMonth()])
            ->orderBy('fecha_aprobacion')
            ->get();
    }

    /**
     * Rango de meses pedido (?desde=2026-01&hasta=2026-06). Sin datos válidos,
     * muestra los últimos 6 meses. Máximo 36 meses.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function rangoMeses(Request $request): array
    {
        $leer = function (?string $valor): ?Carbon {
            if (! $valor || ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $valor)) {
                return null;
            }

            return Carbon::createFromFormat('!Y-m', $valor)->startOfMonth();
        };

        $hasta = $leer($request->get('hasta')) ?? now()->startOfMonth();
        $desde = $leer($request->get('desde')) ?? $hasta->copy()->subMonths(5);

        if ($desde->gt($hasta)) {
            [$desde, $hasta] = [$hasta, $desde];
        }
        if ($desde->diffInMonths($hasta) > 35) {
            $desde = $hasta->copy()->subMonths(35);
        }

        return [$desde, $hasta];
    }

    protected function estadoValido(?string $estado): string
    {
        return in_array($estado, ['vigente', 'por_vencer', 'vencida', 'sin_licencia'], true) ? $estado : 'todas';
    }

    protected function mesTexto(Carbon $mes): string
    {
        return ucfirst($mes->copy()->locale('es')->translatedFormat('F Y'));
    }
}
