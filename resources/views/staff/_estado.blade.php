@php
    // Etiqueta de color según el estado de la cita (clases completas, para que Tailwind las detecte).
    $estilos = [
        'confirmada' => ['bg-emerald-50 text-emerald-700 ring-emerald-200', 'bg-emerald-500'],
        'completado' => ['bg-sky-50 text-sky-700 ring-sky-200', 'bg-sky-500'],
        'pendiente'  => ['bg-amber-50 text-amber-700 ring-amber-200', 'bg-amber-500'],
        'cancelado'  => ['bg-rose-50 text-rose-700 ring-rose-200', 'bg-rose-500'],
    ];
    [$clsEstado, $puntoEstado] = $estilos[mb_strtolower(trim((string) $estado))] ?? ['bg-olive-50 text-olive-700 ring-olive-100', 'bg-olive-500'];
@endphp
<span class="inline-flex items-center gap-1.5 text-[11px] font-medium px-2.5 py-1 rounded-full ring-1 ring-inset {{ $clsEstado }}">
    <span class="w-1.5 h-1.5 rounded-full {{ $puntoEstado }}"></span>{{ $estado }}
</span>
