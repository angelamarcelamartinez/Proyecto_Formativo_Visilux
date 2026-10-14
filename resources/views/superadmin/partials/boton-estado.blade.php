{{--
    Botón para activar o suspender una óptica.
    Variables: $nit, $nombre, $suspendida (bool), $compacto (bool, opcional)
--}}
@php $compacto = $compacto ?? false; @endphp
<form method="POST" action="{{ route('superadmin.empresas.estado', $nit) }}" class="inline"
      onsubmit="return confirm('{{ $suspendida
          ? '¿Reactivar ' . addslashes($nombre) . '? Su equipo podrá volver a entrar al panel.'
          : '¿Suspender ' . addslashes($nombre) . '? Su equipo no podrá entrar al panel y su página pública dejará de mostrarse.' }}')">
    @csrf
    @if ($compacto)
        <button type="submit" title="{{ $suspendida ? 'Activar' : 'Suspender' }}"
                class="p-2 rounded-lg transition {{ $suspendida ? 'text-emerald-600 hover:bg-emerald-50' : 'text-ink/60 hover:bg-rose-50 hover:text-rose-600' }}">
            @include('partials.icon', ['name' => $suspendida ? 'shield' : 'lock', 'class' => 'w-4 h-4'])
        </button>
    @else
        <button type="submit"
                class="inline-flex items-center gap-2 text-sm font-medium px-4 py-2.5 rounded-xl transition
                       {{ $suspendida ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-white border border-rose-200 text-rose-700 hover:bg-rose-50' }}">
            @include('partials.icon', ['name' => $suspendida ? 'shield' : 'lock', 'class' => 'w-4 h-4'])
            {{ $suspendida ? 'Activar óptica' : 'Suspender óptica' }}
        </button>
    @endif
</form>
