{{--
    Tabla del listado. Se usa al cargar la página y también se devuelve
    sola cuando se busca/filtra en vivo (sin recargar toda la página).
--}}
<div data-total="{{ $rows->total() }}">
    <div class="bg-white rounded-2xl border border-olive-100 shadow-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-creamdark/60 text-left text-[11px] uppercase tracking-wider text-muted">
                        @foreach ($listColumns as $col)
                            <th class="px-4 py-3 font-semibold whitespace-nowrap">{{ $col['label'] }}</th>
                        @endforeach
                        <th class="px-5 py-3 font-semibold text-right whitespace-nowrap">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-olive-100">
                    @forelse ($rows as $row)
                        @php $rowId = $row->{$config['primary_key']}; @endphp
                        <tr class="hover:bg-creamdark/30 transition">
                            @foreach ($listColumns as $col)
                                <td class="px-4 py-3 align-top max-w-[16rem]">
                                    @include('admin.crud._cell', ['col' => $col, 'row' => $row, 'fkMaps' => $fkMaps, 'detail' => false])
                                </td>
                            @endforeach
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    @if ($hasMore)
                                        <button type="button" data-detail="detail-{{ $table }}-{{ $loop->index }}"
                                                class="js-ver-mas inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-olive-700 bg-olive-50 hover:bg-olive-100"
                                                title="Ver toda la información">
                                            @include('partials.icon', ['name' => 'eye', 'class' => 'w-4 h-4'])
                                            Ver más
                                        </button>
                                    @endif
                                    <a href="{{ route('admin.crud.edit', [$table, $rowId]) }}"
                                       class="p-2 rounded-lg text-olive-600 hover:bg-olive-100" title="Editar">
                                        @include('partials.icon', ['name' => 'pencil', 'class' => 'w-4 h-4'])
                                    </a>
                                    <form method="POST" action="{{ route('admin.crud.destroy', [$table, $rowId]) }}"
                                          onsubmit="return confirm('¿Eliminar este registro? Esta acción no se puede deshacer.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 rounded-lg text-red-500 hover:bg-red-50" title="Eliminar">
                                            @include('partials.icon', ['name' => 'trash', 'class' => 'w-4 h-4'])
                                        </button>
                                    </form>
                                </div>

                                @if ($hasMore)
                                    {{-- Contenido del cuadro "Ver más" de este registro --}}
                                    <template id="detail-{{ $table }}-{{ $loop->index }}">
                                        @php
                                            $fileCols = collect($detailColumns)->filter(fn ($c) => $c['type'] === 'file');
                                            $otherCols = collect($detailColumns)->reject(fn ($c) => $c['type'] === 'file');
                                        @endphp
                                        <div class="text-left">
                                            @foreach ($fileCols as $col)
                                                <div class="mb-5 flex justify-center bg-creamdark/40 rounded-xl p-4">
                                                    @include('admin.crud._cell', ['col' => $col, 'row' => $row, 'fkMaps' => $fkMaps, 'detail' => true])
                                                </div>
                                            @endforeach
                                            <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-4">
                                                @foreach ($otherCols as $col)
                                                    <div class="{{ $col['type'] === 'textarea' ? 'sm:col-span-2' : '' }}">
                                                        <dt class="text-[11px] uppercase tracking-wider text-muted">{{ $col['label'] }}</dt>
                                                        <dd class="text-sm text-ink mt-0.5 whitespace-normal">
                                                            @include('admin.crud._cell', ['col' => $col, 'row' => $row, 'fkMaps' => $fkMaps, 'detail' => true])
                                                        </dd>
                                                    </div>
                                                @endforeach
                                            </dl>
                                        </div>
                                    </template>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($listColumns) + 1 }}" class="px-5 py-12 text-center text-muted">
                                @if ($q !== '' || collect(request()->query())->keys()->contains(fn ($k) => str_starts_with($k, 'f_') && request($k) !== null && request($k) !== ''))
                                    No se encontraron registros con esa búsqueda o filtro.
                                @else
                                    No hay registros todavía.
                                    <a href="{{ route('admin.crud.create', $table) }}" class="text-olive-600 font-medium hover:underline">Crear el primero</a>.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($rows->hasPages())
            <div class="px-5 py-4 border-t border-olive-100 js-paginacion">
                {{ $rows->links() }}
            </div>
        @endif
    </div>
</div>
