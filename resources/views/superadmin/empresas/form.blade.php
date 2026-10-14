@extends('layouts.superadmin')

@php
    // Óptica nueva que todavía no se aprueba: se puede corregir el NIT y la verificación se confirma al aprobar
    $pendiente = $empresa->estaPendiente();
@endphp

@section('title', 'Editar · ' . $empresa->nombre)
@section('breadcrumb')
    <a href="{{ route('superadmin.empresas.index') }}" class="hover:underline">Ópticas</a> / {{ $empresa->nombre }}
@endsection
@section('errores_en_formulario', '1')

@section('content')

    @php
        $campo = 'w-full bg-creamdark/40 border border-olive-100 rounded-xl py-2.5 px-3.5 text-sm focus:outline-none focus:ring-2 focus:ring-olive-300';
        $volver = $pendiente ? route('superadmin.dashboard') : route('superadmin.empresas.show', $empresa->nit);
    @endphp

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ $volver }}" class="p-2 rounded-lg hover:bg-olive-100 text-ink/70">
            @include('partials.icon', ['name' => 'x', 'class' => 'w-5 h-5'])
        </a>
        <div>
            <h1 class="font-serif text-2xl">Editar datos de la óptica</h1>
            <p class="text-sm text-muted">
                {{ $empresa->nombre . ' · NIT ' . $empresa->nit }}
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('superadmin.empresas.update', $empresa->nit) }}"
          enctype="multipart/form-data" x-data="{ confirmar: false }" class="max-w-3xl space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-2xl border border-olive-100 shadow-card p-6 sm:p-8">
            <h2 class="font-serif text-lg mb-5">Datos de la óptica</h2>

            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium mb-1.5" for="f_nit">NIT <span class="text-red-500">*</span></label>
                    <input id="f_nit" type="text" name="nit" value="{{ old('nit', $empresa->nit) }}" class="{{ $campo }}" required>
                    <p class="text-[11px] text-muted mt-1">
                        @if ($pendiente)
                            Corrígelo si la óptica lo digitó mal antes de aprobarla.
                        @else
                            Si lo cambias, se actualiza en todos los datos de la óptica (usuarios, citas, licencias...).
                        @endif
                    </p>
                    @error('nit') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5" for="f_nombre">Nombre <span class="text-red-500">*</span></label>
                    <input id="f_nombre" type="text" name="nombre" value="{{ old('nombre', $empresa->nombre) }}" class="{{ $campo }}" required>
                    @error('nombre') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5" for="f_email">Correo <span class="text-red-500">*</span></label>
                    <input id="f_email" type="email" name="email" value="{{ old('email', $empresa->email) }}" class="{{ $campo }}" required>
                    <p class="text-[11px] text-muted mt-1">Aquí llegan los avisos de pago.</p>
                    @error('email') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5" for="f_telefono">Teléfono</label>
                    <input id="f_telefono" type="text" name="telefono" value="{{ old('telefono', $empresa->telefono) }}" class="{{ $campo }}">
                    @error('telefono') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5" for="f_ciudad">Ciudad</label>
                    <input id="f_ciudad" type="text" name="ciudad" value="{{ old('ciudad', $empresa->ciudad) }}" class="{{ $campo }}">
                    @error('ciudad') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5" for="f_direccion">Dirección</label>
                    <input id="f_direccion" type="text" name="direccion" value="{{ old('direccion', $empresa->direccion) }}" class="{{ $campo }}">
                    @error('direccion') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1.5" for="f_slug">Dirección de su página</label>
                    <div class="flex items-center">
                        <span class="bg-creamdark/70 border border-r-0 border-olive-100 rounded-l-xl py-2.5 px-3.5 text-sm text-muted whitespace-nowrap">{{ url('/optica') }}/</span>
                        <input id="f_slug" type="text" name="slug" value="{{ old('slug', $empresa->slug) }}" placeholder="se genera con el nombre"
                               class="w-full bg-creamdark/40 border border-olive-100 rounded-r-xl py-2.5 px-3.5 text-sm focus:outline-none focus:ring-2 focus:ring-olive-300">
                    </div>
                    <p class="text-[11px] text-muted mt-1">Solo minúsculas, números y guiones. Si lo dejas vacío se arma con el nombre.</p>
                    @error('slug') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Verificación del NIT: PDF de la Cámara de Comercio --}}
        <div class="bg-white rounded-2xl border border-olive-100 shadow-card p-6 sm:p-8">
            <h2 class="font-serif text-lg mb-1">Verificación con la Cámara de Comercio</h2>
            <p class="text-sm text-muted mb-5">
                @if ($pendiente)
                    El certificado ya lo adjuntó la óptica al registrarse. Solo cámbialo si hace falta; la verificación del NIT se confirma
                    después, al aprobar el registro.
                @else
                    Adjunta el certificado de existencia y representación legal. Al guardar tendrás que confirmar que
                    el NIT {{ $empresa->nit }} existe en la Cámara de Comercio.
                @endif
            </p>
            <label class="block text-sm font-medium mb-1.5" for="f_camara">Certificado (PDF, máx. 5 MB) @if (! $pendiente || ! $empresa->tieneCamara())<span class="text-red-500">*</span>@endif</label>
            <input id="f_camara" type="file" name="camara_comercio" accept="application/pdf,.pdf" @required(! $pendiente || ! $empresa->tieneCamara())
                   class="block w-full text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-olive-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-olive-700 hover:file:bg-olive-200">
            @error('camara_comercio') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
            @error('confirmo') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
            @if ($empresa->tieneCamara())
                <p class="text-[11px] text-muted mt-2">Ya hay un certificado cargado; el nuevo lo reemplaza.</p>
            @endif
        </div>


        <div class="flex items-center gap-3">
            {{-- No envía el formulario directo: primero abre la ventana de confirmación --}}
            @if ($pendiente)
                <button type="submit"
                        class="bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-5 py-2.5 rounded-xl shadow-card">
                    Guardar cambios
                </button>
            @else
            <button type="button" @click="if ($el.form.reportValidity()) confirmar = true"
                    class="bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-5 py-2.5 rounded-xl shadow-card">
                Guardar cambios
            </button>
            @endif
            <a href="{{ $volver }}" class="text-sm text-muted hover:text-ink px-4 py-2.5">Cancelar</a>
        </div>

        {{-- Ventana emergente: confirmar la verificación del NIT (no aplica a una óptica pendiente) --}}
        @unless ($pendiente)
        <div x-show="confirmar" x-cloak x-transition.opacity @keydown.escape.window="confirmar = false"
             class="fixed inset-0 z-50 flex items-center justify-center bg-ink/60 p-4" role="dialog" aria-modal="true">
            <div @click.outside="confirmar = false" class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6">
                <h3 class="font-serif text-lg mb-1">Confirmar verificación del NIT</h3>
                <p class="text-sm text-muted mb-4">
                    Antes de guardar los cambios, compara el PDF que adjuntaste con la Cámara de Comercio
                    (o el RUES) y confirma que el NIT existe.
                </p>
                <label class="flex items-start gap-2 text-sm mb-5">
                    <input type="checkbox" name="confirmo" value="1" required class="mt-1 rounded border-olive-300">
                    <span>Verifiqué con la Cámara de Comercio que el NIT
                        <strong>{{ $empresa->nit }}</strong>
                        corresponde a una empresa que existe.</span>
                </label>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="confirmar = false" class="text-sm text-muted hover:text-ink px-4 py-2">Volver</button>
                    <button type="submit" class="bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-5 py-2 rounded-lg">
                        Confirmar y guardar
                    </button>
                </div>
            </div>
        </div>
        @endunless
    </form>

@endsection
