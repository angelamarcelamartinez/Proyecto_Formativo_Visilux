# Fusión visilux_3 + visilux-cc (2)  — 13/oct/2026

Base común: el commit `b285e02` ("correcion camara de comercio").
- De `visilux_3` (compañero): NIT como número entero (BIGINT) + validación en línea, bloqueo de cuentas por
  4 intentos fallidos con solicitud de reactivación, botón Activar/Desactivar usuarios, rediseño de login/registro/contratar/
  renovar/footer/carrito, correos de reactivación.
- De `visilux-cc (2)`: citas de 30 min/1 h y tipos de cita, panel superadmin (Licencias, Ópticas, admins, editar óptica),
  renovación de licencia con pantalla de bloqueo, licencias de 10 dígitos, correos de credenciales a empresa y admin,
  módulo Equipo (el admin crea a sus empleados), "Licencia inactiva" para los demás roles.

## Decisiones al fusionar
- La columna del certificado se queda como `camara_de_comercio` (el SQL del compañero la renombraba: se quitó ese paso).
- `/superadmin/opticas/crear` ("Registrar óptica") sigue eliminada (el compañero aún la tenía).
- El panel "Reportes" no trae "Registro de actividad" (se había quitado).
- Intentos de login: conviven los dos mecanismos (bloqueo a los 4 fallos = cuenta inactiva; límite de 4 intentos/minuto
  para correos que no existen).
- Activar/desactivar: el botón del compañero, la pantalla Equipo y el formulario del admin del superadmin usan todos
  `BloqueoUsuarios` (recuerda el estado anterior y avisa por correo).

## Instalar
1. Copiar encima del proyecto (respetando carpetas). No incluye vendor/.env.
2. phpMyAdmin, en este orden:
   1. `database/sql/2026_10_13_nit_empresa_entero.sql`   (NIT a entero; haz copia de la base antes)
   2. `database/sql/2026_10_13_renovacion_licencias.sql` (estado «inactiva» + licencias de 10 dígitos)
3. `php artisan optimize:clear`
4. Cron en Hostinger (avisos de vencimiento): `* * * * * cd /RUTA && php artisan schedule:run`
