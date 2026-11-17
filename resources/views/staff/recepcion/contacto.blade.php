@extends('layouts.staff')

@section('title', 'Por contactar')
@section('breadcrumb', 'Por contactar')

@section('content')
    <div class="mb-6">
        <h1 class="font-serif text-2xl">Por contactar</h1>
        <p class="text-sm text-muted">Mensajes que llegaron desde «Contáctanos». Escríbeles o llámalos y márcalos como contactados.</p>
    </div>

    <div class="bg-white rounded-2xl border border-olive-100 shadow-card overflow-hidden">
        @if ($mensajes->isEmpty())
            @include('staff._vacio', ['icono' => 'mail', 'titulo' => 'No hay mensajes', 'texto' => 'Cuando alguien escriba desde el sitio aparecerá aquí.'])
        @else
            <ul class="divide-y divide-olive-100">
                @foreach ($mensajes as $m)
                    @php $hecho = $m->estado_contactenos === 'Contactado'; @endphp
                    <li class="px-5 sm:px-6 py-4 flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium">{{ $m->nombre_contactenos }}
                                <span class="ml-2 text-[11px] font-medium px-2.5 py-1 rounded-full ring-1 ring-inset {{ $hecho ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200' }}">{{ $m->estado_contactenos }}</span>
                            </p>
                            <p class="text-sm text-ink/70 mt-1">
                                <a href="mailto:{{ $m->correo_contactenos }}" class="text-olive-700 hover:underline">{{ $m->correo_contactenos }}</a>
                                @if ($m->telefono_contactenos) · <a href="tel:{{ preg_replace('/[^0-9+]/', '', $m->telefono_contactenos) }}" class="text-olive-700 hover:underline">{{ $m->telefono_contactenos }}</a>@endif
                                · {{ \Illuminate\Support\Carbon::parse($m->fecha_contactenos)->format('d/m/Y H:i') }}
                            </p>
                            <p class="text-sm text-ink/80 mt-2">{{ $m->mensaje_contactenos }}</p>
                        </div>
                        <form method="POST" action="{{ route('recepcion.contacto.estado', $m->id_contactenos) }}">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium {{ $hecho ? 'text-amber-700 bg-amber-50 hover:bg-amber-100' : 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100' }}">
                                {{ $hecho ? 'Volver a pendiente' : 'Marcar contactado' }}
                            </button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
