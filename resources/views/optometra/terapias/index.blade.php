@extends('layouts.optometra')

@section('title', 'Terapias')
@section('breadcrumb', 'Terapias')

@section('content')
    @php use App\Support\Optometria; use Illuminate\Support\Carbon; @endphp

    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
        <div>
            <h1 class="font-serif text-2xl">Terapias y procedimientos</h1>
            <p class="text-sm text-muted">Los planes que dejaste programados desde tus consultas. Registra cada sesión para llevar la evolución.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach (array_merge(Optometria::ESTADOS_TERAPIA, ['todas']) as $e)
                <a href="{{ route('optometra.terapias.index', ['estado' => $e]) }}"
                   class="op-btn op-btn-sm {{ $estado === $e ? 'op-btn-primary' : 'op-btn-ghost' }}">
                    {{ $e === 'todas' ? 'Todas' : $e }}
                    @if ($e !== 'todas')
                        <span class="opacity-70">{{ $conteos[$e] ?? 0 }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>

    <section class="op-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="op-table">
                <thead><tr><th>Paciente</th><th>Terapia</th><th>Inicio</th><th>Fin estimado</th><th class="text-center">Sesiones</th><th>Última sesión</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    @forelse ($terapias as $t)
                        <tr>
                            <td>
                                <a href="{{ route('optometra.pacientes.show', $t->documento) }}" class="font-medium hover:underline">{{ $t->nombres }} {{ $t->apellido }}</a>
                                <span class="block text-xs text-muted">{{ $t->documento }}</span>
                            </td>
                            <td>{{ $t->nomtipo_terp ?? '—' }}<span class="block text-xs text-muted">{{ ucfirst($t->tipo) }}</span></td>
                            <td class="whitespace-nowrap">{{ Carbon::parse($t->fecha_inicio)->format('d/m/Y g:i a') }}</td>
                            <td class="whitespace-nowrap">{{ $t->fecha_final ? Carbon::parse($t->fecha_final)->format('d/m/Y') : '—' }}</td>
                            <td class="text-center tabular-nums">{{ $t->sesiones }}{{ $t->sesiones_plan ? ' / ' . $t->sesiones_plan : '' }}</td>
                            <td class="whitespace-nowrap">{{ $t->ultima_sesion ? Carbon::parse($t->ultima_sesion)->format('d/m/Y') : '—' }}</td>
                            <td><span class="{{ Optometria::colorEstado($t->estado) }}">{{ $t->estado ?? '—' }}</span></td>
                            <td class="text-right"><a href="{{ route('optometra.terapias.show', $t->id_agendamiento_procedimiento) }}" class="op-btn op-btn-ghost op-btn-sm">Abrir</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-10">No hay terapias {{ $estado !== 'todas' ? 'en estado ' . mb_strtolower($estado) : '' }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($terapias->hasPages())
            <div class="px-5 py-3 border-t border-olive-100 flex justify-end gap-2 text-sm">
                @if ($terapias->previousPageUrl())<a href="{{ $terapias->previousPageUrl() }}" class="op-btn op-btn-ghost op-btn-sm">← Anterior</a>@endif
                @if ($terapias->nextPageUrl())<a href="{{ $terapias->nextPageUrl() }}" class="op-btn op-btn-ghost op-btn-sm">Siguiente →</a>@endif
            </div>
        @endif
    </section>
@endsection
