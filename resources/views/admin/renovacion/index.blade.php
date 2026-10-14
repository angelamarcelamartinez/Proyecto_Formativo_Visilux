@extends('layouts.admin')

@section('title', 'Renovar licencia')
@section('breadcrumb', 'Renovar licencia')

@section('content')

    @php
        $dinero = fn ($v) => '$' . number_format((float) $v, 0, ',', '.');
        $campo = 'w-full bg-creamdark/40 border border-olive-100 rounded-xl py-2.5 px-3.5 text-sm focus:outline-none focus:ring-2 focus:ring-olive-300';
        $elegido = old('id_plan', $planElegido ?: $planes->firstWhere('destacado', 1)?->id_plan ?? $planes->first()?->id_plan);
    @endphp

    {{-- Con la licencia vencida esta pantalla se pone ENCIMA del panel y no se puede cerrar. --}}
    @if ($bloqueada)
        <div class="fixed inset-0 z-[100] bg-ink/70 backdrop-blur-sm overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="titulo-renovacion">
            <div class="min-h-full flex items-start sm:items-center justify-center p-4 sm:p-8">
                <div class="w-full max-w-3xl bg-cream rounded-3xl shadow-2xl overflow-hidden">
    @else
        <div class="max-w-3xl">
            <div class="bg-cream rounded-3xl border border-olive-100 shadow-card overflow-hidden">
    @endif

                    <div class="bg-ink text-cream px-6 sm:px-8 py-6">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-olive-300">{{ $empresa->nombre }}</p>
                        <h1 id="titulo-renovacion" class="font-serif text-2xl sm:text-3xl mt-1">
                            {{ $bloqueada ? 'Tu licencia venció' : 'Renovar mi licencia' }}
                        </h1>
                        <p class="text-sm text-cream/70 mt-1">
                            @if ($bloqueada)
                                @if ($vencioEl) Venció el {{ \Illuminate\Support\Carbon::parse($vencioEl)->format('d/m/Y') }}. @endif
                                Renuévala para seguir usando el panel. Tus datos se conservan.
                            @else
                                Si renuevas antes de que venza, el plan nuevo empieza cuando termine el actual.
                            @endif
                        </p>
                    </div>

                    <div class="px-6 sm:px-8 py-6">
                        @if (session('success'))
                            <div class="mb-5 rounded-xl border border-green-200 bg-green-50 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
                        @endif

                        @if ($bloqueada)
                            <p class="mb-5 text-sm text-ink/80">
                                Mientras la licencia esté vencida, <strong>solo tú (administrador) puedes entrar</strong>;
                                los demás usuarios de la óptica verán que la licencia está inactiva hasta que renueves.
                            </p>
                        @endif

                        @if ($pendiente)
                            {{-- Ya pagó: espera que el superadmin acepte la renovación --}}
                            <div class="rounded-2xl border border-sky-200 bg-sky-50 text-sky-900 px-5 py-5">
                                <p class="font-semibold">Pago en revisión</p>
                                <p class="text-sm mt-1">
                                    Recibimos tu pago del plan <strong>{{ $pendiente->plan->nombre }}</strong> por <strong>{{ $dinero($pendiente->valor) }}</strong>
                                    (referencia {{ $pendiente->referencia_pago }}).
                                </p>
                                <p class="text-sm mt-2">
                                    Te enviamos un correo de confirmación. Tus credenciales son las mismas que ya tenías;
                                    en cuanto VisiOptica acepte la renovación podrás entrar, y tus usuarios también.
                                </p>
                            </div>
                        @else
                            @if ($rechazada)
                                <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3 text-sm">
                                    Tu último pago no fue aprobado. Puedes intentarlo de nuevo con otra tarjeta o comunicarte con VisiOptica.
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm">
                                    <ul class="list-disc pl-5">
                                        @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('admin.renovacion.store') }}" autocomplete="off" class="space-y-6">
                                @csrf

                                <div>
                                    <p class="text-sm font-semibold mb-2">1. Elige tu plan</p>
                                    <div class="grid sm:grid-cols-3 gap-3">
                                        @foreach ($planes as $plan)
                                            <label class="cursor-pointer">
                                                <input type="radio" name="id_plan" value="{{ $plan->id_plan }}" class="peer sr-only" @checked((int) $elegido === (int) $plan->id_plan)>
                                                <div class="h-full rounded-2xl border border-olive-100 bg-white p-4 transition peer-checked:border-olive-600 peer-checked:ring-2 peer-checked:ring-olive-300">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <p class="font-medium">{{ $plan->nombre }}</p>
                                                        @if ($plan->destacado)
                                                            <span class="text-[10px] font-semibold uppercase text-olive-700 bg-olive-50 rounded px-1.5 py-0.5">Popular</span>
                                                        @endif
                                                    </div>
                                                    <p class="font-serif text-2xl mt-2">{{ $plan->precioFormateado() }}</p>
                                                    <p class="text-xs text-muted mt-0.5">{{ $plan->meses }} {{ $plan->meses === 1 ? 'mes' : 'meses' }}</p>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <div>
                                    <p class="text-sm font-semibold mb-2">2. Datos de la tarjeta</p>
                                    <div class="grid sm:grid-cols-2 gap-4">
                                        <div class="sm:col-span-2">
                                            <label class="block text-sm mb-1" for="t_titular">Nombre en la tarjeta</label>
                                            <input id="t_titular" name="tarjeta_titular" value="{{ old('tarjeta_titular') }}" class="{{ $campo }}" required>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="block text-sm mb-1" for="t_numero">Número de la tarjeta</label>
                                            <input id="t_numero" name="tarjeta_numero" inputmode="numeric" maxlength="19" placeholder="0000 0000 0000 0000" class="{{ $campo }}" required
                                                   oninput="this.value=this.value.replace(/\D/g,'').slice(0,16).replace(/(.{4})/g,'$1 ').trim()">
                                        </div>
                                        <div>
                                            <label class="block text-sm mb-1" for="t_venc">Vencimiento (MM/AA)</label>
                                            <input id="t_venc" name="tarjeta_vencimiento" value="{{ old('tarjeta_vencimiento') }}" inputmode="numeric" maxlength="5" placeholder="MM/AA" class="{{ $campo }}" required
                                                   oninput="let v=this.value.replace(/\D/g,'').slice(0,4); this.value=v.length>2 ? v.slice(0,2)+'/'+v.slice(2) : v">
                                        </div>
                                        <div>
                                            <label class="block text-sm mb-1" for="t_cvv">CVV</label>
                                            <input id="t_cvv" name="tarjeta_cvv" type="password" inputmode="numeric" maxlength="4" class="{{ $campo }}" required>
                                        </div>
                                    </div>
                                    <p class="text-[11px] text-muted mt-2">Pago de demostración: no se cobra nada real y no guardamos el número completo ni el CVV.</p>
                                </div>

                                <div class="flex flex-wrap items-center gap-3">
                                    <button type="submit" class="bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-6 py-3 rounded-xl shadow-card">
                                        Pagar y renovar
                                    </button>
                                    @unless ($bloqueada)
                                        <a href="{{ route('admin.licencia.index') }}" class="text-sm text-muted hover:text-ink px-3 py-3">Volver a Mi licencia</a>
                                    @endunless
                                </div>
                            </form>
                        @endif

                        @if ($bloqueada)
                            <form method="POST" action="{{ route('logout') }}" class="mt-6 pt-5 border-t border-olive-100">
                                @csrf
                                <button type="submit" class="text-sm text-muted hover:text-ink underline underline-offset-2">Cerrar sesión</button>
                            </form>
                        @endif
                    </div>

    @if ($bloqueada)
                </div>
            </div>
        </div>
    @else
            </div>
        </div>
    @endif

@endsection
