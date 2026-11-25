# Base de datos VisiÓptica: cómo dejarla al día

## 1. Importar (phpMyAdmin)

1. **Copia de seguridad primero:** seleccione su base → *Exportar* → *Continuar*.
2. **Lo más simple (recomendado):** cree una base **vacía** (o vacíe la actual), selecciónela e importe **`visilux_corregido.sql`**. Trae todos los datos y todas las correcciones; no hace falta ningún otro script. Sirve en XAMPP y en Hostinger (sin procedimientos, sin eventos, sin DEFINER).
3. Si prefiere actualizar su base sin vaciarla, importe los scripts que le falten, **en este orden**:

| Script | Qué hace |
|---|---|
| `visioptica_Corregido_sin_P10.sql` | Correcciones de la primera auditoría (sin P-10) |
| `2026_11_24_modulo_optometra.sql` | Rol 7, tabla del simulador, triggers clínicos |
| `2026_11_24_tabla_mensajes_contacto.sql` | Tabla de mensajes de contacto |
| `2026_11_24_mejoras_auditoria.sql` | Reglas del documento por tipo y demás mejoras |
| `2026_11_25_optometra_cuenta_propia.sql` | El optómetra con su cuenta en su propia tabla |
| `2026_11_25_triggers_simplificados.sql` | Deja solo los 8 triggers |
| `2026_11_25_auditoria_final.sql` | 3FN, tipos, fechas de auditoría, índices, AUTO_INCREMENT |

`visioptica_final.sql` trae los 7 en un solo archivo. Si su base se quedó en el error #1644 del `visioptica_Corregido.sql` original, importe `continuar_despues_del_error.sql`.

**No importe** el `visioptica_Corregido.sql` original: su parte P-10 borra `nit_empresa` de citas, horarios y ventas, y la aplicación deja de funcionar (error 500).

## 2. Documento según su tipo

Las reglas están en la tabla `tipo_documento` (columnas `patron`, `long_min`, `long_max`, `solo_numeros`, `ayuda`) y las aplica Laravel (`App\Rules\DocumentoValido`) en todos los formularios.

| Tipo | Regla |
|---|---|
| Cédula de ciudadanía | solo números, 6 a 10 dígitos |
| Tarjeta de identidad | solo números, 10 u 11 dígitos |
| Cédula de extranjería | solo números, 6 a 10 dígitos |
| Pasaporte | letras y números, 6 a 12 caracteres |
| NIT | solo números, 9 o 10 dígitos (con o sin dígito de verificación) |
| Registro civil (NUIP) | solo números, 10 u 11 dígitos |

La cédula colombiana tiene máximo 10 dígitos (las antiguas tienen 6 a 8), así que se dejó en 6 a 10 y no en 11. Los documentos de 11 dígitos son la tarjeta de identidad y el registro civil.

Antes de guardar, el documento se limpia: `1.020.304.050` → `1020304050` y `ab 12-345` → `AB12345`. Para cambiar una regla basta un `UPDATE tipo_documento ...`.

## 3. Triggers (solo 8)

Se dejaron solo los triggers necesarios y fáciles de explicar. Lo demás lo valida Laravel: el documento según su tipo, los rangos de la fórmula, los turnos libres (sin doble reserva), la cita cancelada, la óptica de cada registro, que un documento o correo no se repita entre `usuario` y `optometra`, y la bitácora de cambios de historias y fórmulas (`log_historia_clinica`).

| Trigger | Tabla | Qué hace |
|---|---|---|
| `trg_log_acceso` | usuario | Cada ingreso de un usuario queda en `log_acceso` |
| `trg_usuario_cambio_estado` | usuario | Activar o inactivar un usuario queda en `log_estado_usuario` |
| `trg_optometra_bitacora` | optometra | Lo mismo que los dos anteriores, para el optómetra |
| `trg_cita_cambio_estado` | asignacion_cita | Cada cambio de estado de una cita queda en `log_cambio_cita` |
| `trg_producto_cambio_precio` | producto | Precio anterior y nuevo quedan en `log_precio_producto` |
| `trg_lote_stock_bajo` | lote | Cuando el stock llega al mínimo se crea una fila en `alerta_stock` |
| `trg_venta_descuenta_stock` | detalle_venta_producto | Al vender, descuenta la cantidad del lote más antiguo que tenga suficientes unidades; si ninguno alcanza, la venta se rechaza (un producto sin lotes se vende sin descontar) |
| `trg_historia_clinica_ai` | historia_clinica | Al guardar la consulta, la cita queda en "Completado" |

No hay procedimientos almacenados ni eventos programados.

## 4. Seguridad, respaldos y limpieza (criterios 7, 8 y 10)

**Usuario de la aplicación (mínimo privilegio).** La app no debe conectarse como `root`.
- **Hostinger:** hPanel → *Bases de datos MySQL* crea la base y su usuario; ese usuario solo tiene permisos sobre esa base. Póngalo en `.env` (`DB_USERNAME`, `DB_PASSWORD`).
- **XAMPP:** en phpMyAdmin → SQL (con root, una sola vez):
  ```sql
  CREATE USER 'visilux_app'@'localhost' IDENTIFIED BY 'una-clave-larga';
  GRANT SELECT, INSERT, UPDATE, DELETE ON visilux.* TO 'visilux_app'@'localhost';
  ```
  Para importar scripts o correr migraciones se usa otro usuario con permisos de estructura (`ALTER`, `CREATE`, `DROP`, `INDEX`, `TRIGGER`), no el de la app.

**Contraseñas.** Todas están cifradas con bcrypt (`usuario.password`, `optometra.contrasena`). Los 5 usuarios de prueba (hramirezjuanita, angelamarcelamartinezruiz, andres.rodriguez, valentina.gomez y adsosenasena, todos @gmail.com) comparten la misma contraseña de demostración: cámbienla antes de publicar el sistema.

**Copias de seguridad.**
- **Qué:** respaldo completo diario. Desde la terminal: `mysqldump -u <usuario> -p --triggers visilux > respaldo_AAAA-MM-DD.sql`. En Hostinger: hPanel → *Copias de seguridad* (diarias automáticas) + una descarga manual semanal.
- **RPO** (cuánto se puede perder): 24 horas. **RTO** (cuánto se tarda en recuperar): 1 hora, importando el último respaldo en una base vacía.
- **Prueba:** una vez al mes, restaurar el último respaldo en una base de prueba y abrir la app contra ella.
- **Retención:** respaldos de los últimos 30 días + uno por mes. Las historias clínicas se conservan mínimo 15 años (Res. 839 de 2017): nunca se borran.

**Limpieza manual (una vez al año).** No hay eventos programados. Las sesiones y la caché las limpia Laravel solo. Las bitácoras viejas se borran a mano:
```sql
DELETE FROM log_acceso   WHERE fecha_hora < NOW() - INTERVAL 2 YEAR;
DELETE FROM alerta_stock WHERE fecha_hora < NOW() - INTERVAL 1 YEAR;
```
`log_historia_clinica`, `historia_clinica` y `formula_medica` no se purgan.

**Versión del motor.** MariaDB 10.4 ya no recibe parches: en producción use la que ofrezca Hostinger (10.11 o superior). Este proyecto se probó en 10.11 y el SQL no usa nada que 10.4 no tenga.

**Documentación.** `database/docs/DICCIONARIO_DATOS.md` (todas las tablas y columnas), `database/docs/DIAGRAMA_ER.md` (diagrama entidad-relación) y `database/docs/DECISIONES_DISENO.md` (por qué se hizo cada cosa).

## 5. El optómetra (desde el 25-11-2026)

- Toda su información y su cuenta están **solo** en la tabla `optometra`: documento, nombre, celular, correo, contraseña (bcrypt), estado, óptica, tarjeta profesional e `id_especialidad` (el nombre de la especialidad se lee de la tabla `especialidad`). El optómetra **no** tiene fila en `usuario`.
- **Login:** se busca el correo en `usuario`; si no está, en `optometra`; si tampoco está, la persona no tiene cuenta.
- `contrasena = NULL` significa que el optómetra está registrado (con horarios y citas) pero aún no tiene cuenta. El administrador se la crea en **Equipo → Nuevo empleado → Optómetra → "¿Ya está en la lista?"**.
- Sus acciones en la bitácora quedan en `actividad.doc_optometra` (en `actividad.documento` quedan las de los usuarios).
- **Catalina Esquivel (50001001)** quedó registrada **sin cuenta** (`correo` y `contrasena` en NULL) y conserva sus horarios y citas. Para darle acceso: el administrador entra a **Equipo → Nuevo empleado**, rol Optómetra, la elige en "¿Ya está en la lista?", escribe su cédula, su correo y su tarjeta profesional, y le llega al correo una contraseña temporal que debe cambiar al entrar. No hay contraseñas escritas en el SQL.
