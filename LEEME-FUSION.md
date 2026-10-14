# Fusión: licencias + cambios de citas/XLSX/recuperar contraseña

Este proyecto junta `visilux-cc_licencias` (licencias, superadmin, páginas por óptica,
contratar/renovar, correos) con `visilux-cc__4_` (turnos de citas, tipo de documento,
exportar a Excel, recuperar contraseña en español, validaciones).

## Instalación

1. Copia esta carpeta encima de tu `visilux-cc` (o úsala como proyecto nuevo).
2. En phpMyAdmin (base `optometria`, pestaña SQL) ejecuta, en este orden:
   1. `database/sql/2026_09_27_tablas_base_licencias.sql`   <- crea `licencia`, `plan`, `empresa` (arregla el error "Table 'optometria.licencia' doesn't exist")
   2. `database/sql/2026_09_28_superadmin_y_paginas.sql`
   3. `database/sql/2026_09_29_registro_desde_planes.sql`
   4. Si aún no los tenías: `2026_09_28_cambios.sql`, `2026_09_28_horarios_todos_los_dias.sql`, `2026_09_28_solo_turno_solicitudes.sql`
      (o `php artisan migrate` para las dos migraciones de `usuarios_no_registrados`)
3. `php artisan optimize:clear`

## Qué se tomó de cada versión

- De licencias: todo lo de superadmin, licencias, planes, páginas por óptica, contratar/renovar,
  bloqueo por vencimiento, correos, panel con CSS local (`panel.css`), dashboard filtrado por óptica.
- De citas (28/09): turnos y agenda (`AgendaCitas`, `/agendar-cita/horarios`), tipo de documento,
  exportar a Excel (`SimpleXlsx`, `/admin/{tabla}/exportar`), buscar paciente por documento,
  recuperar contraseña en español, CRUD y `admin_tables` nuevos, registro con tipo de documento.
- Mezclados a mano: `routes/web.php`, `SiteController`, `Usuario`, `header`, `partials/planes`
  (tarjetas desde la tabla `plan` con el diseño nuevo), `lang/es/validation.php`.
- Las citas (con sesión o de visitante) ahora guardan `nit_empresa` de la óptica que se estaba viendo.
- `panel.css` se regeneró para incluir las clases de las vistas nuevas del CRUD.

## Revisar

- `.env`: APP_URL quedó `https://visilux.myjob.solutions` (el de la versión de citas); la otra tenía `myjobs.solutions`.
- Los precios de los planes del script 2026_09_27 son los que se veían en la página (299.999 / 449.999 / 599.999
  como valor total del plan). Ajústalos en el panel del superadmin.
- Esta versión no trae `vendor`: corre `composer install` en la carpeta del proyecto (si ya tienes tu `vendor`, no hace falta).
- Se quitaron `node_modules`, `.git` y `public/hot` del zip. Con `public/hot` presente Laravel busca el servidor de Vite.

## Login del superadmin (sin botón en el sitio)

- El superadmin entra por su propio login, en una dirección que NO está enlazada en ninguna parte del sitio.
  La dirección sale de `SUPERADMIN_LOGIN_PATH` en el `.env` (por defecto: `https://tu-dominio/login/superadmin`).
  Puedes cambiarla en el `.env` (y también en el de Hostinger).
- Ese login solo deja entrar al rol superadmin (id_rol = 6). El login normal (`/login`) rechaza al superadmin
  con el mismo mensaje de "credenciales incorrectas".
- `/superadmin/...` responde 404 a quien no sea superadmin (sin sesión, paciente o admin de óptica), en vez de redirigir al login.
- El login del superadmin tiene el mismo diseño que el de los usuarios, con la insignia "Superadmin". Limita a 5 intentos por minuto por correo e IP y pide `noindex` a los buscadores.
- Después de copiar los archivos: `php artisan optimize:clear` (y `php artisan route:clear` si tenías las rutas en caché).
- No pongas esa dirección en `robots.txt`, porque la revelaría.
