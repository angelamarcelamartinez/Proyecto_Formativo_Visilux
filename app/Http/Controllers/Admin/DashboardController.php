<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ControlReminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $hoy = now()->toDateString();

        // 1) Citas agendadas hoy
        $citasHoy = DB::table('asignacion_cita')
            ->whereDate('fecha_cita', $hoy)
            ->count();

        // 2) Ventas del día (productos + medicamentos)
        $ventasProductos = (float) DB::table('venta')
            ->whereDate('fecha_venta', $hoy)
            ->sum('total');

        $ventasMedicamentos = (float) DB::table('venta_medicamento')
            ->whereDate('fecha', $hoy)
            ->sum('total');

        $ventasHoy = $ventasProductos + $ventasMedicamentos;

        // 3) Pedidos a proveedores en tránsito (estado "Enviado" o "En proceso")
        $pedidosEnTransito = DB::table('pedido')
            ->join('estado', 'estado.id_estado', '=', 'pedido.id_estado')
            ->whereIn('estado.nom_estado', ['Enviado', 'En proceso'])
            ->count();

        // 4) Productos con stock bajo (stock_actual <= stock_minimo)
        $stockBajo = DB::table('lote')
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->distinct('id_producto')
            ->count('id_producto');

        // 5) Pacientes próximos a cumplir un año desde su última cita
        //    (candidatos a recibir el recordatorio de control)
        $controlesPendientes = ControlReminder::proximos(30)->count();

        // --- Lista de citas con filtro de rango: día / semana / mes ---
        $rango = $request->get('rango', 'dia');
        if (! in_array($rango, ['dia', 'semana', 'mes'], true)) {
            $rango = 'dia';
        }

        $citasQuery = DB::table('asignacion_cita')
            ->join('usuario', 'usuario.documento', '=', 'asignacion_cita.id_usuario')
            ->join('optometra', 'optometra.doc_optometra', '=', 'asignacion_cita.id_optometra')
            ->join('estado', 'estado.id_estado', '=', 'asignacion_cita.id_estado');

        if ($rango === 'dia') {
            $citasQuery->whereDate('asignacion_cita.fecha_cita', now()->toDateString());
        } elseif ($rango === 'semana') {
            $citasQuery->whereBetween('asignacion_cita.fecha_cita', [
                now()->startOfWeek()->toDateString(),
                now()->endOfWeek()->toDateString(),
            ]);
        } else { // mes
            $citasQuery->whereMonth('asignacion_cita.fecha_cita', now()->month)
                ->whereYear('asignacion_cita.fecha_cita', now()->year);
        }

        $citasLista = $citasQuery
            ->orderBy('asignacion_cita.fecha_cita')
            ->orderBy('asignacion_cita.hora_cita')
            ->limit(50)
            ->get([
                'asignacion_cita.id_cita',
                'asignacion_cita.fecha_cita',
                'asignacion_cita.hora_cita',
                'usuario.nombres as paciente',
                'optometra.nombre as optometra',
                'estado.nom_estado as estado',
            ]);

        return view('admin.dashboard.index', [
            'citasHoy' => $citasHoy,
            'ventasHoy' => $ventasHoy,
            'pedidosEnTransito' => $pedidosEnTransito,
            'stockBajo' => $stockBajo,
            'controlesPendientes' => $controlesPendientes,
            'citasLista' => $citasLista,
            'rango' => $rango,
            'totalTablas' => count(config('admin_tables.tables')),
            'usuario' => Auth::user(),
        ]);
    }
}
