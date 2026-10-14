@extends('layouts.admin')

@section('title', 'Equipo')
@section('breadcrumb', 'Equipo')

@section('content')

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="font-serif text-2xl">Equipo</h1>
            <p class="text-sm text-muted">Los empleados de tu óptica. Al crearlos, su usuario y una contraseña temporal les llegan al correo.</p>
        </div>
        <a href="{{ route('admin.equipo.create') }}"
           class="inline-flex items-center justify-center gap-2 bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-4 py-2.5 rounded-xl shadow-card">
            @include('partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4'])
            Nuevo empleado
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-olive-100 shadow-card overflow-hidden">
        @if ($empleados->isEmpty())
            <div class="px-6 py-14 text-center">
                <p class="font-serif text-lg">Todavía no has creado empleados</p>
                <p class="text-sm text-muted mt-1">Crea vendedores, recepcionistas o auditores para tu óptica.</p>
            </div>
        @else
            <div class="divide-y divide-olive-100">
                @foreach ($empleados as $e)
                    @php
                        $inactivo = in_array((int) $e->id_estado, [2, 4], true);
                        $temporal = (int) $e->id_estado === 3;
                    @endphp
                    <div class="p-4 sm:px-6 flex flex-col lg:flex-row lg:items-center gap-3 lg:gap-6">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium break-words">{{ $e->nombres }} {{ $e->apellido }}</p>
                            <p class="text-xs text-muted break-all">{{ $e->email }} · doc. {{ $e->documento }} · {{ $e->telefono }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <span class="font-semibold rounded-full px-2.5 py-1 bg-olive-50 text-olive-700">{{ $e->rol->nombre_rol ?? '—' }}</span>
                            @if ($inactivo)
                                <span class="font-semibold rounded-full px-2.5 py-1 bg-rose-50 text-rose-700">Inactivo</span>
                            @elseif ($temporal)
                                <span class="font-semibold rounded-full px-2.5 py-1 bg-amber-50 text-amber-700">Contraseña temporal sin cambiar</span>
                            @else
                                <span class="font-semibold rounded-full px-2.5 py-1 bg-green-50 text-green-700">Activo</span>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                            <a href="{{ route('admin.equipo.edit', $e->documento) }}" class="font-medium text-olive-700 hover:underline">Editar</a>
                            <form method="POST" action="{{ route('admin.equipo.credenciales', $e->documento) }}"
                                  onsubmit="return confirm('Se generará una contraseña temporal nueva y se enviará a {{ $e->email }}. ¿Continuar?')">
                                @csrf
                                <button type="submit" class="font-medium text-olive-700 hover:underline">Reenviar credenciales</button>
                            </form>
                            <form method="POST" action="{{ route('admin.equipo.estado', $e->documento) }}">
                                @csrf
                                <button type="submit" class="font-medium {{ $inactivo ? 'text-green-700' : 'text-rose-600' }} hover:underline">
                                    {{ $inactivo ? 'Activar' : 'Desactivar' }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

@endsection
