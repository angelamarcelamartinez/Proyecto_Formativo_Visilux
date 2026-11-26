@extends('layouts.admin')

@section('title', $empleado ? 'Editar empleado' : 'Nuevo empleado')
@section('breadcrumb')
    <a href="{{ route('admin.equipo.index') }}" class="hover:underline">Equipo</a> / {{ $empleado ? 'Editar' : 'Nuevo' }}
@endsection

@section('content')

    @php
        $campo = 'w-full bg-creamdark/40 border border-olive-100 rounded-xl py-2.5 px-3.5 text-sm focus:outline-none focus:ring-2 focus:ring-olive-300';
        $rolOpt = (string) config('visioptica.rol_optometra', 7);
        // Optómetras que ya están en la tabla `optometra` (con citas y horarios) pero todavía no tienen usuario.
        $existentes = collect($optometrasSinUsuario ?? [])->mapWithKeys(fn ($o) => [$o->doc_optometra => [
            'nombres' => $o->nombre,
            'apellido' => $o->apellido,
            'email' => ($o->correo && $o->correo !== '0') ? $o->correo : '',
            'telefono' => (string) $o->telefono,
            'tarjeta' => (string) ($o->tarjeta_profesional ?? ''),
            'especialidad' => (string) ($o->id_especialidad ?? ''),
        ]]);
    @endphp

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

    <form method="POST" action="{{ $empleado ? route('admin.equipo.update', $empleado->documento) : route('admin.equipo.store') }}" class="max-w-3xl space-y-6"
          x-data="{
              rol: '{{ old('id_rol', $empleado?->id_rol) }}',
              existente: '{{ old('optometra_existente') }}',
              registros: {{ \Illuminate\Support\Js::from($existentes) }},
              get esOptometra() { return this.rol == '{{ $rolOpt }}' },
              // Al elegir un optómetra que ya existe, se llenan sus datos (se pueden corregir).
              cargar() {
                  const r = this.registros[this.existente];
                  if (! r) return;
                  for (const [ref, valor] of Object.entries({ nom: r.nombres, ape: r.apellido, mail: r.email, tel: r.telefono, tp: r.tarjeta })) {
                      if (this.$refs[ref] && valor) this.$refs[ref].value = valor;
                  }
                  if (this.$refs.esp && r.especialidad) this.$refs.esp.value = r.especialidad;
              },
          }">
        @csrf
        @if ($empleado) @method('PUT') @endif

        <div class="bg-white rounded-2xl border border-olive-100 shadow-card p-6 sm:p-8 space-y-6">

            {{-- 1. Rol primero: según el rol, el formulario pide unos datos u otros --}}
            <div>
                <label class="block text-sm font-medium mb-1.5" for="e_rol">Rol <span class="text-red-500">*</span></label>
                <select id="e_rol" name="id_rol" class="{{ $campo }}" required x-model="rol">
                    <option value="">Selecciona un rol…</option>
                    @foreach ($roles as $r)
                        <option value="{{ $r->id_rol }}" @selected(old('id_rol', $empleado?->id_rol) == $r->id_rol)>{{ $r->nombre_rol }}{{ $r->descripcion ? ' — ' . $r->descripcion : '' }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-muted mt-1.5" x-show="esOptometra" x-cloak>
                    Entrará al módulo del optómetra: agenda, historias clínicas, fórmulas, terapias y simulador visual.
                </p>
            </div>

            {{-- 2. Solo para Optómetra: datos profesionales (tabla optometra) --}}
            <div x-show="esOptometra" x-cloak class="rounded-xl border border-olive-100 bg-creamdark/30 p-5 space-y-5">
                <p class="text-sm font-semibold">Datos profesionales del optómetra</p>

                @unless ($empleado)
                    @if ($existentes->isNotEmpty())
                        <div>
                            <label class="block text-sm font-medium mb-1.5" for="e_exist">¿Ya está en la lista de optómetras de la óptica?</label>
                            <select id="e_exist" name="optometra_existente" class="{{ $campo }}" x-model="existente" @change="cargar()">
                                <option value="">No, es un optómetra nuevo</option>
                                @foreach ($optometrasSinUsuario as $o)
                                    <option value="{{ $o->doc_optometra }}">
                                        {{ $o->nombre }} {{ $o->apellido }}{{ $o->tarjeta_profesional ? ' · T.P. ' . $o->tarjeta_profesional : '' }} · registro {{ $o->doc_optometra }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-muted mt-1.5">
                                Elígelo si ya está registrado (con horarios o citas) pero todavía no tiene cuenta: se le crea la cuenta en su mismo registro y conserva todo. Sus datos se llenan solos abajo; corrígelos si hace falta.
                            </p>
                        </div>
                    @endif
                @endunless

                <div class="grid sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium mb-1.5" for="e_tp">Tarjeta profesional <span class="text-red-500">*</span></label>
                        <input id="e_tp" x-ref="tp" type="number" name="tarjeta_profesional" value="{{ old('tarjeta_profesional', $optometra?->tarjeta_profesional ?? '') }}" class="{{ $campo }}" :required="esOptometra">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5" for="e_esp">Especialidad</label>
                        <select id="e_esp" x-ref="esp" name="id_especialidad" class="{{ $campo }}">
                            <option value="">Optometría Clínica (por defecto)</option>
                            @foreach ($especialidades ?? [] as $esp)
                                <option value="{{ $esp->id_especialidad }}" @selected(old('id_especialidad', $optometra?->id_especialidad ?? '') == $esp->id_especialidad)>{{ $esp->nombre_especialidad }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- 3. Datos personales (todos los roles) --}}
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
                        <label class="block text-sm font-medium mb-1.5" for="e_doc">
                            <span x-text="esOptometra ? 'Número de cédula (no la tarjeta profesional)' : 'Documento'">Documento</span>
                            <span class="text-red-500">*</span>
                        </label>
                        <input id="e_doc" type="text" name="documento" value="{{ old('documento') }}" class="{{ $campo }}" data-doc-tipo="#e_tipo" autocomplete="off" required>
                        <p class="text-xs text-muted mt-1.5" data-doc-ayuda></p>
                        <p class="text-xs text-muted mt-1.5" x-show="esOptometra && existente" x-cloak>
                            Si su registro tenía otro número (<span x-text="existente"></span>), al guardar se cambia por esta cédula y sus citas y horarios pasan con ella.
                        </p>
                    </div>
                @endunless
                <div>
                    <label class="block text-sm font-medium mb-1.5" for="e_nom">Nombres <span class="text-red-500">*</span></label>
                    <input id="e_nom" x-ref="nom" name="nombres" value="{{ old('nombres', $empleado?->nombres) }}" class="{{ $campo }}" required>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5" for="e_ape">Apellidos <span class="text-red-500">*</span></label>
                    <input id="e_ape" x-ref="ape" name="apellido" value="{{ old('apellido', $empleado?->apellido) }}" class="{{ $campo }}" required>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5" for="e_mail">Correo (será su usuario) <span class="text-red-500">*</span></label>
                    <input id="e_mail" x-ref="mail" type="email" name="email" value="{{ old('email', $empleado?->email) }}" class="{{ $campo }}" required>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5" for="e_tel">Celular <span class="text-red-500">*</span></label>
                    <input id="e_tel" x-ref="tel" name="telefono" value="{{ old('telefono', $empleado?->telefono) }}" class="{{ $campo }}" required>
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
    @include('partials.documento-js')

@endsection
