# Contratar un plan desde /planes (registro + pago simulado)

## Instalación

1. Copia el contenido de esta carpeta encima de `visilux-cc`, reemplazando archivos.
2. En phpMyAdmin (base `optometria`, pestaña SQL) ejecuta
   `database/sql/2026_09_29_registro_desde_planes.sql`.
3. En la terminal, dentro del proyecto: `php artisan optimize:clear`
4. Revisa que el correo funcione (el `.env` ya tiene configurado Gmail en MAIL_*).

## Cómo funciona

1. En `/planes` la óptica elige un plan. Los precios salen de la tabla `plan`.
2. Llena el formulario: datos de la óptica, administrador y tarjeta.
   El plan gratis no pide tarjeta.
3. El pago es simulado. Tarjetas de prueba:
   - 4242 4242 4242 4242 → aprobado (Visa)
   - 5555 5555 5555 4444 → aprobado (Mastercard)
   - 4000 0000 0000 0002 → rechazado por el banco
   Cualquier fecha futura (MM/AA) y cualquier CVV de 3 dígitos.
   No se guarda el número de la tarjeta ni el CVV, solo la marca y los últimos 4 dígitos.
4. La óptica queda "Esperando aprobación" y aparece en el panel del superadmin,
   en "Pagos por aprobar", marcada como "Óptica nueva".
5. El superadmin pulsa "Aprobar y enviar acceso": la óptica se activa, se genera una
   contraseña y se envía al correo del administrador. Si el correo falla, la contraseña
   aparece en pantalla para entregarla de otra forma.
6. "Rechazar" borra el registro para que el NIT y el correo queden libres.
