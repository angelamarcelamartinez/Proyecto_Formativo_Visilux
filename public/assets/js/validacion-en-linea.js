/*!
 * Validación en línea de los formularios públicos de VisiOptica.
 *
 * Uso:
 *   <form data-validar> ... </form>
 *   Cada campo se valida con sus atributos HTML (required, minlength, maxlength,
 *   min, max, type="email", type="date", accept) y con data-v="regla":
 *
 *     nombre        Solo letras, espacios, tildes, ñ, apóstrofo y guion (mín. 2).
 *     razon-social  Nombre de empresa: letras, números y . , & - ' (mín. 3).
 *     ciudad        Solo letras y espacios (mín. 3).
 *     email         usuario@dominio.ext
 *     celular       10 dígitos y empieza por 3              (300 123 4567)
 *     telefono      10 dígitos: celular (3…) o fijo (60…)   (601 234 5678)
 *     documento     Solo números; la longitud depende del tipo de documento
 *                   elegido en el <select> indicado en data-v-tipo="#id".
 *     nit           NIT de empresa con máscara 800000000-9: 9 dígitos,
 *                   guion y el dígito de verificación (solo el formato).
 *     nit-simple    NIT de 10 dígitos, con o sin puntos y guion (sin máscara).
 *     direccion     Debe tener al menos un número (ej. Calle 12 # 34-56).
 *     password      Mínimo 8 caracteres (o lo que diga minlength).
 *     tarjeta       16 dígitos.
 *     vencimiento   MM/AA, mes válido y que no haya pasado.
 *     cvv           3 o 4 dígitos.
 *     pdf           Archivo .pdf de máximo data-v-max-mb MB (5 por defecto).
 *     entero        Número entero (respeta min y max).
 *
 *   Extras:
 *     data-v-igual="#otroCampo"   Debe ser igual a otro campo (confirmar contraseña).
 *     data-v-max-valor="2147483647" Valor numérico máximo (documento en la base).
 *     data-v-omitir               No validar este campo con este script.
 *     data-v-sin-mensaje          Solo pinta el color; el motivo queda en el tooltip.
 *
 * Verde (is-valid) = bien. Rojo (is-invalid) = mal, con el motivo debajo.
 * Si al enviar hay campos en rojo, no se envía y se lleva al primero.
 * La validación de verdad la sigue haciendo el servidor (Laravel).
 */
(function () {
    'use strict';

    // ---------------------------------------------------------------
    // Estilos (los de Bootstrap ya pintan .is-valid / .is-invalid;
    // esto completa los grupos con ícono y los campos de login).
    // ---------------------------------------------------------------
    var css = '' +
        '.form-control.is-valid,.form-select.is-valid,.form-control.is-valid:focus,.form-select.is-valid:focus{border-color:#198754!important}' +
        '.form-control.is-invalid,.form-select.is-invalid,.form-control.is-invalid:focus,.form-select.is-invalid:focus{border-color:#dc3545!important}' +
        '.form-control.is-valid:focus,.form-select.is-valid:focus{box-shadow:0 0 0 .2rem rgba(25,135,84,.2)!important}' +
        '.form-control.is-invalid:focus,.form-select.is-invalid:focus{box-shadow:0 0 0 .2rem rgba(220,53,69,.2)!important}' +
        '.input-group:has(.is-valid) .input-group-text{border-color:#198754!important;color:#198754}' +
        '.input-group:has(.is-invalid) .input-group-text{border-color:#dc3545!important;color:#dc3545}' +
        '.input-group:has(.is-valid) .input-group-text i,.input-group:has(.is-invalid) .input-group-text i{color:inherit!important}' +
        '.form-check-input.is-valid{border-color:#198754}.form-check-input.is-invalid{border-color:#dc3545}' +
        '.vl-feedback{font-size:.825rem;margin-top:.25rem}.vl-feedback:empty{display:none}';
    var estilo = document.createElement('style');
    estilo.textContent = css;
    document.head.appendChild(estilo);

    // ---------------------------------------------------------------
    // NIT: 9 dígitos + guion + 1 dígito de verificación (solo el formato)
    // ---------------------------------------------------------------
    function soloDigitos(v) { return String(v || '').replace(/\D/g, ''); }

    // "8000000009" → "800000000-9" (se va armando mientras se escribe)
    function formatearNit(digitos) {
        digitos = digitos.slice(0, 10);
        var base = digitos.slice(0, 9);
        var dv = digitos.slice(9);
        return dv ? base + '-' + dv : base;
    }

    // Aplica un formato conservando la posición del cursor (cuenta los dígitos antes del cursor)
    function aplicarMascara(campo, formatear) {
        var pos = campo.selectionStart == null ? campo.value.length : campo.selectionStart;
        var digitosAntes = soloDigitos(campo.value.slice(0, pos)).length;
        var nuevo = formatear(soloDigitos(campo.value));
        if (nuevo === campo.value) return;
        campo.value = nuevo;
        var p = 0, vistos = 0;
        while (p < nuevo.length && vistos < digitosAntes) {
            if (/\d/.test(nuevo[p])) vistos++;
            p++;
        }
        try { campo.setSelectionRange(p, p); } catch (e) { /* algunos tipos no lo permiten */ }
    }

    // ---------------------------------------------------------------
    // Documentos de identidad (Registraduría / Migración Colombia)
    //   1 Cédula de ciudadanía   6 a 10 dígitos
    //   2 Tarjeta de identidad   10 u 11 dígitos
    //   3 Cédula de extranjería  6 a 10 dígitos
    //   4 Pasaporte              5 a 15 dígitos (el sistema solo guarda números)
    //   5 NIT                    9 o 10 dígitos
    // ---------------------------------------------------------------
    var TIPOS_DOC = {
        '1': { min: 6, max: 10, nombre: 'La cédula' },
        '2': { min: 10, max: 11, nombre: 'La tarjeta de identidad' },
        '3': { min: 6, max: 10, nombre: 'La cédula de extranjería' },
        '4': { min: 5, max: 15, nombre: 'El pasaporte' },
        '5': { min: 9, max: 10, nombre: 'El NIT' }
    };

    var LETRAS = "A-Za-zÁÉÍÓÚÜÑáéíóúüñ";
    var RE_NOMBRE = new RegExp('^[' + LETRAS + "]+(?:[ '\\-.][" + LETRAS + ']+)*\\.?$');
    var RE_CIUDAD = new RegExp('^[' + LETRAS + "]+(?:[ .\\-][" + LETRAS + ']+)*\\.?$');
    var RE_RAZON = new RegExp('^[' + LETRAS + "0-9][" + LETRAS + "0-9 .,&'\\-()]*$");
    var RE_EMAIL = /^[A-Za-z0-9._%+\-]+@[A-Za-z0-9\-]+(?:\.[A-Za-z0-9\-]+)*\.[A-Za-z]{2,}$/;

    // Reglas que solo admiten números mientras se escribe
    var SOLO_NUMEROS = ['celular', 'telefono', 'documento', 'cvv', 'entero'];

    function reglas(campo) {
        return (campo.getAttribute('data-v') || '').split(/\s+/).filter(Boolean);
    }

    function nombreArchivoValido(nombre) { return /\.pdf$/i.test(nombre || ''); }

    /**
     * Devuelve null si está bien o el texto del error.
     * Para campos opcionales vacíos devuelve undefined (sin color).
     */
    function validar(campo) {
        var tipo = (campo.type || '').toLowerCase();
        var r = reglas(campo);
        var valor = tipo === 'file' ? '' : String(campo.value || '').trim();
        var obligatorio = campo.required;

        // Casillas
        if (tipo === 'checkbox') {
            if (!obligatorio) return undefined;
            return campo.checked ? null : 'Debes marcar esta casilla para continuar.';
        }

        // Archivos
        if (tipo === 'file') {
            var archivo = campo.files && campo.files[0];
            if (!archivo) return obligatorio ? 'Adjunta el archivo.' : undefined;
            if (r.indexOf('pdf') !== -1) {
                if (!nombreArchivoValido(archivo.name) || (archivo.type && archivo.type !== 'application/pdf')) {
                    return 'El archivo debe ser un PDF.';
                }
                var maxMb = parseFloat(campo.getAttribute('data-v-max-mb') || '5');
                if (archivo.size > maxMb * 1024 * 1024) return 'El PDF pesa más de ' + maxMb + ' MB.';
            }
            return null;
        }

        // Vacío
        if (valor === '') {
            if (!obligatorio) return undefined;
            return campo.tagName === 'SELECT' ? 'Selecciona una opción.' : 'Este campo es obligatorio.';
        }

        if (campo.tagName === 'SELECT') return null;

        // Largo
        var minL = parseInt(campo.getAttribute('minlength') || '0', 10);
        var maxL = parseInt(campo.getAttribute('maxlength') || '0', 10);

        var d = soloDigitos(valor);

        for (var i = 0; i < r.length; i++) {
            switch (r[i]) {
                case 'nombre':
                    if (valor.length < 2) return 'Escribe al menos 2 letras.';
                    if (!RE_NOMBRE.test(valor)) return 'Usa solo letras y espacios (sin números ni símbolos).';
                    break;
                case 'razon-social':
                    if (valor.length < 3) return 'Escribe al menos 3 caracteres.';
                    if (!RE_RAZON.test(valor)) return 'Usa letras, números y los signos . , & - \' ( ).';
                    break;
                case 'ciudad':
                    if (valor.length < 3) return 'Escribe el nombre completo de la ciudad.';
                    if (!RE_CIUDAD.test(valor)) return 'La ciudad solo lleva letras y espacios.';
                    break;
                case 'email':
                    if (!RE_EMAIL.test(valor) || valor.indexOf('..') !== -1) return 'Escribe un correo válido, por ejemplo nombre@correo.com.';
                    break;
                case 'celular':
                    if (!/^\d+$/.test(valor)) return 'Escribe solo números.';
                    if (d.length !== 10) return 'El celular debe tener 10 dígitos (llevas ' + d.length + ').';
                    if (d.charAt(0) !== '3') return 'Un celular en Colombia empieza por 3 (ej. 300 123 4567).';
                    break;
                case 'telefono':
                    if (!/^\d+$/.test(valor)) return 'Escribe solo números.';
                    if (d.length !== 10) return 'El teléfono debe tener 10 dígitos (llevas ' + d.length + ').';
                    if (!/^(3\d{9}|60\d{8})$/.test(d)) return 'Debe ser un celular (empieza por 3) o un fijo con indicativo (empieza por 60, ej. 601 234 5678).';
                    break;
                case 'documento': {
                    if (!/^\d+$/.test(valor)) return 'El documento lleva solo números, sin puntos ni espacios.';
                    var sel = campo.getAttribute('data-v-tipo') ? document.querySelector(campo.getAttribute('data-v-tipo')) : null;
                    var t = TIPOS_DOC[sel ? sel.value : '1'] || { min: 5, max: 15, nombre: 'El documento' };
                    if (d.charAt(0) === '0') return 'El documento no puede empezar por 0.';
                    if (d.length < t.min || d.length > t.max) {
                        return t.nombre + ' debe tener ' + (t.min === t.max ? t.min : 'entre ' + t.min + ' y ' + t.max) + ' dígitos.';
                    }
                    break;
                }
                case 'nit': {
                    if (/[^\d.\-\s]/.test(valor)) return 'El NIT lleva solo números.';
                    if (d.length < 9) return 'El NIT tiene 9 dígitos y el dígito de verificación (ej. 800197268-4).';
                    if (d.length === 9) return 'Falta el dígito de verificación (el número después del guion).';
                    if (d.length > 10) return 'El NIT tiene máximo 10 dígitos.';
                    break;
                }
                case 'nit-simple':
                    if (/[^\d.\-\s]/.test(valor)) return 'El NIT lleva solo números.';
                    if (d.length !== 10) return 'Escribe el NIT completo: 9 dígitos y el dígito de verificación (10 en total).';
                    break;
                case 'direccion':
                    if (valor.length < 5 || !/\d/.test(valor)) return 'Escribe la dirección completa, por ejemplo Calle 12 # 34-56.';
                    break;
                case 'password':
                    var minP = minL || 8;
                    if (valor.length < minP) return 'La contraseña debe tener al menos ' + minP + ' caracteres (llevas ' + valor.length + ').';
                    if (/\s/.test(valor)) return 'La contraseña no puede tener espacios.';
                    break;
                case 'tarjeta':
                    if (/[^\d\s]/.test(valor)) return 'El número de la tarjeta lleva solo números.';
                    if (d.length !== 16) return 'El número de la tarjeta debe tener 16 dígitos (llevas ' + d.length + ').';
                    break;
                case 'vencimiento': {
                    var m = /^(\d{2})\/(\d{2})$/.exec(valor);
                    if (!m) return 'Escribe la fecha como MM/AA (ej. 08/29).';
                    var mes = parseInt(m[1], 10), anio = 2000 + parseInt(m[2], 10);
                    if (mes < 1 || mes > 12) return 'El mes debe estar entre 01 y 12.';
                    var hoy = new Date();
                    var finMes = new Date(anio, mes, 0, 23, 59, 59);
                    if (finMes < hoy) return 'La tarjeta está vencida.';
                    if (anio > hoy.getFullYear() + 20) return 'Revisa el año de vencimiento.';
                    break;
                }
                case 'cvv':
                    if (!/^\d{3,4}$/.test(valor)) return 'El CVV tiene 3 dígitos (4 en American Express).';
                    break;
                case 'entero':
                    if (!/^-?\d+$/.test(valor)) return 'Escribe un número entero.';
                    break;
            }
        }

        // Atributos HTML genéricos
        if (tipo === 'email' && r.indexOf('email') === -1 && !RE_EMAIL.test(valor)) {
            return 'Escribe un correo válido, por ejemplo nombre@correo.com.';
        }
        if (minL && r.indexOf('password') === -1 && valor.length < minL) return 'Escribe al menos ' + minL + ' caracteres (llevas ' + valor.length + ').';
        if (maxL && valor.length > maxL) return 'Máximo ' + maxL + ' caracteres.';

        if (tipo === 'date') {
            if (!/^\d{4}-\d{2}-\d{2}$/.test(valor)) return 'Escribe una fecha válida.';
            if (campo.min && valor < campo.min) return 'La fecha no puede ser antes del ' + fechaBonita(campo.min) + '.';
            if (campo.max && valor > campo.max) return 'La fecha no puede ser después del ' + fechaBonita(campo.max) + '.';
        }

        if (tipo === 'number' || r.indexOf('entero') !== -1) {
            var n = Number(valor);
            if (isNaN(n)) return 'Escribe un número.';
            if (campo.min !== '' && campo.min != null && n < Number(campo.min)) return 'El valor mínimo es ' + campo.min + '.';
            if (campo.max !== '' && campo.max != null && n > Number(campo.max)) return 'El valor máximo es ' + campo.max + '.';
        }

        var maxValor = campo.getAttribute('data-v-max-valor');
        if (maxValor && d && Number(d) > Number(maxValor)) return 'El número es demasiado largo.';

        var igual = campo.getAttribute('data-v-igual');
        if (igual) {
            var otro = document.querySelector(igual);
            if (otro && otro.value !== campo.value) return 'Las contraseñas no coinciden.';
        }

        return null;
    }

    function fechaBonita(iso) {
        var p = iso.split('-');
        return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : iso;
    }

    // ---------------------------------------------------------------
    // Pintar el campo
    // ---------------------------------------------------------------
    function contenedorMensaje(campo) {
        if (campo._vlFeedback) return campo._vlFeedback;
        var fb = document.createElement('div');
        fb.className = 'vl-feedback';
        fb.setAttribute('aria-live', 'polite');
        if (campo.id) fb.id = campo.id + '-vl';

        var ancla = campo.closest('.input-group') || (campo.type === 'checkbox' ? campo.closest('.form-check') : null) || campo;
        if (campo.type === 'checkbox' && ancla !== campo) {
            ancla.appendChild(fb);
        } else {
            ancla.insertAdjacentElement('afterend', fb);
        }
        campo._vlFeedback = fb;
        return fb;
    }

    // Quita los mensajes de error que puso el servidor cuando la persona corrige el campo
    function ocultarErrorServidor(campo) {
        var caja = campo.closest('.mb-3, .mb-4, [class*="col-"], .form-check') || campo.parentElement;
        if (!caja) return;
        caja.querySelectorAll('.invalid-feedback').forEach(function (e) { e.classList.add('d-none'); e.classList.remove('d-block'); });
    }

    function pintar(campo, error) {
        // Campos pequeños (boletín, cantidad del carrito): solo color, el motivo va en el tooltip
        if (campo.hasAttribute('data-v-sin-mensaje')) {
            campo.classList.remove('is-valid', 'is-invalid');
            campo.removeAttribute('title');
            if (error === undefined) return true;
            campo.classList.add(error === null ? 'is-valid' : 'is-invalid');
            campo.setAttribute('aria-invalid', error === null ? 'false' : 'true');
            if (error) campo.title = error;
            return error === null;
        }
        var fb = contenedorMensaje(campo);
        campo.classList.remove('is-valid', 'is-invalid');
        fb.classList.remove('text-success', 'text-danger');
        fb.textContent = '';

        if (error === undefined) {           // opcional y vacío: sin color
            campo.removeAttribute('aria-invalid');
            return true;
        }
        if (error === null) {
            campo.classList.add('is-valid');
            campo.setAttribute('aria-invalid', 'false');
            return true;
        }
        campo.classList.add('is-invalid');
        campo.setAttribute('aria-invalid', 'true');
        fb.classList.add('text-danger');
        fb.textContent = error;
        if (fb.id) campo.setAttribute('aria-describedby', fb.id);
        return false;
    }

    function revisar(campo) {
        if (!esValidable(campo)) return true;
        return pintar(campo, validar(campo));
    }

    function esValidable(campo) {
        if (campo.disabled || campo.hasAttribute('data-v-omitir')) return false;
        var tipo = (campo.type || '').toLowerCase();
        if (['hidden', 'submit', 'button', 'reset', 'radio', 'image'].indexOf(tipo) !== -1) return false;
        // Sin reglas y sin restricciones: no hay nada que validar
        return campo.required || campo.hasAttribute('data-v') || campo.hasAttribute('minlength') ||
            campo.hasAttribute('maxlength') || campo.hasAttribute('data-v-igual') ||
            tipo === 'email' || tipo === 'date' || tipo === 'number';
    }

    // ---------------------------------------------------------------
    // Conectar cada formulario
    // ---------------------------------------------------------------
    function iniciar(form) {
        if (form._vlListo) return;
        form._vlListo = true;

        var campos = Array.prototype.slice.call(form.querySelectorAll('input, select, textarea'));

        campos.forEach(function (campo) {
            var r = reglas(campo);
            var tieneErrorServidor = campo.classList.contains('is-invalid');

            // Máscara del NIT: 800000000-9
            if (r.indexOf('nit') !== -1) {
                campo.setAttribute('inputmode', 'numeric');
                campo.setAttribute('maxlength', '11');
                campo.setAttribute('autocomplete', 'off');
                if (campo.value) campo.value = formatearNit(soloDigitos(campo.value));
                campo.addEventListener('input', function () { aplicarMascara(campo, formatearNit); });
            }

            // Solo números
            if (r.some(function (x) { return SOLO_NUMEROS.indexOf(x) !== -1; })) {
                if (campo.type !== 'number') campo.setAttribute('inputmode', 'numeric');
                campo.addEventListener('input', function () {
                    if (campo.type === 'number') return;
                    var limpio = campo.value.replace(/\D/g, '');
                    if (limpio !== campo.value) campo.value = limpio;
                });
            }

            var evento = (campo.tagName === 'SELECT' || ['checkbox', 'file', 'date'].indexOf(campo.type) !== -1) ? 'change' : 'input';

            campo.addEventListener(evento, function () {
                ocultarErrorServidor(campo);
                // Mientras escribe solo se valida si ya salió una vez del campo o si ya tiene color
                if (evento === 'change' || campo._vlTocado || campo.classList.contains('is-valid') || campo.classList.contains('is-invalid')) {
                    revisar(campo);
                }
                // Si cambia la contraseña, se vuelve a revisar la confirmación
                campos.forEach(function (otro) {
                    var sel = otro.getAttribute('data-v-igual');
                    if (sel && campo.matches(sel) && otro.value) revisar(otro);
                    var tipoSel = otro.getAttribute('data-v-tipo');
                    if (tipoSel && campo.matches(tipoSel) && otro.value) revisar(otro);
                });
            });

            campo.addEventListener('blur', function () {
                if (!esValidable(campo)) return;
                // Si solo pasó por el campo sin escribir nada, no se marca en rojo todavía
                if (!campo._vlTocado && campo.value === '' && evento === 'input') return;
                campo._vlTocado = true;
                revisar(campo);
            });

            // Al cargar (por ejemplo cuando el servidor devolvió el formulario con errores)
            // se pintan en verde los campos que ya traen un valor correcto.
            if (!tieneErrorServidor && esValidable(campo) && campo.type !== 'file' && campo.type !== 'checkbox' &&
                campo.tagName !== 'SELECT' && campo.value !== '' && campo.type !== 'password') {
                campo._vlTocado = true;
                revisar(campo);
            }
        });

        // Al enviar: si hay errores no se envía y se lleva al primer campo en rojo
        form.addEventListener('submit', function (e) {
            var primero = null;
            campos.forEach(function (campo) {
                if (!esValidable(campo)) return;
                campo._vlTocado = true;
                if (!revisar(campo) && !primero) primero = campo;
            });
            if (primero) {
                e.preventDefault();
                e.stopImmediatePropagation();
                primero.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(function () { try { primero.focus({ preventScroll: true }); } catch (x) { primero.focus(); } }, 300);
            }
        }, true);
    }

    function arrancar() {
        document.querySelectorAll('form[data-validar]').forEach(iniciar);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', arrancar);
    else arrancar();

    // Para usarlo desde otras páginas si hace falta
    window.ValidacionEnLinea = { iniciar: iniciar, revisar: revisar, nit: { formatear: formatearNit } };
})();
