{{--
    Campos de la tarjeta para el pago simulado, con vista previa.
    Lo usan el formulario de contratar un plan y el de renovar.
--}}
<style>
    .tarjeta-vista { border-radius: 16px; padding: 22px; color: #fff; min-height: 170px;
                     background: linear-gradient(135deg, #1f3b57, #2c7a7b); box-shadow: 0 10px 24px -12px rgba(0,0,0,.45); }
    .tarjeta-vista .numero { font-size: 1.3rem; letter-spacing: 2px; font-variant-numeric: tabular-nums; }
    .tarjeta-vista .etiqueta { font-size: .65rem; opacity: .7; text-transform: uppercase; letter-spacing: 1px; }
    .aviso-simulado { background: #fff8e6; border: 1px solid #f3dca3; border-radius: 12px; font-size: .875rem; }
</style>

@php $campoTarjeta = fn ($name) => 'form-control' . ($errors->has($name) ? ' is-invalid' : ''); @endphp

<div class="aviso-simulado p-3 mb-4">
    <i class="bi bi-info-circle me-1"></i>
    <strong>Pago simulado:</strong> no se hace ningún cobro real y no guardamos el número de tu tarjeta.
    Para probar sirve cualquier número de 16 dígitos, una fecha futura (MM/AA) y cualquier CVV de 3 o 4 dígitos.
    Con <code>4000 0000 0000 0002</code> el pago sale rechazado.
</div>

<div class="row g-4 align-items-center">
    <div class="col-md-5 order-md-2">
        <div class="tarjeta-vista">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <i class="bi bi-credit-card-2-front fs-3"></i>
                <span id="vistaMarca" class="fw-semibold small">Tarjeta</span>
            </div>
            <div id="vistaNumero" class="numero mb-3">•••• •••• •••• ••••</div>
            <div class="d-flex justify-content-between">
                <div>
                    <div class="etiqueta">Titular</div>
                    <div id="vistaTitular" class="small text-truncate" style="max-width: 150px;">NOMBRE APELLIDO</div>
                </div>
                <div class="text-end">
                    <div class="etiqueta">Vence</div>
                    <div id="vistaVence" class="small">MM/AA</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-7 order-md-1">
        <div class="row g-3">
            <div class="col-12">
                <label for="tarjeta_numero" class="form-label fw-medium">Número de la tarjeta</label>
                <input type="text" inputmode="numeric" autocomplete="cc-number" id="tarjeta_numero" name="tarjeta_numero"
                       maxlength="23" placeholder="0000 0000 0000 0000" class="{{ $campoTarjeta('tarjeta_numero') }}" required>
                @error('tarjeta_numero') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
            </div>
            <div class="col-12">
                <label for="tarjeta_titular" class="form-label fw-medium">Nombre como aparece en la tarjeta</label>
                <input type="text" autocomplete="cc-name" id="tarjeta_titular" name="tarjeta_titular" value="{{ old('tarjeta_titular') }}" class="{{ $campoTarjeta('tarjeta_titular') }}" required>
                @error('tarjeta_titular') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
            </div>
            <div class="col-6">
                <label for="tarjeta_vencimiento" class="form-label fw-medium">Vencimiento</label>
                <input type="text" inputmode="numeric" autocomplete="cc-exp" id="tarjeta_vencimiento" name="tarjeta_vencimiento"
                       value="{{ old('tarjeta_vencimiento') }}" maxlength="5" placeholder="MM/AA" class="{{ $campoTarjeta('tarjeta_vencimiento') }}" required>
                @error('tarjeta_vencimiento') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
            </div>
            <div class="col-6">
                <label for="tarjeta_cvv" class="form-label fw-medium">CVV</label>
                <input type="password" inputmode="numeric" autocomplete="cc-csc" id="tarjeta_cvv" name="tarjeta_cvv"
                       maxlength="4" placeholder="123" class="{{ $campoTarjeta('tarjeta_cvv') }}" required>
                @error('tarjeta_cvv') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
</div>

<script>
    // Formato y vista previa de la tarjeta (solo visual; la validación real la hace el servidor)
    (function () {
        const num = document.getElementById('tarjeta_numero');
        const tit = document.getElementById('tarjeta_titular');
        const ven = document.getElementById('tarjeta_vencimiento');

        const marca = (n) => /^4/.test(n) ? 'Visa'
            : /^(5[1-5]|2[2-7])/.test(n) ? 'Mastercard'
            : /^3[47]/.test(n) ? 'American Express' : 'Tarjeta';

        num.addEventListener('input', () => {
            const d = num.value.replace(/\D/g, '').slice(0, 19);
            num.value = d.replace(/(.{4})/g, '$1 ').trim();
            document.getElementById('vistaNumero').textContent =
                (d + '•'.repeat(Math.max(0, 16 - d.length))).replace(/(.{4})/g, '$1 ').trim();
            document.getElementById('vistaMarca').textContent = marca(d);
        });

        tit.addEventListener('input', () => {
            document.getElementById('vistaTitular').textContent = tit.value.toUpperCase() || 'NOMBRE APELLIDO';
        });

        ven.addEventListener('input', () => {
            let d = ven.value.replace(/\D/g, '').slice(0, 4);
            if (d.length > 2) d = d.slice(0, 2) + '/' + d.slice(2);
            ven.value = d;
            document.getElementById('vistaVence').textContent = d || 'MM/AA';
        });

        document.getElementById('tarjeta_cvv').addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '').slice(0, 4);
        });

        tit.dispatchEvent(new Event('input'));
        ven.dispatchEvent(new Event('input'));
    })();
</script>
