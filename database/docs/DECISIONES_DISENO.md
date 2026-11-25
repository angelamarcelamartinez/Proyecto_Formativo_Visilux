# Decisiones de diseño de la base de datos (ADR) — VisiÓptica

Cada decisión dice qué se decidió, por qué, y qué se aceptó a cambio.

## ADR-01. El documento es la llave primaria de `usuario` y `optometra`
- **Decisión:** `usuario.documento` y `optometra.doc_optometra` son llaves naturales `VARCHAR(20)`. El resto de las tablas usa `id_<tabla>` autoincremental.
- **Por qué:** la cédula identifica a la persona en todos los procesos (citas, fórmulas, facturas). En `VARCHAR` caben pasaportes con letras y documentos de 11 dígitos.
- **A cambio:** si un documento cambia (un menor que pasa de TI a cédula), las llaves foráneas lo actualizan solas (`ON UPDATE CASCADE`).

## ADR-02. El optómetra tiene su cuenta en su propia tabla
- **Decisión:** toda la información del optómetra (datos, correo y contraseña bcrypt) está solo en `optometra`; no tiene fila en `usuario`. El login busca el correo en `usuario` y, si no está, en `optometra` (`App\Auth\DosTablasUserProvider`).
- **Por qué:** no repetir datos entre tablas (3FN) y que el optómetra tenga sus relaciones propias (especialidad, horario, citas, fórmulas).
- **A cambio:** la aplicación valida que un documento o un correo no exista en las dos tablas (registro, Equipo, recepción, contratar y panel admin).

## ADR-03. Pocos triggers (8) y sencillos
- **Decisión:** quedan 4 de bitácora (ingreso, estado de usuario, estado de cita, precio de producto), su equivalente para el optómetra, stock bajo, descuento de stock al vender y "cita Completado" al guardar la historia. No hay procedimientos almacenados ni eventos.
- **Por qué:** el equipo debe poder explicar y mantener cada trigger. Las demás reglas (documento por tipo, rangos de la fórmula, turnos libres, cita cancelada, auditoría de cambios clínicos) las valida Laravel, donde se prueban más fácil.
- **A cambio:** si alguien inserta datos directo en phpMyAdmin, esas reglas no se revisan. Por eso la aplicación se conecta con un usuario de base de datos propio y nadie trabaja en producción con phpMyAdmin.

## ADR-04. La óptica (`nit_empresa`) solo en las tablas "padre"
- **Decisión:** `nit_empresa` está en citas, horarios, ventas, pedidos, lotes, catálogos, usuarios y optómetras. Las tablas hijas (historia, fórmula, diagnóstico, carrito, envío...) se filtran por su registro padre (`App\Support\Alcance`).
- **Por qué:** evita repetir el dato y mantenerlo sincronizado con triggers.
- **A cambio:** las consultas de esas tablas hacen un JOIN o un `IN` con su padre; los índices de las llaves foráneas lo hacen rápido.
- **No se aplicó P-10** del script original `visioptica_Corregido.sql`: quitaba `nit_empresa` también de las tablas padre y la aplicación dejaba de funcionar.

## ADR-05. Borrados protegidos (`ON DELETE RESTRICT`)
- **Decisión:** no se puede borrar un catálogo (medicamento, procedimiento, empresa...) que esté en uso. Solo las bitácoras usan `SET NULL`.
- **Por qué:** un borrado en cascada podía eliminar en silencio partes de historias clínicas (DBA-03).
- **A cambio:** un registro que ya no se usa se **inactiva** (`id_estado`), no se borra.

## ADR-06. Auditoría de cambios clínicos en la aplicación
- **Decisión:** `historia_clinica` y `formula_medica` guardan `created_by` (quién la creó) y `created_at` / `updated_at`. Cada vez que el optómetra edita una historia, `ConsultaController` guarda la versión anterior en `log_historia_clinica`.
- **Por qué:** la historia clínica no se puede perder ni cambiar sin rastro (Res. 839/2017). Hacerlo en PHP es más simple de explicar que 6 triggers con JSON.

## ADR-07. Sin purga automática
- **Decisión:** no hay eventos programados (`EVENT`). Hostinger no permite activar el programador de eventos y en XAMPP viene apagado.
- **Por qué:** lo que crece rápido (sesiones, caché) lo limpia Laravel; las bitácoras se purgan a mano una vez al año con las consultas de `LEEME_BASE_DE_DATOS.md`. Lo clínico no se purga nunca (retención mínima de 15 años).

## ADR-08. Versionamiento
- **Decisión:** cada cambio de la base está en un script fechado de `database/sql/` y el último también como migración de Laravel reversible (`2026_11_25_000001_auditoria_final`, con `up` y `down`). La tabla `migrations` registra qué se aplicó.
- **Por qué:** poder reconstruir la base desde cero y deshacer el último cambio si algo falla.
