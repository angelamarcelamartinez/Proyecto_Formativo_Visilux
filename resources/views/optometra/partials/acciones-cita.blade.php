{{-- Botones de una cita según su estado. Recibe $c (fila de Optometria::citas()). --}}
@php
    $estadoNom = mb_strtolower((string) $c->nom_estado);
    $esMia = (string) $c->id_optometra === \App\Support\Optometria::doc();
@endphp
<div class="flex flex-wrap items-center justify-end gap-2">
    @if ($c->id_historia_clinica)
        <a href="{{ route('optometra.historias.show', $c->id_historia_clinica) }}" class="op-btn op-btn-ghost op-btn-sm">
            @include('partials.icon', ['name' => 'file-text', 'class' => 'w-3.5 h-3.5']) Historia
        </a>
    @elseif ($esMia && $estadoNom === 'cancelado')
        <form method="POST" action="{{ route('optometra.citas.estado', $c->id_cita) }}">
            @csrf
            <input type="hidden" name="accion" value="reabrir">
            <button class="op-btn op-btn-ghost op-btn-sm">Reabrir</button>
        </form>
    @elseif ($esMia)
        @if ($estadoNom === 'en proceso')
            <a href="{{ route('optometra.consulta.create', $c->id_cita) }}" class="op-btn op-btn-primary op-btn-sm">
                @include('partials.icon', ['name' => 'stethoscope', 'class' => 'w-3.5 h-3.5']) Continuar consulta
            </a>
        @else
            <form method="POST" action="{{ route('optometra.citas.estado', $c->id_cita) }}">
                @csrf
                <input type="hidden" name="accion" value="iniciar">
                <button class="op-btn op-btn-primary op-btn-sm">
                    @include('partials.icon', ['name' => 'stethoscope', 'class' => 'w-3.5 h-3.5']) Atender
                </button>
            </form>
        @endif
        <form method="POST" action="{{ route('optometra.citas.estado', $c->id_cita) }}"
              x-data @submit="if (! confirm('¿Cancelar la cita de ' + {{ \Illuminate\Support\Js::from($c->nombres) }} + '? El turno quedará libre.')) $event.preventDefault()">
            @csrf
            <input type="hidden" name="accion" value="cancelar">
            <input type="hidden" name="motivo" value="No asistió">
            <button class="op-btn op-btn-danger op-btn-sm" title="No asistió / cancelar">
                @include('partials.icon', ['name' => 'x', 'class' => 'w-3.5 h-3.5'])
            </button>
        </form>
    @endif
    <a href="{{ route('optometra.pacientes.show', $c->id_usuario) }}" class="op-btn op-btn-ghost op-btn-sm" title="Ficha del paciente">
        @include('partials.icon', ['name' => 'user', 'class' => 'w-3.5 h-3.5'])
    </a>
</div>
