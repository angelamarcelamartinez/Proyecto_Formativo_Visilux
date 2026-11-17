<div class="px-6 py-14 text-center">
    <div class="mx-auto w-14 h-14 rounded-2xl bg-creamdark text-olive-600 flex items-center justify-center mb-4">
        @include('partials.icon', ['name' => $icono ?? 'calendar', 'class' => 'w-6 h-6'])
    </div>
    <p class="font-serif text-lg">{{ $titulo }}</p>
    @isset($texto)<p class="text-sm text-muted mt-1">{{ $texto }}</p>@endisset
</div>
