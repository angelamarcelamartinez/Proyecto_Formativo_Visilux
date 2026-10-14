{{-- Administrador(es) de la óptica: nombre, usuario para entrar y estado de su contraseña --}}
@forelse ($lista as $a)
    @php
        $inactivo = in_array((int) $a->id_estado, [2, 4], true);
        $temporal = (int) $a->id_estado === 3;
    @endphp
    <div class="mt-1.5 pt-1.5 border-t border-olive-100/70 text-[11px] leading-snug">
        <p class="text-ink/80 break-words">
            <span class="text-muted">Admin:</span>
            <a href="{{ route('superadmin.empresas.admins.edit', [$nit, $a->documento]) }}" class="font-medium hover:text-olive-700">{{ trim($a->nombres . ' ' . $a->apellido) }}</a>
        </p>
        <p class="text-ink/60 break-all"><span class="text-muted">Usuario:</span> {{ $a->email }}</p>
        <p class="text-muted">
            Contraseña:
            @if ($temporal)
                <span class="text-amber-700">temporal, aún sin cambiar</span>
            @else
                <span>propia del administrador</span>
            @endif
            @if ($inactivo)
                · <span class="text-rose-700 font-semibold">cuenta inactiva</span>
            @endif
        </p>
    </div>
@empty
    <p class="mt-1.5 text-[11px] text-muted">Sin administrador asignado</p>
@endforelse
