{{-- Calendario de citas con los festivos de Colombia (API date.nager.at, app/Support/ApisExternas.php).
     Los festivos se pintan de AMARILLO y no se pueden escoger, porque la óptica no atiende esos días.
     Usa flatpickr (copia local en public/assets/vendor/flatpickr).
     Parámetros:
       $input    → id del <input> de la fecha (por ejemplo 'n_fecha' o 'fecha')
       $festivos → ['2026-12-08' => 'Inmaculada Concepción', ...] (de la API, o de la lista local si la API no respondió)
       $fechaMin, $fechaMax → rango permitido (Y-m-d) --}}
<link rel="stylesheet" href="{{ asset('assets/vendor/flatpickr/flatpickr.min.css') }}">
<style>
    /* Día festivo: amarillo y sin poder hacer clic */
    .flatpickr-day.dia-festivo,
    .flatpickr-day.dia-festivo:hover {
        background: #fde68a;
        border-color: #f59e0b;
        color: #92400e;
        font-weight: 600;
        cursor: not-allowed;
    }
    .leyenda-festivo { display: inline-block; width: .9rem; height: .9rem; border-radius: .2rem; background: #fde68a; border: 1px solid #f59e0b; vertical-align: -2px; margin-right: .3rem; }
    /* El campo visible que crea flatpickr hereda el estilo del original; que se vea clickeable */
    input.flatpickr-input[readonly] { cursor: pointer; }
</style>

<p class="small text-muted mt-1 mb-0" style="font-size: .8rem;">
    <span class="leyenda-festivo"></span>Festivo: la óptica no atiende.
</p>
@if ($festivos === null)
    <p class="mt-1 mb-0" style="font-size: .8rem; color: #b45309;">
        No se pudieron consultar los festivos. Revisa el calendario antes de agendar.
    </p>
@elseif (\App\Support\ApisExternas::$fuenteFestivos === 'local')
    {{-- La API date.nager.at no respondió desde este equipo: se usa la lista calculada de festivos de Colombia --}}
    <p class="mt-1 mb-0" style="font-size: .8rem; color: #b45309;">
        La API de festivos no respondió; se están usando los festivos de Colombia calculados por el sistema.
    </p>
@endif

<script src="{{ asset('assets/vendor/flatpickr/flatpickr.min.js') }}"></script>
<script src="{{ asset('assets/vendor/flatpickr/es.js') }}"></script>
<script>
(function () {
    const festivos = @json($festivos ?? []);   // { 'Y-m-d': 'Nombre del festivo' }

    // Fecha de JavaScript → texto Y-m-d (igual a las claves de la API)
    function aTexto(d) {
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    flatpickr('#{{ $input }}', {
        locale: 'es',
        dateFormat: 'Y-m-d',          // lo que se envía al servidor
        altInput: true,               // lo que ve la persona
        altFormat: 'd/m/Y',
        altInputClass: '',            // el campo visible conserva las clases del original
        minDate: @json($fechaMin),
        maxDate: @json($fechaMax),
        disableMobile: true,          // usa este calendario también en el celular
        // Los festivos quedan deshabilitados (no se pueden escoger)
        disable: [function (d) { return festivos.hasOwnProperty(aTexto(d)); }],
        // ...y se pintan de amarillo con el nombre del festivo al pasar el mouse
        onDayCreate: function (_, __, ___, dia) {
            const nombre = festivos[aTexto(dia.dateObj)];
            if (nombre) {
                dia.classList.add('dia-festivo');
                dia.title = 'Festivo: ' + nombre + ' (no se atiende)';
            }
        },
    });
})();
</script>
