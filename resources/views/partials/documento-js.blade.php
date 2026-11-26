{{--
    Reglas de los tipos de documento para los formularios (salen de la tabla tipo_documento).
    - validacion-en-linea.js las toma de window.VL_TIPOS_DOC (incluir ESTE partial antes de ese script).
    - En los paneles (Tailwind) basta con poner en el input:  data-doc-tipo="#id_del_select"
      y, opcional, un elemento <p data-doc-ayuda> debajo: el campo cambia teclado, largo y ayuda
      según el tipo (cédula = solo números; pasaporte = letras y números).
--}}
<script>
    window.VL_TIPOS_DOC = @json(\App\Support\TipoDocumento::paraJs());
    (function () {
        function preparar(campo) {
            var sel = document.querySelector(campo.getAttribute('data-doc-tipo'));
            var t = window.VL_TIPOS_DOC[sel ? sel.value : '1'] || { min: 4, max: 20, numeros: false, ayuda: '' };
            if (campo.type === 'number') campo.type = 'text';
            campo.setAttribute('inputmode', t.numeros ? 'numeric' : 'text');
            campo.setAttribute('maxlength', String(t.max));
            campo.setAttribute('minlength', String(t.min));
            campo.setAttribute('pattern', t.numeros ? '[0-9]{' + t.min + ',' + t.max + '}' : '[A-Za-z0-9]{' + t.min + ',' + t.max + '}');
            campo.setAttribute('title', t.ayuda);
            limpiar(campo, t);
            var ayuda = campo.parentElement.querySelector('[data-doc-ayuda]');
            if (ayuda) ayuda.textContent = t.ayuda;
        }
        function limpiar(campo, t) {
            var v = t.numeros ? campo.value.replace(/\D/g, '') : campo.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            if (v !== campo.value) campo.value = v;
        }
        function arrancar() {
            document.querySelectorAll('input[data-doc-tipo]').forEach(function (campo) {
                var sel = document.querySelector(campo.getAttribute('data-doc-tipo'));
                preparar(campo);
                if (sel) sel.addEventListener('change', function () { preparar(campo); });
                campo.addEventListener('input', function () {
                    limpiar(campo, window.VL_TIPOS_DOC[sel ? sel.value : '1'] || { numeros: false });
                });
            });
        }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', arrancar);
        else arrancar();
    })();
</script>
