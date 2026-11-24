@extends('layouts.admin')

@section('title', 'Triggers')
@section('breadcrumb', 'Triggers')

@section('content')
    <div class="mb-6">
        <h1 class="font-serif text-2xl">Triggers</h1>
        <p class="text-sm text-muted">Registros que la base de datos guarda sola cuando cambian los datos. Se muestran los últimos 5 de cada tabla; si salen vacías, ejecuta primero los scripts SQL de triggers.</p>
    </div>

    @php
        $bloques = [
            ['Accesos al sistema (trigger 1)', $logAcceso, fn ($r) => $r->nombre.' (doc. '.$r->documento.') inició sesión · '.$r->fecha_hora],
            ['Cambios de estado de citas (trigger 2)', $logCitas, fn ($r) => $r->paciente.' (doc. '.$r->documento.') · cita'.($r->cita ? ' del '.$r->cita : ' #'.$r->id_cita).': '.$r->de.' → '.$r->a.' · '.$r->fecha_hora],
            ['Estado de usuarios (trigger 3)', $logUsuarios, fn ($r) => $r->nombre.' (doc. '.$r->documento.'): '.$r->de.' → '.$r->a.' · '.$r->fecha_hora],
            ['Cambios de precio de productos (trigger 4)', $logPrecios, fn ($r) => $r->producto.': '.$r->de.' → '.$r->a.' · '.$r->fecha_hora],
        ];
    @endphp
    <div class="grid md:grid-cols-2 gap-5">
        @foreach ($bloques as [$titulo, $filas, $texto])
            <div class="bg-white rounded-2xl border border-olive-100 shadow-card p-5">
                <h3 class="font-medium text-sm mb-3">{{ $titulo }}</h3>
                @if ($filas->isEmpty())
                    <p class="text-xs text-muted">Sin registros todavía.</p>
                @else
                    <ul class="space-y-1.5 text-xs text-ink/80">
                        @foreach ($filas as $f) <li>{{ $texto($f) }}</li> @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>
@endsection
