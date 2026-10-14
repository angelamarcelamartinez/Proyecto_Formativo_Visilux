{{-- Botones de una óptica en la lista. Con $etiquetas muestra el texto junto al ícono (vista de tarjetas). --}}
@php
    $etiquetas = $etiquetas ?? false;
    $clase = 'inline-flex items-center gap-1.5 rounded-lg text-ink/70 hover:bg-olive-100 hover:text-olive-700 transition ' . ($etiquetas ? 'px-3 py-2 text-xs font-medium border border-olive-100' : 'p-2');
@endphp
<div class="flex items-center {{ $etiquetas ? 'flex-wrap gap-2' : 'justify-end gap-0.5' }}">
    <a href="{{ route('superadmin.empresas.show', $o->nit) }}" title="Ver detalle" class="{{ $clase }}">
        @include('partials.icon', ['name' => 'eye', 'class' => 'w-4 h-4'])
        @if ($etiquetas) Ver @endif
    </a>
    <a href="{{ route('superadmin.empresas.admins', $o->nit) }}" title="Ver administradores" class="{{ $clase }}">
        @include('partials.icon', ['name' => 'users', 'class' => 'w-4 h-4'])
        @if ($etiquetas) Administradores @endif
    </a>
    <a href="{{ route('superadmin.empresas.edit', $o->nit) }}" title="Editar datos" class="{{ $clase }}">
        @include('partials.icon', ['name' => 'pencil', 'class' => 'w-4 h-4'])
        @if ($etiquetas) Editar @endif
    </a>
</div>
