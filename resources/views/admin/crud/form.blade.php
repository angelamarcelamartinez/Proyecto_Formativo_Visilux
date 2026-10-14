@extends('layouts.admin')

@section('title', ($mode === 'create' ? 'Nuevo · ' : 'Editar · ') . $config['label'])
@section('breadcrumb', $config['group_label'] . ' / ' . $config['label_plural'])

@section('content')

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.crud.index', $table) }}" class="p-2 rounded-lg hover:bg-olive-100 text-ink/70">
            @include('partials.icon', ['name' => 'x', 'class' => 'w-5 h-5'])
        </a>
        <div>
            <h1 class="font-serif text-2xl">{{ $mode === 'create' ? 'Nuevo registro' : 'Editar registro' }}</h1>
            <p class="text-sm text-muted">{{ $config['label'] }} &middot; {{ $config['group_label'] }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-olive-100 shadow-card p-6 sm:p-8 max-w-3xl">
        <form method="POST"
              enctype="multipart/form-data"
              action="{{ $mode === 'create' ? route('admin.crud.store', $table) : route('admin.crud.update', [$table, $record->{$config['primary_key']}]) }}">
            @csrf
            @if ($mode === 'edit')
                @method('PUT')
            @endif

            <div class="grid sm:grid-cols-2 gap-5">
                @foreach ($config['columns'] as $col)
                    {{-- Campos que llena el sistema o que solo se ven en la tabla --}}
                    @continue($col['auto'] || !empty($col['on_create']) || (isset($col['in_form']) && $col['in_form'] === false))

                    @php
                        $name = $col['name'];
                        $raw = old($name, $record->{$name} ?? null);
                        if ($col['type'] === 'datetime-local' && $raw && ! old($name)) {
                            $raw = str_replace(' ', 'T', substr($raw, 0, 16));
                        }
                        if ($col['type'] === 'date' && $raw) {
                            $raw = substr($raw, 0, 10);
                        }
                        if ($col['type'] === 'time' && $raw) {
                            $raw = substr($raw, 0, 5); // 08:00:00 → 08:00
                        }
                        if ($mode === 'create' && ($raw === null || $raw === '') && !empty($col['default'])) {
                            $raw = $col['type'] === 'date'
                                ? \Illuminate\Support\Carbon::parse($col['default'])->toDateString()
                                : $col['default'];
                        }
                        $lockedManualPk = $col['manual_pk'] && $mode === 'edit' && empty($col['pk_editable']);
                        $span = in_array($col['type'], ['textarea', 'file']) || !empty($col['lookup']) ? 'sm:col-span-2' : '';
                        $db = $col['db'] ?? [];
                        // Límites tomados de la base de datos (largo máximo, rango de números)
                        $limites = '';
                        if (isset($db['max_length'])) {
                            $limites .= ' maxlength="'.$db['max_length'].'"';
                        }
                        if (isset($db['max']) && in_array($col['type'], ['number', 'decimal'], true)) {
                            $limites .= ' min="'.($col['min'] ?? $db['min']).'" max="'.($col['max'] ?? $db['max']).'"';
                        }
                        $inputClass = 'w-full bg-creamdark/40 border border-olive-100 rounded-xl py-2.5 px-3.5 text-sm focus:outline-none focus:ring-2 focus:ring-olive-300';
                    @endphp

                    <div class="{{ $span }}">
                        <label class="block text-sm font-medium mb-1.5" for="f_{{ $name }}">
                            {{ $col['label'] }}
                            @if (! $col['nullable'] && $col['type'] !== 'password') <span class="text-red-500">*</span> @endif
                        </label>

                        @if ($lockedManualPk)
                            <input type="text" value="{{ $raw }}" disabled
                                   class="w-full bg-creamdark/60 border border-olive-100 rounded-xl py-2.5 px-3.5 text-sm text-muted">
                            <input type="hidden" name="{{ $name }}" value="{{ $raw }}">
                            <p class="text-[11px] text-muted mt-1">Este identificador no se puede modificar.</p>

                        @elseif (!empty($col['lookup']))
                            {{-- Se escribe el documento y se traen los datos del usuario registrado --}}
                            <div class="js-lookup rounded-xl border border-olive-100 bg-creamdark/30 p-4"
                                 data-url="{{ route('admin.buscar-usuario') }}">
                                <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
                                    <input id="f_{{ $name }}" type="text" inputmode="numeric" name="{{ $name }}" value="{{ $raw }}"
                                           placeholder="Escribe el número de documento" autocomplete="off"
                                           class="js-lookup-input {{ $inputClass }} sm:max-w-xs bg-white">
                                    <p class="js-lookup-status text-[12px] text-muted">Escribe el documento del paciente y sus datos se completan solos.</p>
                                </div>
                                <div class="grid sm:grid-cols-3 gap-3 mt-4">
                                    @foreach (['tipo_documento' => 'Tipo de documento', 'nombres' => 'Nombres', 'apellido' => 'Apellidos', 'telefono' => 'Teléfono', 'email' => 'Correo'] as $campo => $etiqueta)
                                        <div class="{{ $campo === 'email' ? 'sm:col-span-2' : '' }}">
                                            <span class="block text-[11px] uppercase tracking-wider text-muted mb-1">{{ $etiqueta }}</span>
                                            <input type="text" readonly tabindex="-1" data-field="{{ $campo }}"
                                                   class="w-full bg-white/70 border border-olive-100 rounded-lg py-2 px-3 text-sm text-ink/80 cursor-not-allowed">
                                        </div>
                                    @endforeach
                                </div>
                                <p class="text-[11px] text-muted mt-3">Estos datos vienen del registro del usuario. Para cambiarlos, edítalos en el módulo Usuarios.</p>
                            </div>

                        @elseif ($col['type'] === 'select')
                            <select id="f_{{ $name }}" name="{{ $name }}" class="{{ $inputClass }}">
                                @if ($col['nullable'])
                                    <option value="">—</option>
                                @else
                                    <option value="" disabled @selected($raw === null || $raw === '')>Selecciona una opción</option>
                                @endif
                                @if ($col['is_fk'])
                                    @foreach ($fkOptions[$name] ?? [] as $optValue => $optLabel)
                                        <option value="{{ $optValue }}" @selected((string) $raw === (string) $optValue)>{{ $optLabel }}</option>
                                    @endforeach
                                @else
                                    @foreach ($col['options'] ?? [] as $opt)
                                        <option value="{{ $opt }}" @selected((string) $raw === (string) $opt)>{{ ucfirst($opt) }}</option>
                                    @endforeach
                                @endif
                            </select>

                        @elseif ($col['type'] === 'textarea')
                            <textarea id="f_{{ $name }}" name="{{ $name }}" rows="3" class="{{ $inputClass }}" {!! $limites !!}>{{ $raw }}</textarea>

                        @elseif ($col['type'] === 'password')
                            <input id="f_{{ $name }}" type="password" name="{{ $name }}" autocomplete="new-password"
                                   placeholder="{{ $mode === 'edit' ? 'Dejar en blanco para no cambiarla' : '' }}"
                                   class="{{ $inputClass }}">

                        @elseif ($col['type'] === 'file')
                            @php
                                $carpeta = $col['upload_dir'] ?? 'assets/img/productos';
                                $actual = $record->{$name} ?? null;
                                $srcActual = $actual
                                    ? (\Illuminate\Support\Str::startsWith($actual, ['http://', 'https://', '/']) ? $actual : asset($carpeta.'/'.$actual))
                                    : null;
                            @endphp
                            <div class="js-archivo flex flex-col sm:flex-row sm:items-center gap-4">
                                <div class="w-24 h-24 rounded-xl border border-olive-100 bg-creamdark/40 flex items-center justify-center overflow-hidden shrink-0">
                                    <img class="js-archivo-preview max-w-full max-h-full object-cover {{ $srcActual ? '' : 'hidden' }}" src="{{ $srcActual }}" alt="Vista previa">
                                    <span class="js-archivo-vacio text-[11px] text-muted text-center px-2 {{ $srcActual ? 'hidden' : '' }}">Sin imagen</span>
                                </div>
                                <div>
                                    <label for="f_{{ $name }}"
                                           class="inline-flex items-center gap-2 cursor-pointer bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-4 py-2.5 rounded-xl shadow-card">
                                        @include('partials.icon', ['name' => 'upload', 'class' => 'w-4 h-4'])
                                        {{ $srcActual ? 'Cambiar archivo' : 'Subir archivo' }}
                                    </label>
                                    <input id="f_{{ $name }}" type="file" name="{{ $name }}" accept="image/*" class="sr-only js-archivo-input">
                                    <p class="js-archivo-nombre text-[12px] text-ink/70 mt-2">{{ $actual ? 'Archivo actual: '.$actual : 'Ningún archivo seleccionado' }}</p>
                                    <p class="text-[11px] text-muted mt-1">JPG, PNG, GIF, WEBP o AVIF — máximo 2&nbsp;MB.</p>
                                </div>
                            </div>

                        @elseif ($col['type'] === 'decimal')
                            <input id="f_{{ $name }}" type="number" step="{{ $col['step'] ?? ($db['step'] ?? '0.01') }}" name="{{ $name }}" value="{{ $raw }}" class="{{ $inputClass }}" {!! $limites !!}>

                        @elseif ($col['type'] === 'number')
                            <input id="f_{{ $name }}" type="number" name="{{ $name }}" value="{{ $raw }}" class="{{ $inputClass }}" {!! $limites !!}>
                            @if ($col['manual_pk'] && $mode === 'edit' && !empty($col['pk_editable']))
                                <p class="text-[11px] text-muted mt-1">Si cambias este documento se actualiza también en sus citas y horarios.</p>
                            @endif

                        @else
                            <input id="f_{{ $name }}" type="{{ $col['type'] }}" name="{{ $name }}" value="{{ $raw }}" class="{{ $inputClass }}" {!! $limites !!}>
                        @endif

                        @if (!empty($col['default']) && $mode === 'create' && $col['type'] === 'date')
                            <p class="text-[11px] text-muted mt-1">Sugerido: un año después de hoy. Puedes cambiarlo si el control es antes.</p>
                        @endif

                        @error($name)
                            <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>

            <div class="flex items-center gap-3 mt-8 pt-6 border-t border-olive-100">
                <button type="submit" class="bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-5 py-2.5 rounded-xl shadow-card">
                    {{ $mode === 'create' ? 'Crear registro' : 'Guardar cambios' }}
                </button>
                <a href="{{ route('admin.crud.index', $table) }}" class="text-sm text-muted hover:text-ink px-4 py-2.5">Cancelar</a>
            </div>
        </form>
    </div>

@endsection

@push('scripts')
@include('admin.crud._form_scripts')
@endpush
