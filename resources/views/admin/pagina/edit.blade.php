@extends('layouts.admin')

@section('title', 'Mi página')
@section('breadcrumb', 'Mi página')

@section('content')

    @php
        $campo = 'w-full bg-creamdark/40 border border-olive-100 rounded-xl py-2.5 px-3.5 text-sm focus:outline-none focus:ring-2 focus:ring-olive-300';

        // Cada sección del index con sus campos. tipo: text | textarea | lista | email | url | imagen
        $secciones = [
            'portada' => [
                'titulo' => 'Portada',
                'ayuda' => 'Lo primero que ve un paciente al entrar.',
                'campos' => [
                    ['hero_titulo', 'Título', 'text'],
                    ['hero_subtitulo', 'Subtítulo', 'text'],
                    ['hero_texto', 'Texto', 'textarea', 'full'],
                    ['hero_imagen', 'Imagen de fondo', 'imagen', 'full'],
                ],
            ],
            'cifras' => [
                'titulo' => 'Cifras',
                'ayuda' => 'La franja con tres números debajo de la portada.',
                'campos' => [
                    ['stat1_valor', 'Cifra 1', 'text'], ['stat1_texto', 'Descripción 1', 'text'],
                    ['stat2_valor', 'Cifra 2', 'text'], ['stat2_texto', 'Descripción 2', 'text'],
                    ['stat3_valor', 'Cifra 3', 'text'], ['stat3_texto', 'Descripción 3', 'text'],
                ],
            ],
            'servicios' => [
                'titulo' => 'Servicios',
                'ayuda' => 'Los dos servicios principales, con sus beneficios.',
                'campos' => [
                    ['servicios_titulo', 'Título de la sección', 'text'],
                    ['servicios_subtitulo', 'Subtítulo de la sección', 'text'],
                    ['serv1_titulo', 'Servicio 1: nombre', 'text', 'full'],
                    ['serv1_texto', 'Servicio 1: descripción', 'textarea'],
                    ['serv1_items', 'Servicio 1: beneficios', 'lista'],
                    ['serv1_imagen', 'Servicio 1: imagen', 'imagen', 'full'],
                    ['serv2_titulo', 'Servicio 2: nombre', 'text', 'full'],
                    ['serv2_texto', 'Servicio 2: descripción', 'textarea'],
                    ['serv2_items', 'Servicio 2: beneficios', 'lista'],
                    ['serv2_imagen', 'Servicio 2: imagen', 'imagen', 'full'],
                ],
            ],
            'profesional' => [
                'titulo' => 'Profesional',
                'ayuda' => 'La persona que atiende en la óptica.',
                'campos' => [
                    ['prof_nombre', 'Nombre', 'text'],
                    ['prof_cargo', 'Cargo', 'text'],
                    ['prof_texto', 'Presentación', 'textarea', 'full'],
                    ['prof_credenciales', 'Estudios', 'lista', 'full', 'Uno por línea, con este formato: Título | Institución'],
                    ['prof_imagen', 'Foto', 'imagen', 'full'],
                ],
            ],
            'porque' => [
                'titulo' => '¿Por qué elegirnos?',
                'ayuda' => 'Dos razones para escoger tu óptica.',
                'campos' => [
                    ['porque_titulo', 'Título', 'text'], ['porque_subtitulo', 'Subtítulo', 'text'],
                    ['ventaja1_titulo', 'Razón 1', 'text'], ['ventaja1_texto', 'Descripción 1', 'text'],
                    ['ventaja2_titulo', 'Razón 2', 'text'], ['ventaja2_texto', 'Descripción 2', 'text'],
                ],
            ],
            'contacto' => [
                'titulo' => 'Contacto y pie de página',
                'ayuda' => 'Aparece al final de todas las páginas del sitio.',
                'campos' => [
                    ['logo', 'Logo', 'imagen', 'full'],
                    ['footer_texto', 'Texto del pie de página', 'textarea', 'full'],
                    ['telefono', 'Teléfono', 'text'], ['email', 'Correo', 'email'],
                    ['direccion', 'Dirección', 'text', 'full'],
                    ['horario', 'Horario', 'lista', 'full', 'Una línea por horario, por ejemplo: Sáb: 9:00 – 14:00'],
                    ['instagram', 'Instagram', 'url', null, 'Enlace completo. Déjalo vacío para ocultar el ícono.'],
                    ['facebook', 'Facebook', 'url', null, 'Enlace completo. Déjalo vacío para ocultar el ícono.'],
                ],
            ],
        ];
    @endphp

    <div class="flex items-end justify-between flex-wrap gap-4 mb-6">
        <div>
            <h1 class="font-serif text-2xl">Mi página</h1>
            <p class="text-sm text-muted">
                Los textos e imágenes de la página pública de {{ $empresa->nombre }}.
                @if ($pagina->actualizado)
                    Última edición: {{ $pagina->actualizado->format('d/m/Y H:i') }}.
                @endif
            </p>
        </div>
        <a href="{{ $empresa->urlPagina() }}" target="_blank"
           class="inline-flex items-center gap-2 text-sm font-medium px-4 py-2.5 rounded-xl bg-white border border-olive-100 hover:bg-olive-50 transition">
            @include('partials.icon', ['name' => 'eye', 'class' => 'w-4 h-4'])
            Ver mi página
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm">
            Revisa los campos marcados en rojo; no se guardó ningún cambio.
        </div>
    @endif

    <form method="POST" action="{{ route('admin.pagina.update') }}" enctype="multipart/form-data"
          x-data="{ seccion: '{{ old('_seccion', 'portada') }}' }">
        @csrf
        @method('PUT')
        <input type="hidden" name="_seccion" :value="seccion">

        <div class="grid lg:grid-cols-[14rem_1fr] gap-6 items-start">

            {{-- Índice de secciones --}}
            <nav class="bg-white rounded-2xl border border-olive-100 shadow-card p-2 lg:sticky lg:top-24 flex lg:flex-col gap-1 overflow-x-auto">
                @foreach ($secciones as $key => $sec)
                    @php
                        $conError = collect($sec['campos'])->contains(fn ($c) => $errors->has($c[0]));
                    @endphp
                    <button type="button" @click="seccion = '{{ $key }}'"
                            :class="seccion === '{{ $key }}' ? 'bg-olive-600 text-cream' : 'text-ink/80 hover:bg-olive-100'"
                            class="shrink-0 text-left px-3 py-2 rounded-lg text-sm font-medium transition flex items-center justify-between gap-2">
                        {{ $sec['titulo'] }}
                        @if ($conError)
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        @endif
                    </button>
                @endforeach
            </nav>

            <div>
                @foreach ($secciones as $key => $sec)
                    <section x-show="seccion === '{{ $key }}'" @if ($key !== 'portada') x-cloak @endif
                             class="bg-white rounded-2xl border border-olive-100 shadow-card p-6 sm:p-8">
                        <h2 class="font-serif text-xl">{{ $sec['titulo'] }}</h2>
                        <p class="text-sm text-muted mb-6">{{ $sec['ayuda'] }}</p>

                        <div class="grid sm:grid-cols-2 gap-5">
                            @foreach ($sec['campos'] as $c)
                                @php
                                    [$name, $label, $tipo] = $c;
                                    $ancho = ($c[3] ?? null) === 'full' ? 'sm:col-span-2' : '';
                                    $nota = $c[4] ?? ($tipo === 'lista' ? 'Un elemento por línea.' : null);
                                @endphp

                                <div class="{{ $ancho }}">
                                    <label class="block text-sm font-medium mb-1.5" for="f_{{ $name }}">{{ $label }}</label>

                                    @if ($tipo === 'imagen')
                                        <div class="flex flex-col sm:flex-row gap-4 items-start" x-data="{ vista: null }">
                                            <img :src="vista || '{{ $pagina->imagen($name) }}'" alt="{{ $label }}"
                                                 class="w-40 h-28 object-cover rounded-xl border border-olive-100 bg-creamdark {{ $name === 'logo' ? 'object-contain p-2' : '' }}">
                                            <div class="flex-1 w-full">
                                                <input id="f_{{ $name }}" type="file" name="{{ $name }}" accept="image/*"
                                                       @change="vista = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null"
                                                       class="w-full bg-creamdark/40 border border-olive-100 rounded-xl py-2 px-3.5 text-sm focus:outline-none focus:ring-2 focus:ring-olive-300">
                                                <p class="text-[11px] text-muted mt-1">JPG, PNG, WEBP o GIF, máximo 2 MB. Sube una solo si quieres cambiarla.</p>
                                                @if ($pagina->{$name})
                                                    <label class="mt-2 inline-flex items-center gap-2 text-xs text-ink/70">
                                                        <input type="checkbox" name="quitar_{{ $name }}" value="1" class="rounded border-olive-300">
                                                        Quitar mi imagen y volver a la de ejemplo
                                                    </label>
                                                @endif
                                            </div>
                                        </div>

                                    @elseif (in_array($tipo, ['textarea', 'lista']))
                                        <textarea id="f_{{ $name }}" name="{{ $name }}" rows="{{ $tipo === 'lista' ? 4 : 3 }}"
                                                  class="{{ $campo }}">{{ old($name, $pagina->{$name}) }}</textarea>

                                    @else
                                        <input id="f_{{ $name }}" type="{{ $tipo }}" name="{{ $name }}"
                                               value="{{ old($name, $pagina->{$name}) }}"
                                               @if ($tipo === 'url') placeholder="https://" @endif
                                               class="{{ $campo }}">
                                    @endif

                                    @if ($nota)
                                        <p class="text-[11px] text-muted mt-1">{{ $nota }}</p>
                                    @endif
                                    @error($name)
                                        <p class="text-[12px] text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                <div class="sticky bottom-0 mt-6 -mx-4 sm:mx-0 px-4 sm:px-0 py-4 bg-cream/90 backdrop-blur flex items-center gap-3">
                    <button type="submit" class="bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-5 py-2.5 rounded-xl shadow-card">
                        Guardar cambios
                    </button>
                    <p class="text-xs text-muted">Se guardan todas las secciones a la vez.</p>
                </div>
            </div>
        </div>
    </form>

@endsection
