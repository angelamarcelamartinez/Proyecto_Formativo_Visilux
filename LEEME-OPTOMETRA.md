# Módulo del optómetra (rol 7) + simulador visual · 24/11/2026

## Instalación
1. **Base de datos.** En phpMyAdmin, pestaña SQL, ejecuta `database/sql/2026_11_24_modulo_optometra.sql`. Va después de los scripts `2026_11_23_*`.
   - Crea el rol **Optómetra** (id 7) y la tabla `simulacion_visual`.
   - Agrega las enfermedades del simulador.
   - Crea los triggers del módulo. Conviven con `trg_log_acceso`, `trg_usuario_cambio_estado` y `trg_cita_cambio_estado`.
   - Se puede ejecutar más de una vez sin problema.
2. Ejecuta `php artisan optimize:clear`.
3. **Crear al optómetra:** Admin → **Equipo** → Nuevo empleado → rol **Optómetra**. Llena la tarjeta profesional y la especialidad.
   - Si ya existe en la tabla `optometra` (por ejemplo 50001001, Catalina), créalo con el **mismo documento**. Así conserva sus horarios y citas.
   - Entra con la contraseña temporal; después de cambiarla queda en `/optometra`.

## Cómo se integra con el panel de recepción
- **Inicio:** `Usuario::rutaInicio()` ya enviaba el rol 7 a `/optometra`; ahora esa ruta existe.
- **Menús:** el menú del optómetra en `layouts/staff.blade.php` apunta a las rutas reales del módulo. El **chat con recepción** se ve dentro del consultorio, con el contador de mensajes sin leer.
- **Citas:** una cita **Confirmada** por la recepcionista aparece en la agenda del optómetra con el botón **Atender**. Al guardar la historia clínica, el trigger la deja en **Completado**.
- **Accesos:** si alguien con otro rol entra a `/optometra`, vuelve a su propio panel, igual que con el middleware `rol`.

## Triggers del módulo
| Trigger | Qué hace |
|---|---|
| `trg_usuario_optometra_ai` / `_au` | Crea o actualiza la fila en `optometra` (con `nit_empresa`) para cada usuario con rol 7. Si lo inactivan, sale de la agenda pública. |
| `trg_historia_clinica_bi` | Permite una sola historia por cita y ninguna en citas canceladas. |
| `trg_historia_clinica_ai` | Al guardar la historia, la cita pasa a **Completado**. |
| `trg_formula_medica_bi` / `_bu` | Valida los rangos de esfera, cilindro, eje, adición, DP y la fecha del próximo control. |

## Arreglos de paso, necesarios para que el proyecto funcione
- **`app/Support/ApisExternas.php`:** el archivo tenía por error el código de `ApiController`, así que la página de inicio, la nueva cita de recepción y el formulario de contratar daban error 500. Ahora tiene la clase real:
  - departamentos y ciudades (api-colombia.com);
  - índice UV (open-meteo.com);
  - festivos (date.nager.at). Si esa API no responde, los festivos se calculan con la ley colombiana.
- **`CrudController::ordenar()`:** se había borrado y todas las tablas del panel del administrador daban error. Quedó restaurada.
- **`config/admin_tables.php`:** el estado **Confirmada** se agregó a las citas.
