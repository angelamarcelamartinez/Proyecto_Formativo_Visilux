@extends('layouts.staff')

@section('title', 'Solicitudes web')
@section('breadcrumb', 'Solicitudes web')

@section('content')
    <div class="mb-6">
        <h1 class="font-serif text-2xl">Solicitudes web</h1>
        <p class="text-sm text-muted">Personas sin cuenta que pidieron una cita desde el sitio. Contacta a la persona y toca «Registrar cita»: se agenda con el día y la hora que ella pidió, el optómetra la ve y el turno queda ocupado en el sitio. Si el turno ya no sirve, cámbialo.</p>
    </div>

    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm">{{ $errors->first() }}</div>
    @endif

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
                                    @if ($s->pedido['fecha'])
                                        {{ \Illuminate\Support\Carbon::parse($s->pedido['fecha'])->format('d/m/Y') }}@if ($s->pedido['hora']) · {{ $s->pedido['hora'] }}@endif
                                        @if ($s->pedido['optometra'])<p class="text-xs text-muted">{{ $s->pedido['optometra'] }}</p>@endif
                                    @else — @endif
                                </td>
                                <td class="py-3 pr-4 text-ink/70 max-w-sm">{{ $s->motivo_cita }}</td>
                                <td class="py-3 pr-6 text-right whitespace-nowrap">
                                    @php
                                        $tel = preg_replace('/\D/', '', (string) $s->telefono);
                                        $wa = strlen($tel) === 10 ? '57' . $tel : $tel;
                                    @endphp
                                    <div class="flex items-center justify-end gap-2">
                                        <details class="relative text-left">
                                            <summary class="list-none cursor-pointer px-3 py-1.5 rounded-lg text-xs font-medium text-sky-700 bg-sky-50">Contactar con el paciente</summary>
                                            <div class="absolute right-0 mt-1 z-20 w-44 bg-white border border-olive-100 rounded-xl shadow p-1 text-xs">
                                                <a href="tel:{{ $tel }}" class="block px-3 py-2 rounded-lg hover:bg-cream">Llamar · {{ $s->telefono }}</a>
                                                <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="block px-3 py-2 rounded-lg hover:bg-cream">WhatsApp</a>
                                                <a href="mailto:{{ $s->correo }}" class="block px-3 py-2 rounded-lg hover:bg-cream">Correo</a>
                                            </div>
                                        </details>
                                        @if ($s->agendada)
                                            <span class="text-[11px] font-medium px-2.5 py-1 rounded-full ring-1 ring-inset bg-emerald-50 text-emerald-700 ring-emerald-200">Agendada · {{ \Illuminate\Support\Carbon::parse($s->agendada->fecha_cita)->format('d/m') }} {{ \Illuminate\Support\Str::substr((string) $s->agendada->hora_cita, 0, 5) }}</span>
                                        @else
                                            <form method="POST" action="{{ route('recepcion.solicitud.registrar', $s->id_usuario_nr) }}">
                                                @csrf
                                                <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100">Registrar cita</button>
                                            </form>
                                            <a href="{{ route('recepcion.solicitud.agendar', $s->id_usuario_nr) }}" class="text-xs font-semibold text-olive-600 hover:text-olive-800">Cambiar turno</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
