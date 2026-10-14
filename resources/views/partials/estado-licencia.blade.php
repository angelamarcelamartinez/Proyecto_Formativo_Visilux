{{--
    Etiqueta de estado de una óptica y chip de días restantes.
    Uso: @include('partials.estado-licencia', ['estado' => 'vigente', 'dias' => 120])
         Agrega 'solo' => 'estado' o 'solo' => 'dias' para mostrar solo una de las dos.
--}}
@php
    $estados = [
        'vigente'      => ['Vigente', 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'bg-emerald-500'],
        'por_vencer'   => ['Por vencer', 'bg-amber-50 text-amber-700 ring-amber-200', 'bg-amber-500'],
        'vencida'      => ['Vencida', 'bg-rose-50 text-rose-700 ring-rose-200', 'bg-rose-500'],
        'sin_licencia' => ['Sin licencia', 'bg-rose-50 text-rose-700 ring-rose-200', 'bg-rose-500'],
        'pendiente_pago' => ['Esperando aprobación', 'bg-sky-50 text-sky-700 ring-sky-200', 'bg-sky-500'],
        'suspendida'   => ['Suspendida', 'bg-stone-100 text-stone-600 ring-stone-300', 'bg-stone-500'],
        'activa'       => ['Activa', 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'bg-emerald-500'],
        'pendiente'    => ['Pendiente', 'bg-sky-50 text-sky-700 ring-sky-200', 'bg-sky-500'],
        'cancelada'    => ['Cancelada', 'bg-stone-100 text-stone-600 ring-stone-300', 'bg-stone-500'],
    ];
    $estado = is_string($estado ?? null) ? $estado : '';
    [$etiqueta, $clases, $punto] = $estados[$estado] ?? [ucfirst($estado ?: '—'), 'bg-olive-50 text-olive-700 ring-olive-100', 'bg-olive-500'];
    $solo = $solo ?? null;
    $dias = is_numeric($dias ?? null) ? (int) $dias : null;

    if ($dias === null) {
        $chip = ['—', 'text-muted'];
    } elseif ($dias < 0) {
        $chip = ['Venció hace ' . abs($dias) . ' ' . (abs($dias) === 1 ? 'día' : 'días'), 'bg-rose-50 text-rose-700'];
    } elseif ($dias === 0) {
        $chip = ['Vence hoy', 'bg-rose-50 text-rose-700'];
    } elseif ($dias <= 7) {
        $chip = [$dias . ' ' . ($dias === 1 ? 'día' : 'días'), 'bg-rose-50 text-rose-700'];
    } elseif ($dias <= config('visioptica.dias_aviso')) {
        $chip = [$dias . ' días', 'bg-amber-50 text-amber-700'];
    } else {
        $chip = [$dias . ' días', 'bg-emerald-50 text-emerald-700'];
    }
@endphp

@if ($solo !== 'dias')
    <span class="inline-flex items-center gap-1.5 text-[11px] font-medium px-2.5 py-1 rounded-full ring-1 ring-inset whitespace-nowrap {{ $clases }}">
        <span class="w-1.5 h-1.5 rounded-full {{ $punto }}"></span>
        {{ $etiqueta }}
    </span>
@endif
@if ($solo !== 'estado')
    <span class="inline-flex items-center text-xs font-semibold tabular-nums px-2 py-0.5 rounded-md whitespace-nowrap {{ $chip[1] }}">{{ $chip[0] }}</span>
@endif
