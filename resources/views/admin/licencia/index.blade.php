@extends('layouts.admin')

@section('title', 'Mi licencia')
@section('breadcrumb', 'Mi licencia')

@section('content')

    @php $dinero = fn ($v) => '$' . number_format((float) $v, 0, ',', '.'); @endphp

    <div class="mb-6">
        <h1 class="font-serif text-2xl">Mi licencia</h1>
        <p class="text-sm text-muted">Tu plan de VisiOptica, los días que te quedan y la renovación.</p>
    </div>

    @if (! $empresa)
        <div class="bg-white rounded-2xl border border-rose-200 shadow-card p-8 max-w-2xl">
            <p class="font-serif text-lg">Tu usuario no está asociado a ninguna óptica</p>
            <p class="text-sm text-muted mt-2">Pide al superadministrador de VisiOptica que asigne tu usuario a tu óptica para poder entrar al panel.</p>
        </div>
    @else

        {{-- Bloqueo --}}
        @unless ($puedeIngresar)
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 text-rose-800 px-5 py-4 flex items-start gap-3">
                @include('partials.icon', ['name' => 'lock', 'class' => 'w-5 h-5 shrink-0 mt-0.5'])
                <div class="text-sm">
                    @if ($empresa->estaSuspendida())
                        <p class="font-semibold">Tu cuenta está suspendida.</p>
                        <p class="mt-0.5">Comunícate con VisiOptica para reactivarla. Mientras tanto no puedes entrar al panel.</p>
                    @else
                        <p class="font-semibold">Tu licencia venció.</p>
                        <p class="mt-0.5">Elige un plan para seguir usando el panel. Tus datos se conservan.</p>
                    @endif
                </div>
            </div>
        @endunless

        {{-- Plan actual --}}
        <div class="relative rounded-3xl overflow-hidden mb-6 bg-ink text-cream">
            <div class="absolute inset-0 opacity-25 bg-[radial-gradient(circle_at_80%_20%,#93762E,transparent_55%)]"></div>
            <div class="relative px-6 sm:px-10 py-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.2em] text-olive-300">Plan actual</p>
                    @if ($vigente)
                        <h2 class="font-serif text-3xl sm:text-4xl mt-2">{{ $vigente->plan->nombre }}</h2>
                        <p class="mt-2 text-sm text-cream/70">
                            Desde el {{ $vigente->fecha_inicio->format('d/m/Y') }}
                            @if ($coberturaHasta)
                                · cubierto hasta el {{ $coberturaHasta->format('d/m/Y') }}
                            @endif
                        </p>
                    @else
                        <h2 class="font-serif text-3xl sm:text-4xl mt-2">Sin plan vigente</h2>
                        <p class="mt-2 text-sm text-cream/70">Elige un plan abajo para reactivar tu panel.</p>
                    @endif
                </div>
                @if ($diasRestantes !== null)
                    <div class="text-left md:text-right">
                        <p class="font-serif text-5xl tabular-nums leading-none">{{ $diasRestantes }}</p>
                        <p class="text-sm text-cream/70 mt-1">{{ $diasRestantes === 1 ? 'día restante' : 'días restantes' }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Solicitud pendiente --}}
        @if ($pendiente)
            <div class="mb-6 bg-white rounded-2xl border border-sky-200 shadow-card p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center gap-4 justify-between">
                <div class="text-sm">
                    <p class="font-semibold">Solicitaste el plan {{ $pendiente->plan->nombre }} por {{ $dinero($pendiente->valor) }}.</p>
                    <p class="text-muted mt-1">{{ config('visioptica.instrucciones_pago') }}</p>
                </div>
                <form method="POST" action="{{ route('admin.licencia.cancelar', $pendiente->id_licencia) }}"
                      onsubmit="return confirm('¿Cancelar la solicitud?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm font-medium text-rose-600 hover:text-rose-800 whitespace-nowrap">Cancelar solicitud</button>
                </form>
            </div>
        @endif

        {{-- Planes --}}
        @unless ($empresa->estaSuspendida())
            <h3 class="font-serif text-lg mb-1">{{ $vigente ? 'Renovar' : 'Elegir un plan' }}</h3>
            <p class="text-sm text-muted mb-4">
                @if ($vigente)
                    Si renuevas antes de que venza, el plan nuevo empieza cuando termine el actual; no pierdes días.
                @else
                    El plan empieza el día que confirmemos tu pago.
                @endif
            </p>

            @error('id_plan')
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm">{{ $message }}</div>
            @enderror

            <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
                @foreach ($planes as $plan)
                    @php $bloqueado = ($plan->es_prueba && $empresa->prueba_usada) || $pendiente; @endphp
                    <div class="bg-white rounded-2xl border shadow-card p-5 flex flex-col {{ $plan->destacado ? 'border-olive-300 ring-1 ring-olive-300' : 'border-olive-100' }} {{ $plan->es_prueba && $empresa->prueba_usada ? 'opacity-60' : '' }}">
                        <div class="flex items-center justify-between">
                            <p class="font-medium">{{ $plan->nombre }}</p>
                            @if ($plan->destacado)
                                <span class="text-[10px] font-semibold uppercase text-olive-700 bg-olive-50 rounded px-1.5 py-0.5">Más popular</span>
                            @endif
                        </div>
                        <p class="font-serif text-3xl mt-3">{{ $plan->precioFormateado() }}</p>
                        <p class="text-xs text-muted mt-1">
                            {{ $plan->meses }} {{ $plan->meses === 1 ? 'mes' : 'meses' }}
                            @if ($plan->precio > 0 && $plan->meses > 1)
                                · {{ $dinero($plan->precio / $plan->meses) }} al mes
                            @endif
                        </p>

                        <form method="POST" action="{{ route('admin.licencia.solicitar') }}" class="mt-auto pt-5">
                            @csrf
                            <input type="hidden" name="id_plan" value="{{ $plan->id_plan }}">
                            <button type="submit" @disabled($bloqueado)
                                    class="w-full text-sm font-medium px-4 py-2.5 rounded-xl transition
                                           {{ $bloqueado ? 'bg-creamdark text-muted cursor-not-allowed' : 'bg-olive-600 hover:bg-olive-700 text-cream' }}">
                                @if ($plan->es_prueba && $empresa->prueba_usada)
                                    Ya usaste la prueba
                                @elseif ($pendiente)
                                    Tienes una solicitud en curso
                                @else
                                    Solicitar este plan
                                @endif
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endunless

        {{-- Historial --}}
        <div class="bg-white rounded-2xl border border-olive-100 shadow-card overflow-hidden">
            <div class="px-5 sm:px-6 pt-5 pb-4 border-b border-olive-100">
                <h3 class="font-serif text-lg">Historial</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-cream text-left text-[11px] uppercase tracking-wider text-muted">
                            <th class="py-3 pl-6 pr-4 font-semibold">Plan</th>
                            <th class="py-3 pr-4 font-semibold">Periodo</th>
                            <th class="py-3 pr-4 font-semibold">Valor</th>
                            <th class="py-3 pr-6 font-semibold">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-olive-100">
                        @forelse ($historial as $lic)
                            <tr>
                                <td class="py-3 pl-6 pr-4 font-medium">{{ $lic->plan->nombre ?? '—' }}</td>
                                <td class="py-3 pr-4 text-ink/70 tabular-nums whitespace-nowrap">
                                    {{ $lic->fecha_inicio ? $lic->fecha_inicio->format('d/m/Y') . ' – ' . $lic->fecha_fin?->format('d/m/Y') : 'Pedido el ' . $lic->fecha_solicitud?->format('d/m/Y') }}
                                </td>
                                <td class="py-3 pr-4 tabular-nums">{{ $dinero($lic->valor) }}</td>
                                <td class="py-3 pr-6">@include('partials.estado-licencia', ['estado' => $lic->estado, 'solo' => 'estado'])</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-8 text-center text-muted">Todavía no tienes licencias.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

@endsection
