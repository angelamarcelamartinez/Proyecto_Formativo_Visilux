@extends('layouts.staff')

@section('title', 'Registrar paciente')
@section('breadcrumb')
    <a href="{{ route('recepcion.pacientes') }}" class="hover:underline">Pacientes</a> / Registrar
@endsection

@section('content')
    @php $campo = 'w-full bg-creamdark/40 border border-olive-100 rounded-xl py-2.5 px-3.5 text-sm focus:outline-none focus:ring-2 focus:ring-olive-300'; @endphp

    <div class="mb-6">
        <h1 class="font-serif text-2xl">Registrar paciente</h1>
        <p class="text-sm text-muted">Para quien llega a la óptica sin cuenta. Luego podrás agendarle su cita. Para entrar al sitio, el paciente crea su contraseña con «¿Olvidaste tu contraseña?».</p>
    </div>

    @if ($errors->any())
        <div class="mb-5 max-w-3xl rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm">
            <ul class="list-disc pl-5">@foreach ($errors->all() as $m) <li>{{ $m }}</li> @endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('recepcion.paciente.guardar') }}" class="max-w-3xl space-y-6">
        @csrf
        <div class="bg-white rounded-2xl border border-olive-100 shadow-card p-6 sm:p-8">
            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium mb-1.5" for="p_tipo">Tipo de documento <span class="text-red-500">*</span></label>
                    <select id="p_tipo" name="id_tipo_docu" class="{{ $campo }}" required>
                        @foreach ($tiposDoc as $t)
                            <option value="{{ $t->id_tipo_docu }}" @selected(old('id_tipo_docu', 1) == $t->id_tipo_docu)>{{ $t->nom_ti_docu }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5" for="p_doc">Documento <span class="text-red-500">*</span></label>
                    <input id="p_doc" type="text" name="documento" value="{{ old('documento') }}" class="{{ $campo }}" data-doc-tipo="#p_tipo" autocomplete="off" required>
                    <p class="text-xs text-ink/60 mt-1.5" data-doc-ayuda></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5" for="p_nom">Nombres <span class="text-red-500">*</span></label>
                    <input id="p_nom" name="nombres" value="{{ old('nombres') }}" class="{{ $campo }}" required>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5" for="p_ape">Apellidos <span class="text-red-500">*</span></label>
                    <input id="p_ape" name="apellido" value="{{ old('apellido') }}" class="{{ $campo }}" required>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5" for="p_tel">Celular <span class="text-red-500">*</span></label>
                    <input id="p_tel" name="telefono" value="{{ old('telefono') }}" class="{{ $campo }}" required>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5" for="p_mail">Correo <span class="text-red-500">*</span></label>
                    <input id="p_mail" type="email" name="email" value="{{ old('email') }}" class="{{ $campo }}" required>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button type="submit" class="bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-5 py-2.5 rounded-xl shadow-card">Registrar y agendar cita</button>
            <a href="{{ route('recepcion.pacientes') }}" class="text-sm text-ink/60 hover:text-ink">Cancelar</a>
        </div>
    </form>
    @include('partials.documento-js')
@endsection
