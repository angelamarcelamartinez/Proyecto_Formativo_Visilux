@extends('layouts.admin')

@section('title', 'API')
@section('breadcrumb', 'API')

@section('content')
    <div class="mb-6">
        <h1 class="font-serif text-2xl">API</h1>
        <p class="text-sm text-muted">APIs externas públicas consumidas con PHP y cURL. Cada una está en su propio archivo dentro de <code>public/apis/</code> y aquí solo se muestra. Es un ejercicio independiente: no usa ni cambia los datos de la óptica.</p>
    </div>

    {{-- Pestañas: una por cada archivo de public/apis --}}
    <div class="flex flex-wrap gap-2 mb-4">
        @foreach ($apis as $i => $api)
            <button type="button" data-api="{{ $i }}"
                    class="api-tab text-sm font-medium px-4 py-2 rounded-lg border border-olive-100 transition {{ $i === 0 ? 'bg-olive-600 text-cream shadow-card' : 'bg-white hover:bg-olive-100' }}">
                {{ $i + 1 }} · {{ $api['titulo'] }}
            </button>
        @endforeach
    </div>

    @foreach ($apis as $i => $api)
        <div class="api-panel bg-white rounded-2xl border border-olive-100 shadow-card p-5 {{ $i === 0 ? '' : 'hidden' }}" data-panel="{{ $i }}">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <p class="text-xs text-olive-700">API externa: {{ $api['fuente'] }} · Archivo: public/apis/{{ $api['archivo'] }}</p>
                <a href="{{ asset('apis/'.$api['archivo']) }}" target="_blank" class="text-xs text-olive-700 underline">Abrir en pestaña nueva</a>
            </div>
            {{-- El iframe carga el archivo PHP de public/apis (se carga al abrir la pestaña) --}}
            <iframe data-src="{{ asset('apis/'.$api['archivo']) }}" title="{{ $api['titulo'] }}"
                    class="w-full rounded-lg border border-olive-100" style="min-height: 420px;"></iframe>
        </div>
    @endforeach
@endsection

@push('scripts')
<script>
(function () {
    const tabs = document.querySelectorAll('.api-tab');
    const panels = document.querySelectorAll('.api-panel');
    const activas = ['bg-olive-600', 'text-cream', 'shadow-card'];

    // Ajusta el alto del iframe al contenido de la tabla
    function ajustar(frame) {
        try { frame.style.height = (frame.contentWindow.document.body.scrollHeight + 30) + 'px'; } catch (e) {}
    }

    function mostrar(i) {
        tabs.forEach(t => {
            const activa = t.dataset.api === String(i);
            activas.forEach(c => t.classList.toggle(c, activa));
            t.classList.toggle('bg-white', !activa);
        });
        panels.forEach(p => {
            const visible = p.dataset.panel === String(i);
            p.classList.toggle('hidden', !visible);
            const frame = p.querySelector('iframe');
            if (visible && !frame.src) {
                frame.onload = () => ajustar(frame);
                frame.src = frame.dataset.src;
            }
        });
    }

    tabs.forEach(t => t.addEventListener('click', () => mostrar(t.dataset.api)));
    mostrar(0);
})();
</script>
@endpush
