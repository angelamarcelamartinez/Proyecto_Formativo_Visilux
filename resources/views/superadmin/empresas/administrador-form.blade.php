@extends('layouts.superadmin')

@section('title', 'Editar administrador · ' . $empresa->nombre)
@section('breadcrumb')
    <a href="{{ route('superadmin.empresas.index') }}" class="hover:underline">Ópticas</a> /
    <a href="{{ route('superadmin.empresas.admins', $empresa->nit) }}" class="hover:underline">Administradores</a> / Editar
@endsection
@section('errores_en_formulario', '1')

@section('content')

    @php
        $campo = 'w-full bg-creamdark/40 border border-olive-100 rounded-xl py-2.5 px-3.5 text-sm focus:outline-none focus:ring-2 focus:ring-olive-300';
    @endphp

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('superadmin.empresas.admins', $empresa->nit) }}" class="p-2 rounded-lg hover:bg-olive-100 text-ink/70">
            @include('partials.icon', ['name' => 'x', 'class' => 'w-5 h-5'])
        </a>
        <div>
            <h1 class="font-serif text-2xl">Editar administrador</h1>
            <p class="text-sm text-muted">{{ $empresa->nombre }} · documento {{ $admin->documento }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('superadmin.empresas.admins.update', [$empresa->nit, $admin->documento]) }}" class="max-w-3xl space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-2xl border border-olive-100 shadow-card p-6 sm:p-8">
            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium mb-1.5" for="a_nombres">Nombres <span class="text-red-500">*</span></label>
                    <input id="a_nombres" type="text" name="nombres" value="{{ old('nombres', $admin->nombres) }}" class="{{ $campo }}" required>
                    @error('nombres') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5" for="a_apellido">Apellidos</label>
                    <input id="a_apellido" type="text" name="apellido" value="{{ old('apellido', $admin->apellido) }}" class="{{ $campo }}">
                    @error('apellido') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5" for="a_email">Correo para ingresar <span class="text-red-500">*</span></label>
                    <input id="a_email" type="email" name="email" value="{{ old('email', $admin->email) }}" class="{{ $campo }}" required>
                    @error('email') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5" for="a_telefono">Celular <span class="text-red-500">*</span></label>
                    <input id="a_telefono" type="text" name="telefono" value="{{ old('telefono', $admin->telefono) }}" class="{{ $campo }}" required>
                    @error('telefono') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Documento</label>
                    <input type="text" value="{{ $admin->documento }}" disabled class="w-full bg-creamdark/60 border border-olive-100 rounded-xl py-2.5 px-3.5 text-sm text-muted">
                    <p class="text-[11px] text-muted mt-1">El documento identifica al usuario y no se puede cambiar.</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-olive-100 shadow-card p-6 sm:p-8">
            <h2 class="font-serif text-lg mb-1">Contraseña</h2>
            <p class="text-sm text-muted mb-5">Déjala vacía para no cambiarla. Si pones una nueva, compártela con el administrador por un medio seguro.</p>
            <div class="grid sm:grid-cols-2 gap-5 items-start">
                <div>
                    <label class="block text-sm font-medium mb-1.5" for="a_password">Nueva contraseña</label>
                    <input id="a_password" type="password" name="password" minlength="8" autocomplete="new-password" class="{{ $campo }}">
                    <p class="text-[11px] text-muted mt-1">Mínimo 8 caracteres.</p>
                    @error('password') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <label class="flex items-start gap-2 text-sm sm:mt-8">
                    <input type="checkbox" name="forzar_cambio" value="1" checked class="mt-1 rounded border-olive-300">
                    <span>Obligarlo a cambiarla en su próximo ingreso</span>
                </label>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-olive-100 shadow-card p-6 sm:p-8">
            <h2 class="font-serif text-lg mb-1">Estado de la cuenta</h2>
            <p class="text-sm text-muted mb-4">Un administrador inactivo no puede entrar. Tú (superadmin) activas a los administradores; ellos activan a los demás usuarios de su óptica.</p>
            @php $inactivo = in_array((int) $admin->id_estado, [2, 4], true); @endphp
            <label class="flex items-start gap-2 text-sm">
                <input type="checkbox" name="activo" value="1" @checked(old('activo', ! $inactivo ? 1 : 0)) class="mt-1 rounded border-olive-300">
                <span>Cuenta activa</span>
            </label>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-5 py-2.5 rounded-xl shadow-card">
                Guardar cambios
            </button>
            <a href="{{ route('superadmin.empresas.admins', $empresa->nit) }}" class="text-sm text-muted hover:text-ink px-4 py-2.5">Cancelar</a>
        </div>
    </form>

@endsection
