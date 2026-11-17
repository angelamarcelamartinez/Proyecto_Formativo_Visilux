@extends('layouts.staff')

@section('title', 'Solicitudes web')
@section('breadcrumb', 'Solicitudes web')

@section('content')
    <div class="mb-6">
        <h1 class="font-serif text-2xl">Solicitudes web</h1>
        <p class="text-sm text-muted">Personas sin cuenta que pidieron una cita desde el sitio. Comunícate con ellas para confirmar; si no están registradas, regístralas y agéndales su cita.</p>
    </div>

    <div class="bg-white rounded-2xl border border-olive-100 shadow-card overflow-hidden">
        @if ($solicitudes->isEmpty())
            @include('staff._vacio', ['icono' => 'user-question', 'titulo' => 'No hay solicitudes', 'texto' => 'Aquí aparecen las solicitudes de visitantes.'])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-cream text-left text-[11px] uppercase tracking-wider text-muted">
                            <th class="py-3 pl-6 pr-4 font-semibold">Solicitante</th>
                            <th class="py-3 pr-4 font-semibold">Contacto</th>
                            <th class="py-3 pr-4 font-semibold">Turno pedido</th>
                            <th class="py-3 pr-4 font-semibold">Detalle</th>
                            <th class="py-3 pr-6"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-olive-100">
                        @foreach ($solicitudes as $s)
                            <tr class="hover:bg-cream/70 transition align-top">
                                <td class="py-3 pl-6 pr-4">
                                    <p class="font-medium whitespace-nowrap">{{ $s->nombre }}</p>
                                    <p class="text-xs text-muted">Doc. {{ $s->documento }}</p>
                                </td>
                                <td class="py-3 pr-4 text-ink/70">{{ $s->telefono }}<p class="text-xs text-muted">{{ $s->correo }}</p></td>
                                <td class="py-3 pr-4 whitespace-nowrap text-ink/80">
                                    @if (! empty($s->fecha_cita))
                                        {{ \Illuminate\Support\Carbon::parse($s->fecha_cita)->format('d/m/Y') }} · {{ \Illuminate\Support\Str::substr($s->hora_cita ?? '', 0, 5) }}
                                    @else — @endif
                                </td>
                                <td class="py-3 pr-4 text-ink/70 max-w-sm">{{ $s->motivo_cita }}</td>
                                <td class="py-3 pr-6 text-right">
                                    <a href="{{ route('recepcion.paciente.nuevo') }}" class="text-xs font-semibold text-olive-600 hover:text-olive-800 whitespace-nowrap">Registrar</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
