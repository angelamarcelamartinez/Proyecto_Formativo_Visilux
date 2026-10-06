@extends('layouts.superadmin')

@section('title', 'Planes')
@section('breadcrumb', 'Planes')

@section('content')

    @php
        $campo = 'w-full bg-creamdark/40 border border-olive-100 rounded-xl py-2.5 px-3.5 text-sm focus:outline-none focus:ring-2 focus:ring-olive-300';
    @endphp

    <div class="mb-6">
        <h1 class="font-serif text-2xl">Planes</h1>
        <p class="text-sm text-muted">Precio y duración de lo que ofreces a las ópticas. Los cambios aplican a las compras nuevas; las licencias ya aprobadas no cambian.</p>
    </div>

    <div class="grid md:grid-cols-2 2xl:grid-cols-4 gap-5">
        @foreach ($planes as $plan)
            <form method="POST" action="{{ route('superadmin.planes.update', $plan->id_plan) }}"
                  class="bg-white rounded-2xl border shadow-card p-5 sm:p-6 flex flex-col {{ $plan->destacado ? 'border-olive-300 ring-1 ring-olive-300' : 'border-olive-100' }}">
                @csrf
                @method('PUT')

                <div class="flex items-center justify-between mb-5">
                    <span class="text-xs text-muted tabular-nums">{{ $plan->licencias_activas }} {{ $plan->licencias_activas === 1 ? 'licencia activa' : 'licencias activas' }}</span>
                    @if ($plan->es_prueba)
                        <span class="text-[10px] font-semibold uppercase text-sky-700 bg-sky-50 rounded px-1.5 py-0.5">Prueba gratis</span>
                    @elseif ($plan->destacado)
                        <span class="text-[10px] font-semibold uppercase text-olive-700 bg-olive-50 rounded px-1.5 py-0.5">Más popular</span>
                    @endif
                </div>

                <div class="space-y-4 flex-1">
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Nombre</label>
                        <input type="text" name="nombre" value="{{ $plan->nombre }}" class="{{ $campo }}" required>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium mb-1.5">Meses</label>
                            <input type="number" name="meses" min="1" max="60" value="{{ $plan->meses }}" class="{{ $campo }}" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1.5">Precio</label>
                            <input type="number" name="precio" min="0" step="1" value="{{ (int) $plan->precio }}"
                                   class="{{ $campo }} {{ $plan->es_prueba ? 'text-muted' : '' }}" @readonly($plan->es_prueba) required>
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="activo" value="1" @checked($plan->activo) class="rounded border-olive-300 text-olive-600">
                        Se vende (aparece para las ópticas)
                    </label>
                    @unless ($plan->es_prueba)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="destacado" value="1" @checked($plan->destacado) class="rounded border-olive-300 text-olive-600">
                            Marcar como «Más popular»
                        </label>
                    @endunless
                </div>

                <button type="submit" class="mt-6 w-full bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-5 py-2.5 rounded-xl">
                    Guardar plan
                </button>
            </form>
        @endforeach
    </div>

@endsection
