@extends('layouts.staff')

@section('title', 'Chat')
@section('breadcrumb', 'Chat')

@section('content')
    <div class="mb-6">
        <h1 class="font-serif text-2xl">Chat interno</h1>
        <p class="text-sm text-muted">Conversa con {{ $rolContacto ?? 'tu equipo' }} de la óptica para coordinar citas y pacientes.</p>
    </div>

    <div class="bg-white rounded-2xl border border-olive-100 shadow-card overflow-hidden">
        @if ($contactos->isEmpty())
            @include('staff._vacio', ['icono' => 'users', 'titulo' => 'Todavía no hay con quién chatear', 'texto' => 'Cuando el administrador cree a ' . $rolContacto . ' en Equipo, aparecerán aquí.'])
        @else
            <div class="chat">
                <aside class="chat-lista">
                    @foreach ($contactos as $c)
                        <a href="{{ route('chat.index', ['con' => $c->documento]) }}"
                           class="chat-contacto {{ $actual && $actual->documento == $c->documento ? 'activo' : '' }}">
                            <span class="chat-avatar">{{ mb_strtoupper(mb_substr($c->nombres, 0, 1)) }}</span>
                            <span class="chat-contacto-texto">
                                <span class="chat-contacto-nombre">{{ $c->nombres }} {{ $c->apellido }}</span>
                                <span class="chat-contacto-ultimo">{{ $c->ultimo ? \Illuminate\Support\Str::limit($c->ultimo->mensaje, 34) : 'Sin mensajes' }}</span>
                            </span>
                            @if ($c->sinLeer > 0)<span class="chat-insignia">{{ $c->sinLeer }}</span>@endif
                        </a>
                    @endforeach
                </aside>

                <section class="chat-panel">
                    @if ($actual)
                        <header class="chat-cabecera">
                            <span class="chat-avatar">{{ mb_strtoupper(mb_substr($actual->nombres, 0, 1)) }}</span>
                            <span class="font-medium">{{ $actual->nombres }} {{ $actual->apellido }}</span>
                        </header>

                        <div id="chat-mensajes" class="chat-mensajes"
                             data-nuevos="{{ route('chat.nuevos', $actual->documento) }}"
                             data-ultimo="{{ $mensajes->where('de_documento', '!=', $yo)->max('id_chat') ?? 0 }}">
                            @forelse ($mensajes as $m)
                                <div class="chat-burbuja {{ $m->de_documento == $yo ? 'mia' : 'suya' }}">
                                    <p>{{ $m->mensaje }}</p>
                                    <span>{{ \Illuminate\Support\Carbon::parse($m->fecha)->format('d/m H:i') }}</span>
                                </div>
                            @empty
                                <p id="chat-vacio" class="chat-vacio">Aún no hay mensajes. Escribe el primero.</p>
                            @endforelse
                        </div>

                        <form id="chat-form" method="POST" action="{{ route('chat.enviar', $actual->documento) }}" class="chat-form">
                            @csrf
                            <input type="text" name="mensaje" id="chat-texto" maxlength="1000" autocomplete="off" required
                                   placeholder="Escribe un mensaje…" class="chat-entrada">
                            <button type="submit" class="chat-enviar">Enviar</button>
                        </form>
                    @endif
                </section>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/chat.js') }}"></script>
@endpush
