{{--
    Botón + ventana emergente para revisar el certificado de la Cámara de Comercio de una óptica.
    Recibe: $empresa. Opcionales: $licencia (si viene, la ventana incluye la confirmación y
    aprueba el registro de esa licencia) y $etiqueta (texto del botón).

    El superadmin abre el PDF, lo compara a mano con la Cámara de Comercio (sin API) y, si el
    NIT existe, marca la casilla y confirma.
--}}
@php
    $licencia = $licencia ?? null;
    $etiqueta = $etiqueta ?? ($licencia ? 'Verificar NIT y aprobar' : 'Ver certificado');
@endphp

<div x-data="{ abierto: false }" class="inline-block">
    <button type="button" @click="abierto = true"
            class="inline-flex items-center gap-1.5 text-sm font-medium rounded-lg px-4 py-2 transition
                   {{ $licencia ? 'bg-olive-600 hover:bg-olive-700 text-cream' : 'bg-olive-50 text-olive-700 hover:bg-olive-100' }}">
        @include('partials.icon', ['name' => 'file-text', 'class' => 'w-4 h-4'])
        {{ $etiqueta }}
    </button>

    <div x-show="abierto" x-cloak x-transition.opacity
         @keydown.escape.window="abierto = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-ink/60 p-4 text-left" role="dialog" aria-modal="true">
        <div @click.outside="abierto = false" class="bg-white rounded-2xl shadow-xl w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden">

            <div class="flex items-start justify-between gap-4 px-6 py-4 border-b border-olive-100">
                <div>
                    <h3 class="font-serif text-lg leading-tight">Verificar NIT con la Cámara de Comercio</h3>
                    <p class="text-sm text-muted">{{ $empresa->nombre }} · NIT <strong class="text-ink">{{ $empresa->nit }}</strong></p>
                </div>
                <button type="button" @click="abierto = false" class="p-1.5 rounded-lg hover:bg-olive-100 text-ink/70" aria-label="Cerrar">
                    @include('partials.icon', ['name' => 'x', 'class' => 'w-5 h-5'])
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4">
                @if ($empresa->tieneCamara())
                    <template x-if="abierto">
                        <iframe src="{{ route('superadmin.empresas.camara', $empresa->nit) }}" title="Certificado de la Cámara de Comercio"
                                class="w-full h-[50vh] rounded-xl border border-olive-100 bg-creamdark/40"></iframe>
                    </template>
                    <a href="{{ route('superadmin.empresas.camara', $empresa->nit) }}" target="_blank" class="text-sm text-olive-700 hover:underline">
                        Abrir el PDF en una pestaña nueva
                    </a>
                @else
                    <div class="rounded-xl bg-rose-50 text-rose-700 text-sm px-4 py-3">
                        Esta óptica no tiene certificado cargado. Edita la óptica y adjunta el PDF.
                    </div>
                @endif

                @if ($licencia)
                    <ol class="text-sm text-ink/80 list-decimal pl-5 space-y-1">
                        <li>Confirma que el <strong>NIT {{ $empresa->nit }}</strong> y el nombre <strong>{{ $empresa->nombre }}</strong> coinciden con el certificado.</li>
                        <li>Comprueba en la Cámara de Comercio (o en el RUES) que esa empresa existe y está activa.</li>
                        <li>Si todo coincide, marca la casilla y aprueba. Si no, cierra y usa «Rechazar».</li>
                    </ol>
                @endif
            </div>

            <div class="px-6 py-4 border-t border-olive-100 bg-creamdark/30">
                @if ($licencia && $empresa->tieneCamara())
                    <form method="POST" action="{{ route('superadmin.licencias.aprobar', $licencia->id_licencia) }}" class="flex flex-wrap items-center gap-3 justify-between">
                        @csrf
                        <label class="flex items-start gap-2 text-sm max-w-xl">
                            <input type="checkbox" name="confirmo" value="1" required class="mt-1 rounded border-olive-300">
                            <span>Verifiqué con la Cámara de Comercio que el NIT <strong>{{ $empresa->nit }}</strong> corresponde a una empresa que existe.</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="abierto = false" class="text-sm text-muted hover:text-ink px-3 py-2">Cancelar</button>
                            <button type="submit" class="bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-4 py-2 rounded-lg">Confirmar y aprobar</button>
                        </div>
                    </form>
                @else
                    <div class="text-right">
                        <button type="button" @click="abierto = false" class="text-sm font-medium bg-olive-600 hover:bg-olive-700 transition text-cream px-4 py-2 rounded-lg">Cerrar</button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
