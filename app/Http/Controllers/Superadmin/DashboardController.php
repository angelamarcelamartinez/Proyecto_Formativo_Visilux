<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Models\Licencia;
use App\Support\AvisosLicencia;
use App\Support\Licencias;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Mantiene los estados al día aunque no exista el cron todavía.
        Licencias::marcarVencidas();

        // La vista v_estado_licencias ya trae una fila por óptica con su estado y días restantes.
        $opticas = DB::table('v_estado_licencias')->get();
        $porEstado = $opticas->countBy('estado_licencia');

        $pendientes = Licencia::with(['empresa', 'plan'])
            ->where('estado', 'pendiente')
            ->orderBy('fecha_solicitud')
            ->get();

        // Ingresos de los últimos 6 meses según la fecha en que se aprobó el pago.
        $meses = collect(range(5, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $ingresosPorMes = Licencia::whereIn('estado', ['activa', 'vencida'])
            ->whereNotNull('fecha_aprobacion')
            ->where('fecha_aprobacion', '>=', $meses->first())
            ->get(['valor', 'fecha_aprobacion'])
            ->groupBy(fn ($l) => $l->fecha_aprobacion->format('Y-m'))
            ->map(fn ($grupo) => $grupo->sum('valor'));

        $grafica = [
            'labels' => $meses->map(fn ($m) => ucfirst(str_replace('.', '', $m->locale('es')->translatedFormat('M'))))->values(),
            'data' => $meses->map(fn ($m) => (float) ($ingresosPorMes[$m->format('Y-m')] ?? 0))->values(),
        ];

        $ingresosMes = (float) ($ingresosPorMes[now()->format('Y-m')] ?? 0);

        // Ópticas que requieren atención: por vencer, vencidas o sin licencia.
        $alertas = $opticas
            ->whereIn('estado_licencia', ['por_vencer', 'vencida', 'sin_licencia'])
            ->sortBy(fn ($o) => $o->dias_restantes ?? -9999)
            ->values();

        $actividad = Actividad::with(['empresa', 'usuario'])
            ->orderByDesc('fecha')
            ->limit(8)
            ->get();

        return view('superadmin.dashboard', [
            'usuario' => Auth::user(),
            'totalOpticas' => $opticas->count(),
            'vigentes' => (int) ($porEstado['vigente'] ?? 0),
            'porVencer' => (int) ($porEstado['por_vencer'] ?? 0),
            'vencidas' => (int) (($porEstado['vencida'] ?? 0) + ($porEstado['sin_licencia'] ?? 0)),
            'suspendidas' => (int) ($porEstado['suspendida'] ?? 0),
            'ingresosMes' => $ingresosMes,
            'pendientes' => $pendientes,
            'alertas' => $alertas,
            'opticas' => $opticas->sortBy('empresa')->values(),
            'grafica' => $grafica,
            'actividad' => $actividad,
        ]);
    }

    /**
     * Hace lo mismo que el comando diario "php artisan licencias:revisar",
     * para poder probarlo o forzarlo sin esperar al cron.
     */
    public function enviarAvisos(): RedirectResponse
    {
        $r = AvisosLicencia::enviarPendientes();

        $mensaje = $r['enviados'] === 0 && $r['errores'] === 0
            ? 'No había avisos pendientes: ninguna óptica entró hoy en los últimos 30 o 7 días de su licencia sin haber sido avisada.'
            : "Avisos de vencimiento enviados: {$r['enviados']}." . ($r['errores'] ? " No se pudieron enviar: {$r['errores']}." : '');

        return back()->with('success', $mensaje . ($r['detalle'] ? ' ' . implode(' ', $r['detalle']) : ''));
    }
}
