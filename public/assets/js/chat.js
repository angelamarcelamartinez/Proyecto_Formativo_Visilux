/* Chat interno: envía sin recargar y consulta mensajes nuevos cada 4 segundos. */
(function () {
    var caja = document.getElementById('chat-mensajes');
    var form = document.getElementById('chat-form');
    if (!caja || !form) return;

    var texto = document.getElementById('chat-texto');
    var ultimo = parseInt(caja.dataset.ultimo || '0', 10);
    var token = form.querySelector('input[name=_token]').value;

    function bajar() { caja.scrollTop = caja.scrollHeight; }

    function pintar(mensaje, hora, mio) {
        var vacio = document.getElementById('chat-vacio');
        if (vacio) vacio.remove();
        var b = document.createElement('div');
        b.className = 'chat-burbuja ' + (mio ? 'mia' : 'suya');
        var p = document.createElement('p');
        p.textContent = mensaje; // textContent: nunca se interpreta como HTML
        var s = document.createElement('span');
        s.textContent = hora;
        b.appendChild(p);
        b.appendChild(s);
        caja.appendChild(b);
        bajar();
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var valor = texto.value.trim();
        if (!valor) return;
        texto.value = '';
        fetch(form.action, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ mensaje: valor })
        }).then(function (r) { return r.ok ? r.json() : Promise.reject(); })
          .then(function (d) { pintar(d.mensaje, d.hora, true); })
          .catch(function () { texto.value = valor; });
    });

    function consultar() {
        fetch(caja.dataset.nuevos + '?despues=' + ultimo, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : []; })
            .then(function (lista) {
                lista.forEach(function (m) { ultimo = Math.max(ultimo, m.id); pintar(m.mensaje, m.hora, false); });
            })
            .catch(function () {});
    }

    bajar();
    setInterval(consultar, 4000);
})();
