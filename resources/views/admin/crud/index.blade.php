@extends('layouts.admin')

@section('title', $config['label_plural'])
@section('breadcrumb', $config['group_label'] . ' / ' . $config['label_plural'])

@section('content')

    @php
        $exportParams = request()->except(['page', 'partial']);
    @endphp

    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-olive-100 text-olive-700 flex items-center justify-center">
                @include('partials.icon', ['name' => $config['icon'], 'class' => 'w-5 h-5'])
            </div>
            <div>
                <h1 class="font-serif text-2xl">{{ $config['label_plural'] }}</h1>
                <p class="text-sm text-muted"><span id="crud-total">{{ $rows->total() }}</span> registro(s) encontrados</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            @if (!empty($config['export']))
                <a id="crud-export" href="{{ route('admin.crud.export', [$table] + $exportParams) }}"
                   data-base="{{ route('admin.crud.export', $table) }}"
                   class="inline-flex items-center gap-2 bg-white border border-olive-200 hover:bg-olive-50 transition text-olive-700 text-sm font-medium px-4 py-2.5 rounded-xl shrink-0"
                   title="Descargar en Excel lo que estás viendo (con la búsqueda y los filtros aplicados)">
                    @include('partials.icon', ['name' => 'download', 'class' => 'w-4 h-4'])
                    Excel
                </a>
            @endif
            @unless (\App\Support\Alcance::soloLectura($table))
            <a href="{{ route('admin.crud.create', $table) }}"
               class="inline-flex items-center gap-2 bg-olive-600 hover:bg-olive-700 transition text-cream text-sm font-medium px-4 py-2.5 rounded-xl shadow-card shrink-0">
                @include('partials.icon', ['name' => 'plus', 'class' => 'w-4 h-4'])
                Nuevo
            </a>
            @endunless
        </div>
    </div>

    {{-- Búsqueda y filtros: se aplican mientras escribes, sin presionar Enter --}}
    @if (!empty($config['search_columns']) || !empty($filters))
        <form id="crud-filtros" method="GET" action="{{ route('admin.crud.index', $table) }}"
              onsubmit="return false;"
              class="bg-white rounded-2xl border border-olive-100 shadow-card p-4 mb-6 flex flex-wrap items-end gap-3">
            @if (!empty($config['search_columns']))
                <div class="relative flex-1 min-w-[220px]">
                    <label class="block text-[11px] uppercase tracking-wider text-muted mb-1" for="crud-q">Buscar</label>
                    <span class="absolute bottom-0 left-3 h-[42px] flex items-center text-muted pointer-events-none">
                        @include('partials.icon', ['name' => 'search', 'class' => 'w-4 h-4'])
                    </span>
                    <input id="crud-q" type="search" name="q" value="{{ $q }}" autocomplete="off"
                           placeholder="Escribe para buscar en {{ mb_strtolower($config['label_plural']) }}..."
                           class="w-full bg-creamdark/40 border border-olive-100 rounded-xl py-2.5 pl-9 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-olive-300">
                </div>
            @endif

            @foreach ($filters as $filter)
                <div class="min-w-[170px]">
                    <label class="block text-[11px] uppercase tracking-wider text-muted mb-1" for="f_{{ $filter['name'] }}">{{ $filter['label'] }}</label>
                    @if ($filter['kind'] === 'date')
                        <input id="f_{{ $filter['name'] }}" type="date" name="f_{{ $filter['name'] }}" value="{{ request('f_'.$filter['name']) }}"
                               class="w-full bg-creamdark/40 border border-olive-100 rounded-xl py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-olive-300">
                    @else
                        <select id="f_{{ $filter['name'] }}" name="f_{{ $filter['name'] }}"
                                class="w-full bg-creamdark/40 border border-olive-100 rounded-xl py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-olive-300">
                            <option value="">Todos</option>
                            @foreach ($filter['options'] as $value => $label)
                                <option value="{{ $value }}" @selected((string) request('f_'.$filter['name']) === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
            @endforeach

            <button type="button" id="crud-limpiar"
                    class="text-sm text-muted hover:text-ink px-3 py-2.5">
                Limpiar
            </button>
        </form>
    @endif

    @if (!empty($reportStats))
        <div class="grid sm:grid-cols-3 gap-4 mb-6">
            @foreach ($reportStats as $stat)
                <div class="bg-white rounded-2xl border border-olive-100 shadow-card px-5 py-4">
                    <p class="font-serif text-2xl">{{ $stat['value'] }}</p>
                    <p class="text-xs text-muted mt-1">{{ $stat['label'] }}</p>
                </div>
            @endforeach
        </div>
    @endif

    @if (!empty($reportCharts))
        @php
            $paleta = ['#7E6427', '#C8AD6C', '#EFE2C4', '#4A3A18', '#93762E', '#F7F1E1'];
        @endphp
        <div class="space-y-6 mb-6">
            @foreach ($reportCharts as $i => $chart)
                @php
                    $canvasId = 'chart-'.$table.'-'.$i;
                    $suma = array_sum($chart['values']) ?: 1;
                @endphp
                <div class="bg-white rounded-2xl border border-olive-100 shadow-card p-6">
                    <h3 class="font-serif text-lg">{{ $chart['title'] }}</h3>
                    <p class="text-xs text-muted mb-5">{{ $chart['subtitle'] }}</p>

                    @if (empty($chart['values']) || $suma === 0 && $chart['type'] !== 'line')
                        <p class="text-sm text-muted">No hay datos suficientes todavía para esta gráfica.</p>
                    @elseif ($chart['type'] === 'donut')
                        <div class="flex flex-col sm:flex-row sm:items-center gap-8">
                            <div class="w-44 h-44 mx-auto sm:mx-0 shrink-0">
                                <canvas id="{{ $canvasId }}"></canvas>
                            </div>
                            <div class="flex-1 w-full space-y-3">
                                @foreach ($chart['labels'] as $idx => $label)
                                    <div class="flex items-center justify-between text-sm">
                                        <div class="flex items-center gap-2 text-ink/90">
                                            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $paleta[$idx % count($paleta)] }}"></span>
                                            {{ $label }}
                                        </div>
                                        <span class="font-medium">{{ round(($chart['values'][$idx] / $suma) * 100) }}%</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="h-64">
                            <canvas id="{{ $canvasId }}"></canvas>
                        </div>
                    @endif
                </div>

                @if (!empty($chart['values']))
                    @push('scripts')
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                const ctx = document.getElementById('{{ $canvasId }}');
                                if (!ctx || typeof Chart === 'undefined') return;

                                const labels = @json($chart['labels']);
                                const values = @json($chart['values']);
                                const paleta = @json($paleta);

                                @if ($chart['type'] === 'donut')
                                    new Chart(ctx, {
                                        type: 'doughnut',
                                        data: {
                                            labels: labels,
                                            datasets: [{ data: values, backgroundColor: paleta, borderWidth: 0 }],
                                        },
                                        options: {
                                            cutout: '70%',
                                            plugins: { legend: { display: false } },
                                        },
                                    });
                                @elseif ($chart['type'] === 'bar')
                                    new Chart(ctx, {
                                        type: 'bar',
                                        data: {
                                            labels: labels,
                                            datasets: [{
                                                data: values,
                                                backgroundColor: '#C8AD6C',
                                                hoverBackgroundColor: '#7E6427',
                                                borderRadius: 6,
                                                maxBarThickness: 42,
                                            }],
                                        },
                                        options: {
                                            plugins: { legend: { display: false } },
                                            scales: {
                                                x: { grid: { display: false } },
                                                y: { beginAtZero: true, grid: { color: '#F1EBDD' } },
                                            },
                                        },
                                    });
                                @else
                                    new Chart(ctx, {
                                        type: 'line',
                                        data: {
                                            labels: labels,
                                            datasets: [{
                                                data: values,
                                                borderColor: '#7E6427',
                                                backgroundColor: 'rgba(147,118,46,0.12)',
                                                fill: true,
                                                tension: 0.35,
                                                pointRadius: 3,
                                                pointBackgroundColor: '#7E6427',
                                            }],
                                        },
                                        options: {
                                            plugins: { legend: { display: false } },
                                            scales: {
                                                x: { grid: { display: false } },
                                                y: { beginAtZero: true, grid: { color: '#F1EBDD' } },
                                            },
                                        },
                                    });
                                @endif
                            });
                        </script>
                    @endpush
                @endif
            @endforeach
        </div>
    @endif

    <div id="crud-resultados" class="transition-opacity">
        @include('admin.crud._table')
    </div>

    {{-- Cuadro emergente "Ver más" --}}
    <div id="crud-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="crud-modal-title">
        <div class="absolute inset-0 bg-ink/40 js-cerrar-modal"></div>
        <div class="relative bg-white rounded-2xl shadow-card w-full max-w-2xl max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-olive-100">
                <h2 id="crud-modal-title" class="font-serif text-xl">Detalle de {{ mb_strtolower($config['label']) }}</h2>
                <button type="button" class="p-2 rounded-lg hover:bg-olive-100 text-ink/70 js-cerrar-modal" title="Cerrar">
                    @include('partials.icon', ['name' => 'x', 'class' => 'w-5 h-5'])
                </button>
            </div>
            <div id="crud-modal-body" class="px-6 py-5 overflow-y-auto"></div>
            <div class="px-6 py-4 border-t border-olive-100 text-right">
                <button type="button" class="js-cerrar-modal text-sm font-medium px-4 py-2 rounded-xl bg-olive-600 text-cream hover:bg-olive-700">Cerrar</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('crud-filtros');
    const resultados = document.getElementById('crud-resultados');
    const total = document.getElementById('crud-total');
    const exportLink = document.getElementById('crud-export');
    const modal = document.getElementById('crud-modal');
    const modalBody = document.getElementById('crud-modal-body');
    const baseUrl = @json(route('admin.crud.index', $table));
    let timer = null;
    let controller = null;

    function paramsFromForm() {
        const params = new URLSearchParams();
        if (!form) return params;
        new FormData(form).forEach((value, key) => {
            if (String(value).trim() !== '') params.set(key, String(value).trim());
        });
        return params;
    }

    function cargar(url) {
        if (controller) controller.abort();
        controller = new AbortController();
        resultados.style.opacity = '0.5';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
            .then(r => r.text())
            .then(html => {
                resultados.innerHTML = html;
                resultados.style.opacity = '1';
                const wrapper = resultados.querySelector('[data-total]');
                if (wrapper && total) total.textContent = wrapper.dataset.total;

                const u = new URL(url, window.location.origin);
                u.searchParams.delete('partial');
                history.replaceState(null, '', u.pathname + (u.search ? u.search : ''));
                if (exportLink) {
                    const p = new URLSearchParams(u.search);
                    p.delete('page');
                    exportLink.href = exportLink.dataset.base + (p.toString() ? '?' + p.toString() : '');
                }
            })
            .catch(err => { if (err.name !== 'AbortError') resultados.style.opacity = '1'; });
    }

    function buscarAhora() {
        const params = paramsFromForm();
        cargar(baseUrl + (params.toString() ? '?' + params.toString() : ''));
    }

    if (form) {
        // Filtra con cada letra que se escribe (con una pequeña espera para no saturar el servidor)
        form.addEventListener('input', function (e) {
            if (e.target.tagName === 'SELECT' || e.target.type === 'date') return;
            clearTimeout(timer);
            timer = setTimeout(buscarAhora, 250);
        });
        form.addEventListener('change', function (e) {
            if (e.target.tagName === 'SELECT' || e.target.type === 'date') buscarAhora();
        });
        document.getElementById('crud-limpiar')?.addEventListener('click', function () {
            form.querySelectorAll('input, select').forEach(el => el.value = '');
            buscarAhora();
        });
    }

    // Paginación sin recargar la página (conserva la búsqueda y los filtros)
    resultados.addEventListener('click', function (e) {
        const link = e.target.closest('.js-paginacion a');
        if (link) {
            e.preventDefault();
            cargar(link.href);
            resultados.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }

        const btn = e.target.closest('.js-ver-mas');
        if (btn) {
            const tpl = document.getElementById(btn.dataset.detail);
            if (!tpl) return;
            modalBody.innerHTML = '';
            modalBody.appendChild(tpl.content.cloneNode(true));
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }
    });

    function cerrarModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }
    modal.querySelectorAll('.js-cerrar-modal').forEach(el => el.addEventListener('click', cerrarModal));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarModal(); });
})();
</script>
@endpush
