@extends('layouts.staff')

@section('title', 'Pacientes')
@section('breadcrumb', 'Pacientes')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-serif text-2xl">Pacientes</h1>
            <p class="text-sm text-muted">Las personas registradas en la óptica, con todas sus citas. Desde aquí les agendas una nueva.</p>
        </div>
        <a href="{{ route('recepcion.paciente.nuevo') }}" class="inline-flex items-center gap-2 bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-5 py-2.5 rounded-xl shadow-card">
            @include('partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4'])
            Registrar paciente
        </a>
    </div>

    <form method="GET" class="bg-white rounded-2xl border border-olive-100 shadow-card p-4 sm:p-5 mb-6 flex flex-wrap items-center gap-3">
        <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por documento, nombre o celular…"
               class="flex-1 min-w-[220px] bg-creamdark/40 border border-olive-100 rounded-xl py-2.5 px-3.5 text-sm focus:outline-none focus:ring-2 focus:ring-olive-300">
        <button type="submit" class="bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-5 py-2.5 rounded-xl shadow-card">Buscar</button>
        @if ($q !== '') <a href="{{ route('recepcion.pacientes') }}" class="text-sm text-ink/60 hover:text-ink">Limpiar</a> @endif
    </form>

    <div class="bg-white rounded-2xl border border-olive-100 shadow-card overflow-hidden">
        @if ($pacientes->isEmpty())
            @include('staff._vacio', ['icono' => 'users', 'titulo' => 'No hay pacientes para mostrar', 'texto' => 'Si no aparece, regístralo con el botón de arriba.'])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-cream text-left text-[11px] uppercase tracking-wider text-muted">
                            <th class="py-3 pl-6 pr-4 font-semibold">Paciente</th>
                            <th class="py-3 pr-4 font-semibold">Contacto</th>
                            <th class="py-3 pr-4 font-semibold">Próxima cita</th>
                            <th class="py-3 pr-4 font-semibold">Citas</th>
                            <th class="py-3 pr-6"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-olive-100">
                        @foreach ($pacientes as $p)
                            <tr class="hover:bg-cream/70 transition">
                                <td class="py-3 pl-6 pr-4">
                                    <p class="font-medium whitespace-nowrap">{{ $p->nombres }} {{ $p->apellido }}</p>
                                    <p class="text-xs text-muted">Doc. {{ $p->documento }}</p>
                                </td>
                                <td class="py-3 pr-4 text-ink/70">{{ $p->telefono }}<p class="text-xs text-muted">{{ $p->email }}</p></td>
                                <td class="py-3 pr-4 whitespace-nowrap text-ink/80">
                                    @if ($p->proxima)
                                        {{ \Illuminate\Support\Carbon::parse($p->proxima->fecha_cita)->format('d/m/Y') }} {{ \Illuminate\Support\Str::substr($p->proxima->hora_cita, 0, 5) }}
                                    @else
                                        Sin cita
                                    @endif
                                </td>
                                <td class="py-3 pr-4 text-ink/80 min-w-[260px]">
                                    @if ($p->citas->isEmpty())
                                        <span class="text-xs text-muted">Sin citas registradas</span>
                                    @else
                                        <details>
                                            <summary class="cursor-pointer text-xs font-semibold text-olive-600 hover:text-olive-800">{{ $p->citas->count() }} {{ $p->citas->count() === 1 ? 'cita' : 'citas' }}</summary>
                                            <ul class="mt-2 space-y-1.5">
                                                @foreach ($p->citas as $c)
                                                    <li class="text-xs">
                                                        <span class="tabular-nums font-medium">{{ \Illuminate\Support\Carbon::parse($c->fecha_cita)->format('d/m/Y') }} {{ \Illuminate\Support\Str::substr($c->hora_cita, 0, 5) }}</span>
                                                        · {{ $c->tipo ?? 'Cita' }} · {{ $c->optometra }}
                                                        <span class="text-muted">({{ $c->estado }})</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    @endif
                                </td>
                                <td class="py-3 pr-6 text-right">
                                    <a href="{{ route('recepcion.cita.nueva', ['documento' => $p->documento]) }}" class="text-xs font-semibold text-olive-600 hover:text-olive-800">Agendar cita</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
