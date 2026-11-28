@extends('layouts.optometra')

@section('title', 'Pacientes')
@section('breadcrumb', 'Pacientes')

@section('content')
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
        <div>
            <h1 class="font-serif text-2xl">Pacientes</h1>
            <p class="text-sm text-muted">Los que has atendido o tienen cita contigo. Busca por documento o nombre para ver a cualquier paciente de la óptica.</p>
        </div>
        <form method="GET" class="flex gap-2 w-full md:w-auto">
            <input type="search" name="q" value="{{ $q }}" placeholder="Documento o nombre…" class="op-input md:w-72">
            <button class="op-btn op-btn-primary">Buscar</button>
        </form>
    </div>

    <section class="op-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="op-table">
                <thead>
                    <tr><th>Paciente</th><th>Documento</th><th>Contacto</th><th class="text-center">Citas</th><th class="text-center">Historias</th><th>Última cita</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($mios as $p)
                        <tr>
                            <td class="font-medium">{{ $p->nombres }} {{ $p->apellido }}</td>
                            <td class="tabular-nums">{{ $p->documento }}</td>
                            <td class="text-xs text-muted">{{ $p->telefono }}<br>{{ $p->email }}</td>
                            <td class="text-center">{{ $p->citas }}</td>
                            <td class="text-center">{{ $p->historias }}</td>
                            <td>{{ $p->ultima_cita ? \Illuminate\Support\Carbon::parse($p->ultima_cita)->format('d/m/Y') : '—' }}</td>
                            <td class="text-right">
                                <a href="{{ route('optometra.pacientes.show', $p->documento) }}" class="op-btn op-btn-ghost op-btn-sm">Ver ficha</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-10">
                            {{ $q !== '' ? 'Ninguno de tus pacientes coincide con «' . $q . '».' : 'Todavía no tienes pacientes asignados.' }}
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($mios->hasPages())
            <div class="px-5 py-3 border-t border-olive-100 flex items-center justify-between text-sm">
                <span class="text-muted">Página {{ $mios->currentPage() }} de {{ $mios->lastPage() }}</span>
                <span class="flex gap-2">
                    @if ($mios->previousPageUrl())<a href="{{ $mios->previousPageUrl() }}" class="op-btn op-btn-ghost op-btn-sm">← Anterior</a>@endif
                    @if ($mios->nextPageUrl())<a href="{{ $mios->nextPageUrl() }}" class="op-btn op-btn-ghost op-btn-sm">Siguiente →</a>@endif
                </span>
            </div>
        @endif
    </section>

    @if ($q !== '')
        <section class="op-card overflow-hidden mt-6">
            <div class="op-card-head">
                <h2 class="font-semibold text-sm">Otros pacientes de la óptica</h2>
                <span class="text-xs text-muted">Aún no los has atendido</span>
            </div>
            @forelse ($otros as $p)
                <a href="{{ route('optometra.pacientes.show', $p->documento) }}" class="px-5 py-3 flex items-center justify-between gap-3 text-sm hover:bg-olive-50 border-t border-olive-100 first:border-t-0">
                    <span><b class="font-medium">{{ $p->nombres }} {{ $p->apellido }}</b> <span class="text-muted">· {{ $p->documento }}</span></span>
                    <span class="text-xs text-muted">{{ $p->telefono }}</span>
                </a>
            @empty
                <p class="px-5 py-4 text-sm text-muted">Sin coincidencias.</p>
            @endforelse
        </section>
    @endif
@endsection
