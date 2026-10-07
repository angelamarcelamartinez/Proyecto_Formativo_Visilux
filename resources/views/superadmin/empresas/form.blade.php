@extends('layouts.superadmin')

@php $crear = $modo === 'crear'; @endphp

@section('title', $crear ? 'Registrar óptica' : 'Editar · ' . $empresa->nombre)
@section('breadcrumb')
    <a href="{{ route('superadmin.empresas.index') }}" class="hover:underline">Ópticas</a> / {{ $crear ? 'Registrar' : $empresa->nombre }}
@endsection
@section('errores_en_formulario', '1')

@section('content')

    @php
        $campo = 'w-full bg-creamdark/40 border border-olive-100 rounded-xl py-2.5 px-3.5 text-sm focus:outline-none focus:ring-2 focus:ring-olive-300';
        $volver = $crear ? route('superadmin.empresas.index') : route('superadmin.empresas.show', $empresa->nit);
    @endphp

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ $volver }}" class="p-2 rounded-lg hover:bg-olive-100 text-ink/70">
            @include('partials.icon', ['name' => 'x', 'class' => 'w-5 h-5'])
        </a>
        <div>
            <h1 class="font-serif text-2xl">{{ $crear ? 'Registrar óptica' : 'Editar datos de la óptica' }}</h1>
            <p class="text-sm text-muted">
                {{ $crear ? 'Se crea con su administrador y 1 mes de prueba gratis.' : $empresa->nombre . ' · NIT ' . $empresa->nit }}
            </p>
        </div>
    </div>

    <form method="POST" action="{{ $crear ? route('superadmin.empresas.store') : route('superadmin.empresas.update', $empresa->nit) }}"
          enctype="multipart/form-data" x-data="{ confirmar: false }" class="max-w-3xl space-y-6">
        @csrf
        @unless ($crear) @method('PUT') @endunless

        <div class="bg-white rounded-2xl border border-olive-100 shadow-card p-6 sm:p-8">
            <h2 class="font-serif text-lg mb-5">Datos de la óptica</h2>

            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium mb-1.5" for="f_nit">NIT <span class="text-red-500">*</span></label>
                    @if ($crear)
                        <input id="f_nit" type="text" name="nit" value="{{ old('nit') }}" placeholder="900123456-7" class="{{ $campo }}" required>
                    @else
                        <input type="text" value="{{ $empresa->nit }}" disabled class="w-full bg-creamdark/60 border border-olive-100 rounded-xl py-2.5 px-3.5 text-sm text-muted">
                        <p class="text-[11px] text-muted mt-1">El NIT identifica a la óptica y no se puede cambiar.</p>
                    @endif
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
                Adjunta el certificado de existencia y representación legal. Al guardar tendrás que confirmar que
                el NIT {{ $crear ? '' : $empresa->nit . ' ' }}existe en la Cámara de Comercio.
            </p>
            <label class="block text-sm font-medium mb-1.5" for="f_camara">Certificado (PDF, máx. 5 MB) <span class="text-red-500">*</span></label>
            <input id="f_camara" type="file" name="camara_comercio" accept="application/pdf,.pdf" required
                   class="block w-full text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-olive-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-olive-700 hover:file:bg-olive-200">
            @error('camara_comercio') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
            @error('confirmo') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
            @unless ($crear)
                @if ($empresa->tieneCamara())
                    <p class="text-[11px] text-muted mt-2">Ya hay un certificado cargado; el nuevo lo reemplaza.</p>
                @endif
            @endunless
        </div>

        @if ($crear)
            <div class="bg-white rounded-2xl border border-olive-100 shadow-card p-6 sm:p-8">
                <h2 class="font-serif text-lg mb-1">Administrador de la óptica</h2>
                <p class="text-sm text-muted mb-5">Con este usuario la óptica entra a su panel y edita su página.</p>

                <div class="grid sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium mb-1.5" for="f_admin_documento">Documento <span class="text-red-500">*</span></label>
                        <input id="f_admin_documento" type="number" name="admin_documento" value="{{ old('admin_documento') }}" class="{{ $campo }}" required>
                        @error('admin_documento') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5" for="f_admin_email">Correo para ingresar <span class="text-red-500">*</span></label>
                        <input id="f_admin_email" type="email" name="admin_email" value="{{ old('admin_email') }}" class="{{ $campo }}" required>
                        @error('admin_email') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5" for="f_admin_nombres">Nombres <span class="text-red-500">*</span></label>
                        <input id="f_admin_nombres" type="text" name="admin_nombres" value="{{ old('admin_nombres') }}" class="{{ $campo }}" required>
                        @error('admin_nombres') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5" for="f_admin_apellido">Apellidos</label>
                        <input id="f_admin_apellido" type="text" name="admin_apellido" value="{{ old('admin_apellido') }}" class="{{ $campo }}">
                        @error('admin_apellido') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5" for="f_admin_telefono">Celular <span class="text-red-500">*</span></label>
                        <input id="f_admin_telefono" type="text" name="admin_telefono" value="{{ old('admin_telefono') }}" class="{{ $campo }}" required>
                        @error('admin_telefono') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5" for="f_admin_password">Contraseña <span class="text-red-500">*</span></label>
                        <input id="f_admin_password" type="password" name="admin_password" minlength="8" class="{{ $campo }}" required>
                        <p class="text-[11px] text-muted mt-1">Mínimo 8 caracteres. Compártela con la óptica por un medio seguro.</p>
                        @error('admin_password') <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        @endif

        <div class="flex items-center gap-3">
            {{-- No envía el formulario directo: primero abre la ventana de confirmación --}}
            <button type="button" @click="if ($el.form.reportValidity()) confirmar = true"
                    class="bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-5 py-2.5 rounded-xl shadow-card">
                {{ $crear ? 'Registrar óptica' : 'Guardar cambios' }}
            </button>
            <a href="{{ $volver }}" class="text-sm text-muted hover:text-ink px-4 py-2.5">Cancelar</a>
        </div>

        {{-- Ventana emergente: confirmar la verificación del NIT --}}
        <div x-show="confirmar" x-cloak x-transition.opacity @keydown.escape.window="confirmar = false"
             class="fixed inset-0 z-50 flex items-center justify-center bg-ink/60 p-4" role="dialog" aria-modal="true">
            <div @click.outside="confirmar = false" class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6">
                <h3 class="font-serif text-lg mb-1">Confirmar verificación del NIT</h3>
                <p class="text-sm text-muted mb-4">
                    Antes de {{ $crear ? 'registrar la óptica' : 'guardar los cambios' }}, compara el PDF que adjuntaste con la Cámara de Comercio
                    (o el RUES) y confirma que el NIT existe.
                </p>
                <label class="flex items-start gap-2 text-sm mb-5">
                    <input type="checkbox" name="confirmo" value="1" required class="mt-1 rounded border-olive-300">
                    <span>Verifiqué con la Cámara de Comercio que el NIT
                        <strong x-text="'{{ $crear ? '' : $empresa->nit }}' || document.getElementById('f_nit')?.value"></strong>
                        corresponde a una empresa que existe.</span>
                </label>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="confirmar = false" class="text-sm text-muted hover:text-ink px-4 py-2">Volver</button>
                    <button type="submit" class="bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-5 py-2 rounded-lg">
                        Confirmar y {{ $crear ? 'registrar' : 'guardar' }}
                    </button>
                </div>
            </div>
        </div>
    </form>

@endsection
