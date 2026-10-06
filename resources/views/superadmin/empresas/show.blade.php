@extends('layouts.superadmin')

@section('title', $empresa->nombre)
@section('breadcrumb')
    <a href="{{ route('superadmin.empresas.index') }}" class="hover:underline">Ópticas</a> / {{ $empresa->nombre }}
@endsection

@section('content')

    @php
        $dinero = fn ($v) => '$' . number_format((float) $v, 0, ',', '.');
        $diasLicencia = $resumen && $resumen->dias_restantes !== null ? (int) $resumen->dias_restantes : null;
        $campo = 'w-full bg-creamdark/40 border border-olive-100 rounded-xl py-2.5 px-3.5 text-sm focus:outline-none focus:ring-2 focus:ring-olive-300';
    @endphp

    {{-- Encabezado --}}
    <div class="flex items-start justify-between flex-wrap gap-4 mb-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('superadmin.empresas.index') }}" class="p-2 rounded-lg hover:bg-olive-100 text-ink/70">
                @include('partials.icon', ['name' => 'x', 'class' => 'w-5 h-5'])
            </a>
            <div>
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="font-serif text-2xl">{{ $empresa->nombre }}</h1>
                    @include('partials.estado-licencia', ['estado' => $resumen->estado_licencia ?? 'sin_licencia', 'solo' => 'estado'])
                </div>
                <p class="text-sm text-muted">NIT {{ $empresa->nit }} · Registrada el {{ $empresa->fecha_registro?->format('d/m/Y') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ $empresa->urlPagina() }}" target="_blank"
               class="inline-flex items-center gap-2 text-sm font-medium px-4 py-2.5 rounded-xl bg-white border border-olive-100 hover:bg-olive-50 transition">
                @include('partials.icon', ['name' => 'eye', 'class' => 'w-4 h-4'])
                Ver su página
            </a>
            <a href="{{ route('superadmin.empresas.edit', $empresa->nit) }}"
               class="inline-flex items-center gap-2 text-sm font-medium px-4 py-2.5 rounded-xl bg-white border border-olive-100 hover:bg-olive-50 transition">
                @include('partials.icon', ['name' => 'pencil', 'class' => 'w-4 h-4'])
                Editar datos
            </a>
            @unless ($empresa->estaPendiente())
                @include('superadmin.partials.boton-estado', ['nit' => $empresa->nit, 'nombre' => $empresa->nombre, 'suspendida' => $empresa->estaSuspendida()])
            @endunless
        </div>
    </div>

    @if ($empresa->estaPendiente())
        <div class="mb-6 rounded-xl border border-sky-200 bg-sky-50 text-sky-800 px-4 py-3 text-sm flex flex-wrap items-center justify-between gap-3">
            <span>Esta óptica se registró desde la página de planes y espera que apruebes su pago. Su administrador todavía no puede entrar.</span>
            <a href="{{ route('superadmin.licencias.index') }}" class="font-semibold underline underline-offset-2">Ir a pagos por aprobar</a>
        </div>
    @endif

    @if ($empresa->estaSuspendida())
        <div class="mb-6 rounded-xl border border-stone-300 bg-stone-100 text-stone-700 px-4 py-3 text-sm">
            Esta óptica está suspendida: su equipo no puede entrar al panel y su página pública no se muestra, aunque tenga días de licencia.
        </div>
    @endif

    {{-- Movimiento --}}
    @php
        $mov = [
            ['icon' => 'calendar-check', 'valor' => $movimiento['citas_mes'], 'label' => 'Citas este mes', 'hint' => $movimiento['citas_total'] . ' en total'],
            ['icon' => 'cart', 'valor' => $dinero($movimiento['ventas_mes']), 'label' => 'Ventas este mes', 'hint' => $dinero($movimiento['ventas_total']) . ' en total'],
            ['icon' => 'clipboard', 'valor' => $movimiento['historias'], 'label' => 'Historias clínicas', 'hint' => 'Registradas'],
            ['icon' => 'cube', 'valor' => $movimiento['productos'], 'label' => 'Productos', 'hint' => 'En su catálogo'],
        ];
    @endphp
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        @foreach ($mov as $m)
            <div class="bg-white rounded-2xl border border-olive-100 shadow-card p-5">
                <div class="w-10 h-10 rounded-xl bg-olive-100 text-olive-700 flex items-center justify-center mb-4">
                    @include('partials.icon', ['name' => $m['icon'], 'class' => 'w-5 h-5'])
                </div>
                <p class="font-serif text-2xl leading-none tabular-nums">{{ $m['valor'] }}</p>
                <p class="text-sm font-medium text-ink/80 mt-2">{{ $m['label'] }}</p>
                <p class="text-xs text-muted mt-0.5">{{ $m['hint'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid xl:grid-cols-3 gap-6">

        <div class="xl:col-span-2 space-y-6">

            {{-- Licencia actual + asignar plan --}}
            <div class="bg-white rounded-2xl border border-olive-100 shadow-card p-5 sm:p-6">
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-serif text-lg mb-4">Licencia actual</h3>
                        @if ($resumen && $resumen->id_licencia)
                            <p class="font-serif text-3xl">{{ $resumen->plan }}</p>
                            <p class="text-sm text-muted mt-1">
                                Del {{ \Illuminate\Support\Carbon::parse($resumen->fecha_inicio)->format('d/m/Y') }}
                                al {{ \Illuminate\Support\Carbon::parse($resumen->fecha_fin)->format('d/m/Y') }}
                            </p>
                            <div class="mt-4">
                                @include('partials.estado-licencia', ['dias' => $diasLicencia, 'solo' => 'dias'])
                            </div>
                            <p class="text-xs text-muted mt-3">
                                {{ $empresa->prueba_usada ? 'Ya usó el mes de prueba gratis.' : 'Todavía no ha usado el mes de prueba gratis.' }}
                            </p>
                        @else
                            <p class="text-sm text-muted">Esta óptica no tiene ninguna licencia activa. No puede entrar a su panel.</p>
                        @endif
                    </div>

                    @unless ($empresa->estaPendiente())
                    <div class="md:border-l md:border-olive-100 md:pl-6">
                        <h3 class="font-serif text-lg mb-1">Asignar o renovar plan</h3>
                        <p class="text-xs text-muted mb-4">
                            Úsalo cuando el pago llegó por fuera del sistema. Si todavía le quedan días, el plan nuevo empieza cuando termine el actual.
                        </p>
                        <form method="POST" action="{{ route('superadmin.empresas.licencia', $empresa->nit) }}" class="space-y-3">
                            @csrf
                            <select name="id_plan" class="{{ $campo }}" required>
                                @foreach ($planes as $plan)
                                    <option value="{{ $plan->id_plan }}" @disabled($plan->es_prueba && $empresa->prueba_usada)>
                                        {{ $plan->nombre }} — {{ $plan->precioFormateado() }}
                                        {{ $plan->es_prueba && $empresa->prueba_usada ? '(ya usada)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="grid grid-cols-2 gap-3">
                                <input type="text" name="referencia_pago" placeholder="Referencia" class="{{ $campo }}">
                                <input type="number" name="valor" min="0" step="1" placeholder="Valor" class="{{ $campo }}">
                            </div>
                            <button type="submit" class="w-full bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-5 py-2.5 rounded-xl shadow-card">
                                Asignar plan
                            </button>
                        </form>
                    </div>
                    @endunless
                </div>
            </div>

            {{-- Historial de licencias --}}
            <div class="bg-white rounded-2xl border border-olive-100 shadow-card overflow-hidden">
                <div class="px-5 sm:px-6 pt-5 pb-4 border-b border-olive-100">
                    <h3 class="font-serif text-lg">Historial de licencias</h3>
                    <p class="text-xs text-muted">Pruebas, compras y renovaciones de esta óptica</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-cream text-left text-[11px] uppercase tracking-wider text-muted">
                                <th class="py-3 pl-6 pr-4 font-semibold">Plan</th>
                                <th class="py-3 pr-4 font-semibold">Periodo</th>
                                <th class="py-3 pr-4 font-semibold">Valor</th>
                                <th class="py-3 pr-4 font-semibold">Referencia</th>
                                <th class="py-3 pr-4 font-semibold">Solicitada</th>
                                <th class="py-3 pr-6 font-semibold">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-olive-100">
                            @forelse ($licencias as $lic)
                                <tr>
                                    <td class="py-3 pl-6 pr-4 font-medium whitespace-nowrap">{{ $lic->plan->nombre ?? '—' }}</td>
                                    <td class="py-3 pr-4 whitespace-nowrap text-ink/70 tabular-nums">
                                        @if ($lic->fecha_inicio)
                                            {{ $lic->fecha_inicio->format('d/m/Y') }} – {{ $lic->fecha_fin?->format('d/m/Y') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="py-3 pr-4 whitespace-nowrap tabular-nums">{{ $dinero($lic->valor) }}</td>
                                    <td class="py-3 pr-4 text-ink/70">{{ $lic->referencia_pago ?? '—' }}</td>
                                    <td class="py-3 pr-4 whitespace-nowrap text-ink/70">{{ $lic->fecha_solicitud?->format('d/m/Y') }}</td>
                                    <td class="py-3 pr-6">
                                        @include('partials.estado-licencia', ['estado' => $lic->estado, 'solo' => 'estado'])
                                        @if ($lic->estado === 'pendiente' && ! $empresa->estaPendiente())
                                            <form method="POST" action="{{ route('superadmin.licencias.aprobar', $lic->id_licencia) }}" class="inline ml-2">
                                                @csrf
                                                <button class="text-[11px] font-semibold text-olive-700 hover:underline">Aprobar</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-6 py-8 text-center text-muted">Sin licencias registradas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Columna derecha --}}
        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-olive-100 shadow-card p-5 sm:p-6">
                <h3 class="font-serif text-lg mb-4">Datos de la óptica</h3>
                <dl class="space-y-3 text-sm">
                    @foreach ([
                        'Correo' => $empresa->email,
                        'Teléfono' => $empresa->telefono,
                        'Ciudad' => $empresa->ciudad,
                        'Dirección' => $empresa->direccion,
                    ] as $label => $valor)
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted">{{ $label }}</dt>
                            <dd class="text-right font-medium break-all">{{ $valor ?: '—' }}</dd>
                        </div>
                    @endforeach
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">Página</dt>
                        <dd class="text-right font-medium">
                            <a href="{{ $empresa->urlPagina() }}" target="_blank" class="text-olive-700 hover:underline break-all">
                                {{ $empresa->slug ? '/optica/' . $empresa->slug : '—' }}
                            </a>
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white rounded-2xl border border-olive-100 shadow-card p-5 sm:p-6">
                <h3 class="font-serif text-lg mb-4">Usuarios</h3>
                @forelse ($usuariosPorRol as $r)
                    <div class="flex justify-between text-sm py-1.5">
                        <span class="text-ink/80">{{ $r->nombre_rol }}</span>
                        <span class="font-semibold tabular-nums">{{ $r->total }}</span>
                    </div>
                @empty
                    <p class="text-sm text-muted">Sin usuarios asociados.</p>
                @endforelse

                @if ($admins->isNotEmpty())
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-muted mt-5 mb-2">Administradores</p>
                    @foreach ($admins as $admin)
                        <div class="text-sm py-1.5">
                            <p class="font-medium">{{ trim($admin->nombres . ' ' . $admin->apellido) }}</p>
                            <p class="text-xs text-muted">
                                {{ $admin->email }} ·
                                {{ $admin->ultimo_acceso ? 'último ingreso ' . \Illuminate\Support\Carbon::parse($admin->ultimo_acceso)->format('d/m/Y') : 'sin ingresos registrados' }}
                            </p>
                        </div>
                    @endforeach
                @endif
            </div>

            <div class="bg-white rounded-2xl border border-olive-100 shadow-card overflow-hidden">
                <div class="px-5 pt-5 pb-4 border-b border-olive-100">
                    <h3 class="font-serif text-lg">Actividad</h3>
                </div>
                <ul class="divide-y divide-olive-100">
                    @forelse ($actividad as $a)
                        <li class="px-5 py-3">
                            <p class="text-sm">{{ $a->descripcion }}</p>
                            <p class="text-[11px] text-muted mt-0.5">
                                {{ $a->usuario->nombres ?? 'Sistema' }} · {{ $a->fecha?->format('d/m/Y H:i') }}
                            </p>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-sm text-muted">Sin actividad registrada.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

@endsection
