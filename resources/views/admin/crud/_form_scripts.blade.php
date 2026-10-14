<script>
(function () {
    // ---------- Buscar paciente por documento y completar sus datos ----------
    document.querySelectorAll('.js-lookup').forEach(function (box) {
        const input = box.querySelector('.js-lookup-input');
        const status = box.querySelector('.js-lookup-status');
        const fields = box.querySelectorAll('[data-field]');
        let timer = null;
        let controller = null;

        function limpiar() {
            fields.forEach(f => f.value = '');
        }

        function buscar() {
            const doc = input.value.trim();
            if (!doc) {
                limpiar();
                status.textContent = 'Escribe el documento del paciente y sus datos se completan solos.';
                status.className = 'js-lookup-status text-[12px] text-muted';
                return;
            }
            if (!/^\d+$/.test(doc)) {
                limpiar();
                status.textContent = 'El documento solo debe tener números.';
                status.className = 'js-lookup-status text-[12px] text-red-600';
                return;
            }

            if (controller) controller.abort();
            controller = new AbortController();
            status.textContent = 'Buscando...';
            status.className = 'js-lookup-status text-[12px] text-muted';

            fetch(box.dataset.url + '?documento=' + encodeURIComponent(doc), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                signal: controller.signal,
            })
                .then(r => r.json())
                .then(data => {
                    if (!data.found) {
                        limpiar();
                        status.textContent = 'No hay ningún usuario registrado con ese documento. Regístralo primero en Usuarios.';
                        status.className = 'js-lookup-status text-[12px] text-red-600';
                        return;
                    }
                    fields.forEach(f => f.value = data[f.dataset.field] ?? '');
                    status.textContent = 'Paciente encontrado ✓';
                    status.className = 'js-lookup-status text-[12px] text-green-700 font-medium';
                })
                .catch(err => {
                    if (err.name === 'AbortError') return;
                    status.textContent = 'No se pudo consultar el documento. Intenta de nuevo.';
                    status.className = 'js-lookup-status text-[12px] text-red-600';
                });
        }

        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(buscar, 350);
        });

        // Al editar, se cargan de una vez los datos del paciente actual
        if (input.value.trim()) buscar();
    });

    // ---------- Botón de subir archivo con vista previa ----------
    document.querySelectorAll('.js-archivo').forEach(function (box) {
        const input = box.querySelector('.js-archivo-input');
        const preview = box.querySelector('.js-archivo-preview');
        const vacio = box.querySelector('.js-archivo-vacio');
        const nombre = box.querySelector('.js-archivo-nombre');

        input.addEventListener('change', function () {
            const file = input.files && input.files[0];
            if (!file) return;
            nombre.textContent = 'Seleccionado: ' + file.name;
            const reader = new FileReader();
            reader.onload = e => {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                vacio.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        });
    });
})();
</script>
