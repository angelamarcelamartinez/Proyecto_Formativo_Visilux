@extends('layouts.admin')

@section('title', 'Recordatorios de control')
@section('breadcrumb', 'Citas y Agenda / Recordatorios de control')

@section('content')

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-olive-100 text-olive-700 flex items-center justify-center">
                @include('partials.icon', ['name' => 'mail', 'class' => 'w-5 h-5'])
            </div>
            <div>
                <h1 class="font-serif text-2xl">Recordatorios de control</h1>
                <p class="text-sm text-muted">Pacientes a los que se les cumple un año desde su última cita.</p>
            </div>
        </div>

        @if ($pacientes->isNotEmpty())
            <form method="POST" action="{{ route('admin.control.enviarTodos') }}"
                  onsubmit="return confirm('¿Enviar el recordatorio a todos los pacientes pendientes de esta lista?');">
                @csrf
                <input type="hidden" name="dias" value="{{ $dias }}">
                <button type="submit"
                        class="inline-flex items-center gap-2 bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-4 py-2.5 rounded-xl shadow-card">
                    @include('partials.icon', ['name' => 'mail', 'class' => 'w-4 h-4'])
                    Enviar a todos los pendientes
                </button>
            </form>
        @endif
    </div>

    {{-- Filtro de ventana de días --}}
    <form method="GET" class="flex items-center gap-3 mb-6 bg-white rounded-2xl border border-olive-100 shadow-card p-4">
        <label for="dias" class="text-sm font-medium">Próximos a cumplir el año en los próximos</label>
        <input type="number" id="dias" name="dias" value="{{ $dias }}" min="7" max="180"
               class="w-20 bg-creamdark/40 border border-olive-100 rounded-lg py-1.5 px-2.5 text-sm text-center">
        <span class="text-sm text-muted">días</span>
        <button type="submit" class="ml-auto bg-olive-100 hover:bg-olive-200 text-olive-700 text-sm font-medium px-4 py-2 rounded-lg transition">
            Aplicar
        </button>
    </form>

    <div class="bg-white rounded-2xl border border-olive-100 shadow-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-creamdark/60 text-left text-[11px] uppercase tracking-wider text-muted">
                        <th class="px-5 py-3 font-semibold">Paciente</th>
                        <th class="px-5 py-3 font-semibold">Correo</th>
                        <th class="px-5 py-3 font-semibold whitespace-nowrap">Última cita</th>
                        <th class="px-5 py-3 font-semibold whitespace-nowrap">Cumple el año el</th>
                        <th class="px-5 py-3 font-semibold">Estado</th>
                        <th class="px-5 py-3 font-semibold text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-olive-100">
                    @forelse ($pacientes as $p)
                        <tr class="hover:bg-creamdark/30 transition">
                            <td class="px-5 py-3">
                                <p class="font-medium">{{ $p->nombres }} {{ $p->apellido }}</p>
                                <p class="text-[12px] text-muted">Doc. {{ $p->documento }} &middot; {{ $p->telefono }}</p>
                            </td>
                            <td class="px-5 py-3 text-ink/80">{{ $p->correo }}</td>
                            <td class="px-5 py-3 whitespace-nowrap">{{ $p->ultima_cita->format('d/m/Y') }}</td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                {{ $p->fecha_aniversario->format('d/m/Y') }}
                                <span class="block text-[11px] text-muted">
                                    {{ $p->dias_restantes === 0 ? 'Hoy' : ($p->dias_restantes > 0 ? "en {$p->dias_restantes} días" : abs($p->dias_restantes) . ' días atrás') }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                @if ($p->recordatorio_enviado)
                                    <span class="text-[11px] px-2 py-1 rounded-full bg-green-100 text-green-700">Recordatorio enviado</span>
                                @else
                                    <span class="text-[11px] px-2 py-1 rounded-full bg-olive-100 text-olive-700">Pendiente</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <form method="POST" action="{{ route('admin.control.enviar', $p->documento) }}">
                                    @csrf
                                    <input type="hidden" name="dias" value="{{ $dias }}">
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg
                                                   {{ $p->recordatorio_enviado ? 'text-olive-700 hover:bg-olive-100' : 'bg-olive-600 text-cream hover:bg-olive-700' }}">
                                        @include('partials.icon', ['name' => 'mail', 'class' => 'w-3.5 h-3.5'])
                                        {{ $p->recordatorio_enviado ? 'Reenviar' : 'Enviar recordatorio' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-muted">
                                No hay pacientes próximos a su cita de control en este rango de días.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-[12px] text-muted mt-4">
        El correo se envía con la configuración de <code>MAIL_MAILER</code> de tu archivo <code>.env</code>.
        Si lo dejas en <code>log</code>, el mensaje no se manda de verdad: queda escrito en
        <code>storage/logs/laravel.log</code> para que puedas revisarlo mientras pruebas.
    </p>

@endsection
