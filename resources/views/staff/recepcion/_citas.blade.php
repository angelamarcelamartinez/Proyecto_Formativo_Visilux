{{-- Lista de citas de la óptica con acciones de recepción. Recibe $citas y, opcional, $mostrarFecha. --}}
@php
    $fechaCorta = fn ($f) => str_replace('.', '', \Illuminate\Support\Carbon::parse($f)->locale('es')->translatedFormat('D j M'));
@endphp
<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-cream text-left text-[11px] uppercase tracking-wider text-muted">
                @if (! empty($mostrarFecha)) <th class="py-3 pl-6 pr-4 font-semibold">Fecha</th> @endif
                <th class="py-3 {{ empty($mostrarFecha) ? 'pl-6' : '' }} pr-4 font-semibold">Hora</th>
                <th class="py-3 pr-4 font-semibold">Paciente</th>
                <th class="py-3 pr-4 font-semibold">Optómetra</th>
                <th class="py-3 pr-4 font-semibold">Estado</th>
                <th class="py-3 pr-6 font-semibold text-right">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-olive-100">
            @foreach ($citas as $c)
                <tr class="hover:bg-cream/70 transition">
                    @if (! empty($mostrarFecha)) <td class="py-3 pl-6 pr-4 whitespace-nowrap text-ink/80">{{ $fechaCorta($c->fecha_cita) }}</td> @endif
                    <td class="py-3 {{ empty($mostrarFecha) ? 'pl-6' : '' }} pr-4 whitespace-nowrap tabular-nums font-medium">{{ \Illuminate\Support\Str::substr($c->hora_cita, 0, 5) }}</td>
                    <td class="py-3 pr-4">
                        <p class="font-medium whitespace-nowrap">{{ $c->nombres }} {{ $c->apellido }}</p>
                        <p class="text-xs text-muted">{{ $c->telefono }} · {{ $c->tipo ?? 'Cita' }}</p>
                    </td>
                    <td class="py-3 pr-4 text-ink/70 whitespace-nowrap">{{ $c->optometra }}</td>
                    <td class="py-3 pr-4">@include('staff._estado', ['estado' => $c->estado])</td>
                    <td class="py-3 pr-6 text-right whitespace-nowrap">
                        @if ($c->id_estado == 5 || $c->id_estado == 4)
                            <span class="text-xs text-muted">—</span>
                        @else
                            <div class="inline-flex items-center gap-2">
                                @if ($c->id_estado != 11)
                                    <form method="POST" action="{{ route('recepcion.cita.estado', $c->id_cita) }}">
                                        @csrf <input type="hidden" name="accion" value="confirmar">
                                        <button type="submit" class="px-2.5 py-1.5 rounded-lg text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100">Confirmar</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('recepcion.cita.estado', $c->id_cita) }}"
                                      onsubmit="return confirm('¿Cancelar la cita de {{ e($c->nombres) }}? El turno quedará libre.');">
                                    @csrf <input type="hidden" name="accion" value="cancelar">
                                    <button type="submit" class="px-2.5 py-1.5 rounded-lg text-xs font-medium text-rose-700 bg-rose-50 hover:bg-rose-100">Cancelar</button>
                                </form>
                            </div>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
