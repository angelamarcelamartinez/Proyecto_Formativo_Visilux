@extends('layouts.superadmin')

@section('title', 'Administradores · ' . $empresa->nombre)
@section('breadcrumb')
    <a href="{{ route('superadmin.empresas.index') }}" class="hover:underline">Ópticas</a> /
    <a href="{{ route('superadmin.empresas.show', $empresa->nit) }}" class="hover:underline">{{ $empresa->nombre }}</a> / Administradores
@endsection

@section('content')

    <div class="flex items-end justify-between flex-wrap gap-4 mb-6">
        <div>
            <h1 class="font-serif text-2xl">Administradores</h1>
            <p class="text-sm text-muted">{{ $empresa->nombre }} · NIT {{ $empresa->nit }}</p>
        </div>
        <a href="{{ route('superadmin.empresas.show', $empresa->nit) }}"
           class="text-sm font-medium text-olive-700 hover:underline">← Volver a la óptica</a>
    </div>

    <div class="bg-white rounded-2xl border border-olive-100 shadow-card overflow-hidden">
        @if ($admins->isEmpty())
            <div class="px-6 py-12 text-center text-sm text-muted">Esta óptica no tiene administradores.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-cream text-left text-[11px] uppercase tracking-wider text-muted">
                            <th class="py-3 pl-6 pr-4 font-semibold">Administrador</th>
                            <th class="py-3 pr-4 font-semibold">Documento</th>
                            <th class="py-3 pr-4 font-semibold">Correo (usuario)</th>
                            <th class="py-3 pr-4 font-semibold">Celular</th>
                            <th class="py-3 pr-4 font-semibold">Último ingreso</th>
                            <th class="py-3 pr-4 font-semibold">Contraseña</th>
                            <th class="py-3 pr-6 font-semibold text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-olive-100">
                        @foreach ($admins as $a)
                            <tr class="hover:bg-cream/70 transition">
                                <td class="py-3 pl-6 pr-4 font-medium whitespace-nowrap">{{ trim($a->nombres . ' ' . $a->apellido) }}</td>
                                <td class="py-3 pr-4 tabular-nums text-ink/80">{{ $a->documento }}</td>
                                <td class="py-3 pr-4 text-ink/80 whitespace-nowrap">{{ $a->email }}</td>
                                <td class="py-3 pr-4 text-ink/80 whitespace-nowrap">{{ $a->telefono }}</td>
                                <td class="py-3 pr-4 text-ink/70 whitespace-nowrap tabular-nums">
                                    {{ $a->ultimo_acceso ? \Illuminate\Support\Carbon::parse($a->ultimo_acceso)->format('d/m/Y H:i') : 'Nunca' }}
                                </td>
                                <td class="py-3 pr-4 whitespace-nowrap">
                                    @if ($a->debe_cambiar_password)
                                        <span class="text-[11px] font-semibold uppercase text-amber-700 bg-amber-50 rounded px-1.5 py-0.5">Debe cambiarla</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="py-3 pr-6 text-right whitespace-nowrap">
                                    <form method="POST" action="{{ route('superadmin.empresas.admins.credenciales', [$empresa->nit, $a->documento]) }}" class="inline"
                                          onsubmit="return confirm('Se generará una contraseña temporal nueva y se enviará al correo de la óptica y al del administrador. ¿Continuar?')">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center gap-1.5 text-sm font-medium text-olive-700 hover:bg-olive-100 transition px-3 py-1.5 rounded-lg">
                                            @include('partials.icon', ['name' => 'mail', 'class' => 'w-4 h-4'])
                                            Reenviar credenciales
                                        </button>
                                    </form>
                                    <a href="{{ route('superadmin.empresas.admins.edit', [$empresa->nit, $a->documento]) }}"
                                       class="inline-flex items-center gap-1.5 text-sm font-medium text-olive-700 hover:bg-olive-100 transition px-3 py-1.5 rounded-lg">
                                        @include('partials.icon', ['name' => 'pencil', 'class' => 'w-4 h-4'])
                                        Editar
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

@endsection
