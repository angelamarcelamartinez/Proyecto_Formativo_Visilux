@extends('layouts.optometra')

@section('title', 'Mi perfil')
@section('breadcrumb', 'Mi perfil')

@section('content')
    <div class="mb-6">
        <h1 class="font-serif text-2xl">Mi perfil profesional</h1>
        <p class="text-sm text-muted">Tu tarjeta profesional y especialidad salen en las fórmulas que imprimes.</p>
    </div>

    @if ($errors->any() && ! $errors->has('general'))
        <div class="mb-5 max-w-3xl rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm">
            <ul class="list-disc pl-5">@foreach ($errors->all() as $m) <li>{{ $m }}</li> @endforeach</ul>
        </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-6 max-w-5xl">
        <section class="op-card p-6">
            <div class="w-16 h-16 rounded-full bg-olive-600 text-cream flex items-center justify-center text-xl font-semibold mb-4">
                {{ \Illuminate\Support\Str::of($usuario->nombres . ' ' . $usuario->apellido)->explode(' ')->filter()->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->take(2)->implode('') }}
            </div>
            <p class="font-serif text-xl">{{ $usuario->nombres }} {{ $usuario->apellido }}</p>
            <p class="text-sm text-muted">{{ $perfil->nombre_especialidad ?? 'Optómetra' }}</p>
            <dl class="mt-5 space-y-2 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-muted">Documento</dt><dd class="font-medium">{{ $usuario->documento }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-muted">Correo</dt><dd class="font-medium truncate">{{ $usuario->email }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-muted">T.P.</dt><dd class="font-medium">{{ $perfil->tarjeta_profesional ?: 'Sin registrar' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-muted">Óptica</dt><dd class="font-medium truncate">{{ $usuario->empresa->nombre ?? '—' }}</dd></div>
            </dl>
            <p class="mt-5 text-xs text-muted">Para cambiar tu nombre o correo pídeselo al administrador (Equipo).</p>
        </section>

        <form method="POST" action="{{ route('optometra.perfil.update') }}" class="op-card p-6 lg:col-span-2 space-y-5">
            @csrf
            @method('PUT')
            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="op-label" for="p_tp">Tarjeta profesional <span class="text-red-500">*</span></label>
                    <input id="p_tp" type="number" name="tarjeta_profesional" class="op-input" required
                           value="{{ old('tarjeta_profesional', $perfil->tarjeta_profesional) }}">
                </div>
                <div>
                    <label class="op-label" for="p_tel">Celular <span class="text-red-500">*</span></label>
                    <input id="p_tel" name="telefono" class="op-input" required value="{{ old('telefono', $usuario->telefono) }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="op-label" for="p_esp">Especialidad <span class="text-red-500">*</span></label>
                    <select id="p_esp" name="id_especialidad" class="op-input" required>
                        @foreach ($especialidades as $e)
                            <option value="{{ $e->id_especialidad }}" @selected(old('id_especialidad', $perfil->id_especialidad) == $e->id_especialidad)>
                                {{ $e->nombre_especialidad }}{{ $e->descripcion ? ' — ' . $e->descripcion : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex items-center gap-3 pt-2">
                <button class="op-btn op-btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div>
@endsection
