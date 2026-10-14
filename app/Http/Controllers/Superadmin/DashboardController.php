<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Licencia;
use App\Support\AvisosLicencia;
use App\Support\Licencias;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Panel general: primero los pagos por aprobar y debajo las cifras clave.
     * Las ópticas, los ingresos, la actividad y los avisos están en sus pestañas.
     */
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

        // Ingresos del mes en curso según la fecha en que se aprobó el pago.
        $ingresosMes = (float) Licencia::whereIn('estado', ['activa', 'vencida'])
            ->whereNotNull('fecha_aprobacion')
            ->whereBetween('fecha_aprobacion', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('valor');

        return view('superadmin.dashboard', [
            'totalOpticas' => $opticas->count(),
            'vigentes' => (int) ($porEstado['vigente'] ?? 0),
            'porVencer' => (int) ($porEstado['por_vencer'] ?? 0),
            'vencidas' => (int) (($porEstado['vencida'] ?? 0) + ($porEstado['sin_licencia'] ?? 0)),
            'ingresosMes' => $ingresosMes,
            'pendientes' => $pendientes,
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
