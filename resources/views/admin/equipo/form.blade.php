@extends('layouts.admin')

@section('title', $empleado ? 'Editar empleado' : 'Nuevo empleado')
@section('breadcrumb')
    <a href="{{ route('admin.equipo.index') }}" class="hover:underline">Equipo</a> / {{ $empleado ? 'Editar' : 'Nuevo' }}
@endsection

@section('content')

    @php $campo = 'w-full bg-creamdark/40 border border-olive-100 rounded-xl py-2.5 px-3.5 text-sm focus:outline-none focus:ring-2 focus:ring-olive-300'; @endphp

    <div class="mb-6">
        <h1 class="font-serif text-2xl">{{ $empleado ? 'Editar empleado' : 'Nuevo empleado' }}</h1>
        <p class="text-sm text-muted">
            @unless ($empleado)
                Le enviaremos al correo su usuario y una contraseña temporal; al entrar por primera vez deberá cambiarla.
            @else
                Documento {{ $empleado->documento }}
            @endunless
        </p>
    </div>

    @if ($errors->any())
        <div class="mb-5 max-w-3xl rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm">
            <ul class="list-disc pl-5">@foreach ($errors->all() as $m) <li>{{ $m }}</li> @endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $empleado ? route('admin.equipo.update', $empleado->documento) : route('admin.equipo.store') }}" class="max-w-3xl space-y-6">
        @csrf
        @if ($empleado) @method('PUT') @endif

        <div class="bg-white rounded-2xl border border-olive-100 shadow-card p-6 sm:p-8">
            <div class="grid sm:grid-cols-2 gap-5">
                @unless ($empleado)
                    <div>
                        <label class="block text-sm font-medium mb-1.5" for="e_tipo">Tipo de documento <span class="text-red-500">*</span></label>
                        <select id="e_tipo" name="id_tipo_docu" class="{{ $campo }}" required>
                            @foreach ($tiposDoc as $t)
                                <option value="{{ $t->id_tipo_docu }}" @selected(old('id_tipo_docu', 1) == $t->id_tipo_docu)>{{ $t->nom_ti_docu }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5" for="e_doc">Documento <span class="text-red-500">*</span></label>
                        <input id="e_doc" type="number" name="documento" value="{{ old('documento') }}" class="{{ $campo }}" required>
                    </div>
                @endunless
                <div>
                    <label class="block text-sm font-medium mb-1.5" for="e_nom">Nombres <span class="text-red-500">*</span></label>
                    <input id="e_nom" name="nombres" value="{{ old('nombres', $empleado?->nombres) }}" class="{{ $campo }}" required>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5" for="e_ape">Apellidos <span class="text-red-500">*</span></label>
                    <input id="e_ape" name="apellido" value="{{ old('apellido', $empleado?->apellido) }}" class="{{ $campo }}" required>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5" for="e_mail">Correo (será su usuario) <span class="text-red-500">*</span></label>
                    <input id="e_mail" type="email" name="email" value="{{ old('email', $empleado?->email) }}" class="{{ $campo }}" required>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5" for="e_tel">Celular <span class="text-red-500">*</span></label>
                    <input id="e_tel" name="telefono" value="{{ old('telefono', $empleado?->telefono) }}" class="{{ $campo }}" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1.5" for="e_rol">Rol <span class="text-red-500">*</span></label>
                    <select id="e_rol" name="id_rol" class="{{ $campo }}" required>
                        <option value="">Selecciona un rol…</option>
                        @foreach ($roles as $r)
                            <option value="{{ $r->id_rol }}" @selected(old('id_rol', $empleado?->id_rol) == $r->id_rol)>{{ $r->nombre_rol }}{{ $r->descripcion ? ' — ' . $r->descripcion : '' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-5 py-2.5 rounded-xl shadow-card">
                {{ $empleado ? 'Guardar cambios' : 'Crear y enviar credenciales' }}
            </button>
            <a href="{{ route('admin.equipo.index') }}" class="text-sm text-muted hover:text-ink px-4 py-2.5">Cancelar</a>
        </div>
    </form>

@endsection
