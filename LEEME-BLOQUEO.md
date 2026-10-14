# Bloqueo al vencer la licencia y avisos por correo

## Instalación

1. Copia el contenido de esta carpeta encima de `visilux-cc`, reemplazando archivos.
2. `php artisan optimize:clear`
No hay cambios en la base de datos.

## Qué cambió

- El admin de una óptica vencida, suspendida o pendiente de pago **no puede iniciar sesión**.
  El login le explica el motivo y, si solo falta pagar, le muestra el botón "Renovar mi plan".
- Si tenía la sesión abierta cuando venció, en el siguiente clic se le cierra.
- `/renovar`: página pública para renovar con el NIT, el correo del administrador y la tarjeta
  (pago simulado). Queda en "Pagos por aprobar" del superadmin; al aprobarla le llega un
  correo de confirmación y ya puede entrar.
- Correo de aviso cuando a la licencia le quedan 30 días o menos, y otro a los 7 días o menos.
  Se envía al correo de la óptica y a sus administradores, con el botón para renovar.
- Correo de confirmación cuando el superadmin aprueba una renovación o asigna un plan.

## Cómo se envían los avisos

- A mano, desde el panel del superadmin: botón "Enviar avisos" en "Requieren atención".
- Desde la terminal: `php artisan licencias:revisar`
- Automático todos los días a las 8:00, si el servidor ejecuta cada minuto
  `php artisan schedule:run`:
  - Hostinger: hPanel → Avanzado → Cron Jobs → comando
    `cd /home/USUARIO/domains/TU-DOMINIO/public_html && php artisan schedule:run`, cada minuto.
  - Windows con XAMPP: Programador de tareas → tarea que ejecute cada día
    `C:\xampp\php\php.exe C:\ruta\visilux-cc\artisan licencias:revisar`.

## Probarlo

1. Deja una licencia a 20 días de vencer:
   `UPDATE licencia SET fecha_fin = DATE_ADD(CURDATE(), INTERVAL 20 DAY), aviso_pago_enviado = NULL WHERE nit_empresa = 'NIT' AND estado = 'activa';`
2. Pulsa "Enviar avisos" en el panel del superadmin: debe llegar el correo.
   Si lo pulsas otra vez el mismo día, no se repite.
3. Déjala vencida:
   `UPDATE licencia SET fecha_inicio = DATE_SUB(CURDATE(), INTERVAL 40 DAY), fecha_fin = DATE_SUB(CURDATE(), INTERVAL 2 DAY) WHERE nit_empresa = 'NIT' AND estado = 'activa';`
4. Intenta entrar con el admin: el login no lo deja y le ofrece renovar.
