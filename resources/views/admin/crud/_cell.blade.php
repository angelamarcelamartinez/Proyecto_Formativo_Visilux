{{--
    Muestra el valor de una columna de forma legible.
    Variables: $col, $row, $fkMaps, $detail (true = dentro del cuadro "Ver más")
--}}
@php
    $detail = $detail ?? false;
    $val = $row->{$col['name']} ?? null;
    $name = $col['name'];
    $isMoney = collect(['precio', 'total', 'subtotal', 'impuesto', 'descuento'])->contains(fn ($h) => str_contains($name, $h));
@endphp

@if (is_null($val) || $val === '')
    <span class="text-muted">—</span>
@elseif ($col['type'] === 'file')
    @php
        $src = \Illuminate\Support\Str::startsWith($val, ['http://', 'https://', '/'])
            ? $val
            : asset(($col['upload_dir'] ?? 'assets/img/productos').'/'.$val);
    @endphp
    <img src="{{ $src }}" alt="{{ $col['label'] }}"
         class="{{ $detail ? 'rounded-xl border border-olive-100 max-h-56 object-contain' : 'w-10 h-10 rounded-lg object-cover border border-olive-100' }}"
         onerror="this.replaceWith(Object.assign(document.createElement('span'), {className: 'text-muted text-xs', textContent: 'Sin imagen'}))">
@elseif (!empty($col['is_fk']) && empty($col['list_raw']))
    @if (isset($fkMaps[$name][(string) $val]))
        <span class="text-ink/90">{{ $fkMaps[$name][(string) $val] }}</span>
    @else
        {{-- El registro relacionado ya no existe (dato huérfano en la base de datos) --}}
        <span class="text-red-600" title="Este valor no existe en {{ $col['fk_table'] }}">{{ $val }} <small>(no registrado)</small></span>
    @endif
@elseif ($col['type'] === 'decimal' && $isMoney)
    <span class="font-medium">${{ number_format((float) $val, 2) }}</span>
@elseif ($col['type'] === 'time')
    {{ substr($val, 0, 5) }}
@elseif ($col['type'] === 'datetime-local')
    {{ substr($val, 0, 16) }}
@elseif ($col['type'] === 'date')
    {{ substr($val, 0, 10) }}
@elseif ($col['type'] === 'textarea' && ! $detail)
    <span class="block truncate" title="{{ $val }}">{{ \Illuminate\Support\Str::limit($val, 40) }}</span>
@elseif (!empty($col['is_pk']) && ! $detail)
    <span class="font-mono text-[12px] text-ink/70">{{ $val }}</span>
@else
    <span class="{{ $detail ? 'whitespace-pre-line break-words' : '' }}">{{ $val }}</span>
@endif
