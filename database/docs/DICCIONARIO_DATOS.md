# Diccionario de datos — VisiÓptica

Generado desde la base `visilux_corregido.sql` (25-11-2026). 69 tablas y 1 vista (`v_estado_licencias`).

Convenciones: tablas y columnas en minúscula con guion bajo; llave primaria `id_<tabla>` (autoincremental) salvo las llaves naturales `usuario.documento`, `optometra.doc_optometra`, `empresa.nit` y `transportadora.nit`. Todas las tablas son InnoDB, utf8mb4_general_ci.

Columnas: **PK** llave primaria, **UK** única, **FK** llave foránea, **IX** índice.

## Índice

[actividad](#actividad), [agendamiento_procedimiento](#agendamiento_procedimiento), [alerta_stock](#alerta_stock), [asignacion_cita](#asignacion_cita), [cache](#cache), [cache_locks](#cache_locks), [carrito](#carrito), [categoria_producto](#categoria_producto), [categoria_servicio](#categoria_servicio), [ciudad](#ciudad), [compra_online](#compra_online), [departamento](#departamento), [detalle_carrito_producto](#detalle_carrito_producto), [detalle_formula_medica_medicamento](#detalle_formula_medica_medicamento), [detalle_formula_medica_procedimiento](#detalle_formula_medica_procedimiento), [detalle_pedido_producto](#detalle_pedido_producto), [detalle_productos](#detalle_productos), [detalle_prov_prod](#detalle_prov_prod), [detalle_venta_medicamento](#detalle_venta_medicamento), [detalle_venta_producto](#detalle_venta_producto), [diagnostico](#diagnostico), [empresa](#empresa), [enfermedades](#enfermedades), [envio](#envio), [especialidad](#especialidad), [estado](#estado), [evolucion_terapia](#evolucion_terapia), [failed_jobs](#failed_jobs), [formula_medica](#formula_medica), [historia_clinica](#historia_clinica), [horario](#horario), [jobs](#jobs), [job_batches](#job_batches), [licencia](#licencia), [log_acceso](#log_acceso), [log_cambio_cita](#log_cambio_cita), [log_estado_usuario](#log_estado_usuario), [log_historia_clinica](#log_historia_clinica), [log_precio_producto](#log_precio_producto), [lote](#lote), [marca](#marca), [medicamento](#medicamento), [mensajes_contacto](#mensajes_contacto), [metodo_compra](#metodo_compra), [migrations](#migrations), [modelo](#modelo), [motivos_cita](#motivos_cita), [optometra](#optometra), [paciente](#paciente), [pagina_empresa](#pagina_empresa), [password_reset_tokens](#password_reset_tokens), [pedido](#pedido), [plan](#plan), [procedimiento](#procedimiento), [producto](#producto), [proveedor](#proveedor), [rol](#rol), [seguimiento_envio](#seguimiento_envio), [sessions](#sessions), [simulacion_visual](#simulacion_visual), [talla](#talla), [tipo_cita](#tipo_cita), [tipo_documento](#tipo_documento), [tipo_producto](#tipo_producto), [tipo_terapia](#tipo_terapia), [transportadora](#transportadora), [usuario](#usuario), [venta](#venta), [venta_medicamento](#venta_medicamento)

## actividad

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_actividad` | bigint(20) | no | PK (auto) |  |  |
| `nit_empresa` | bigint(20) unsigned | sí | IX FK | NULL | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |
| `documento` | varchar(20) | sí | IX FK | NULL | → usuario.documento (borrar: SET NULL, actualizar: CASCADE) Usuario que hizo la acción |
| `doc_optometra` | varchar(20) | sí | IX FK | NULL | → optometra.doc_optometra (borrar: SET NULL, actualizar: CASCADE) Optómetra que hizo la acción (si no fue un usuario) |
| `accion` | varchar(30) | no |  |  | login / crear / editar / eliminar / licencia |
| `tabla` | varchar(60) | sí |  | NULL |  |
| `id_registro` | varchar(60) | sí |  | NULL |  |
| `descripcion` | varchar(255) | sí |  | NULL |  |
| `fecha` | datetime | no |  | current_timestamp() |  |

## agendamiento_procedimiento

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_agendamiento_procedimiento` | int(11) | no | PK (auto) |  |  |
| `fecha_inicio` | datetime | no |  |  |  |
| `fecha_final` | datetime | sí |  | NULL |  |
| `id_tipo_terapia` | int(11) | no | IX FK |  | → tipo_terapia.id_tipo_terapia (borrar: RESTRICT, actualizar: RESTRICT) |
| `id_formula` | int(11) | sí | IX FK | NULL | → formula_medica.id_formula (borrar: RESTRICT, actualizar: RESTRICT) |
| `tipo` | enum('examen visual','terapia visual','adaptacion lentes contacto') | no |  | 'terapia visual' |  |
| `estado` | varchar(50) | sí |  | NULL |  |
| `id_historia_clinica` | int(11) | sí | IX FK | NULL | → historia_clinica.id_historia_clinica (borrar: RESTRICT, actualizar: RESTRICT) |

Reglas CHECK: `chk_agenda_fechas: `fecha_final` is null or `fecha_final` >= `fecha_inicio``

## alerta_stock

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_alerta` | int(11) | no | PK (auto) |  |  |
| `id_lote` | int(11) | no | IX |  |  |
| `id_producto` | int(11) | no |  |  |  |
| `producto` | varchar(255) | sí |  | NULL |  |
| `stock_actual` | int(11) | no |  |  |  |
| `stock_minimo` | int(11) | no |  |  |  |
| `nit_empresa` | bigint(20) unsigned | sí | IX | NULL |  |
| `fecha_hora` | datetime | no |  | current_timestamp() |  |

## asignacion_cita

Agenda: paciente + optómetra + fecha/hora. La app solo ofrece turnos libres. Triggers: trg_cita_cambio_estado (bitácora).

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_cita` | int(11) | no | PK (auto) |  |  |
| `fecha_cita` | datetime | no |  |  |  |
| `hora_cita` | time | no |  |  |  |
| `observaciones` | text | sí |  | NULL |  |
| `id_usuario` | varchar(20) | no | IX FK |  | → usuario.documento (borrar: RESTRICT, actualizar: CASCADE) |
| `id_optometra` | varchar(20) | no | IX FK |  | → optometra.doc_optometra (borrar: RESTRICT, actualizar: CASCADE) |
| `id_tipo_cita` | int(11) | no | IX FK |  | → tipo_cita.id_tipo_cita (borrar: RESTRICT, actualizar: RESTRICT) |
| `id_estado` | int(11) | no | IX FK |  | → estado.id_estado (borrar: RESTRICT, actualizar: RESTRICT) |
| `id_motivo` | int(11) | sí | IX FK | NULL | → motivos_cita.id_motivo (borrar: RESTRICT, actualizar: RESTRICT) |
| `nit_empresa` | bigint(20) unsigned | sí | IX FK | NULL | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |
| `created_at` | datetime | no |  | current_timestamp() | Cuándo se agendó |
| `updated_at` | datetime | sí |  | NULL (se actualiza sola) | Última modificación |

Triggers: `trg_cita_cambio_estado (AFTER UPDATE)`

## cache

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `key` | varchar(255) | no | PK |  |  |
| `value` | mediumtext | no |  |  |  |
| `expiration` | int(11) | no | IX |  |  |

## cache_locks

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `key` | varchar(255) | no | PK |  |  |
| `owner` | varchar(255) | no |  |  |  |
| `expiration` | int(11) | no | IX |  |  |

## carrito

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_carrito` | int(11) | no | PK (auto) |  |  |
| `fecha_actual` | date | no |  |  |  |
| `id_usuario` | varchar(20) | no | IX FK |  | → usuario.documento (borrar: RESTRICT, actualizar: CASCADE) |
| `id_estado` | int(11) | no | IX FK |  | → estado.id_estado (borrar: RESTRICT, actualizar: RESTRICT) |

## categoria_producto

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_categoria` | int(11) | no | PK (auto) |  |  |
| `nombre_categoria` | varchar(100) | no |  |  |  |
| `id_estado` | int(11) | no | IX FK | 1 | → estado.id_estado (borrar: RESTRICT, actualizar: RESTRICT) |
| `nit_empresa` | bigint(20) unsigned | sí | IX FK | NULL | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |

## categoria_servicio

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_categoria_servicio` | int(11) | no | PK (auto) |  |  |
| `nombre_categoria` | varchar(100) | no |  |  |  |
| `descripcion` | text | sí |  | NULL |  |
| `nit_empresa` | bigint(20) unsigned | sí | IX FK | NULL | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |

## ciudad

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_ciudad` | int(11) | no | PK (auto) |  |  |
| `nombre_ciudad` | varchar(100) | no |  |  |  |
| `id_departamento` | int(11) | no | IX FK |  | → departamento.id_departamento (borrar: RESTRICT, actualizar: RESTRICT) |

## compra_online

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_compra_on` | int(11) | no | PK (auto) |  |  |
| `imagen_formula` | varchar(255) | sí |  | NULL |  |
| `fecha_venta` | date | no |  |  |  |
| `impuestos` | decimal(10,2) | sí |  | 0.00 |  |
| `descuento` | decimal(10,2) | sí |  | 0.00 |  |
| `total` | decimal(10,2) | no |  |  |  |
| `observaciones` | varchar(255) | sí |  | NULL |  |
| `id_usuario` | varchar(20) | no | IX FK |  | → usuario.documento (borrar: RESTRICT, actualizar: CASCADE) |
| `id_estado` | int(11) | no | IX FK |  | → estado.id_estado (borrar: RESTRICT, actualizar: RESTRICT) |
| `id_metodo_compra` | int(11) | sí | IX FK | NULL | → metodo_compra.id_metodo (borrar: RESTRICT, actualizar: RESTRICT) |

## departamento

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_departamento` | int(11) | no | PK (auto) |  |  |
| `nom_departamento` | varchar(100) | no |  |  |  |

## detalle_carrito_producto

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_detalle_carr_pro` | int(11) | no | PK (auto) |  |  |
| `cantidad` | int(11) | no |  |  |  |
| `precio_unitario` | decimal(10,2) | no |  |  |  |
| `subtotal` | decimal(10,2) | no |  |  |  |
| `id_carrito` | int(11) | no | IX FK |  | → carrito.id_carrito (borrar: RESTRICT, actualizar: RESTRICT) |
| `id_producto` | int(11) | no | IX FK |  | → producto.id_producto (borrar: RESTRICT, actualizar: RESTRICT) |

Reglas CHECK: `chk_dcp_subtotal: `subtotal` = `cantidad` * `precio_unitario``

## detalle_formula_medica_medicamento

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_detalle` | int(11) | no | PK (auto) |  |  |
| `id_formula` | int(11) | sí | IX FK | NULL | → formula_medica.id_formula (borrar: RESTRICT, actualizar: CASCADE) |
| `id_medicamento` | int(11) | sí | IX FK | NULL | → medicamento.id_medicamento (borrar: RESTRICT, actualizar: CASCADE) |
| `cantidad` | int(11) | sí |  | NULL |  |
| `dosis` | text | sí |  | NULL |  |

## detalle_formula_medica_procedimiento

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_detalle` | int(11) | no | PK (auto) |  |  |
| `id_formula` | int(11) | sí | IX FK | NULL | → formula_medica.id_formula (borrar: RESTRICT, actualizar: CASCADE) |
| `id_procedimiento` | int(11) | sí | IX FK | NULL | → procedimiento.id_procedimiento (borrar: RESTRICT, actualizar: CASCADE) |
| `cantidad` | int(11) | sí |  | NULL |  |

## detalle_pedido_producto

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_detalle_pe_pro` | int(11) | no | PK (auto) |  |  |
| `cantidad` | int(11) | no |  |  |  |
| `precio_unitario` | decimal(10,2) | no |  |  |  |
| `subtotal` | decimal(10,2) | no |  |  |  |
| `id_producto` | int(11) | no | IX FK |  | → producto.id_producto (borrar: RESTRICT, actualizar: RESTRICT) |
| `id_pedido` | int(11) | no | IX FK |  | → pedido.id_pedido (borrar: RESTRICT, actualizar: RESTRICT) |
| `id_proveedor_producto` | int(11) | sí | IX FK | NULL | → detalle_prov_prod.id_proveedor_producto (borrar: RESTRICT, actualizar: RESTRICT) |

## detalle_productos

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_detalle_producto` | int(11) | no | PK (auto) |  |  |
| `id_producto` | int(11) | no | IX FK |  | → producto.id_producto (borrar: RESTRICT, actualizar: RESTRICT) |
| `cantidad` | int(11) | no |  |  |  |
| `precio_unitario` | decimal(10,2) | no |  |  |  |
| `id_venta` | int(11) | sí | IX FK | NULL | → venta.id_venta (borrar: RESTRICT, actualizar: RESTRICT) |

## detalle_prov_prod

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_proveedor_producto` | int(11) | no | PK (auto) |  |  |
| `precio_ref` | decimal(10,2) | no |  |  |  |
| `tiempo_entrega` | int(11) | no |  |  |  |
| `nit_prov` | varchar(20) | no | IX FK |  | → proveedor.nit (borrar: RESTRICT, actualizar: RESTRICT) |
| `id_producto` | int(11) | no | IX FK |  | → producto.id_producto (borrar: RESTRICT, actualizar: RESTRICT) |

## detalle_venta_medicamento

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_detalle_venta_med` | int(11) | no | PK (auto) |  |  |
| `id_venta_med` | int(11) | no | IX FK |  | → venta_medicamento.id_venta_med (borrar: RESTRICT, actualizar: RESTRICT) |
| `id_medicamento` | int(11) | no | IX FK |  | → medicamento.id_medicamento (borrar: RESTRICT, actualizar: RESTRICT) |
| `cantidad` | int(11) | no |  |  |  |
| `precio_unitario` | decimal(10,2) | no |  |  |  |
| `dosis` | text | sí |  | NULL |  |

## detalle_venta_producto

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_detalle_venta_producto` | int(11) | no | PK (auto) |  |  |
| `cantidad` | int(11) | no |  |  |  |
| `subtotal` | decimal(10,2) | no |  |  |  |
| `id_venta` | int(11) | no | IX FK |  | → venta.id_venta (borrar: RESTRICT, actualizar: RESTRICT) |
| `id_producto` | int(11) | no | IX FK |  | → producto.id_producto (borrar: RESTRICT, actualizar: RESTRICT) |

Reglas CHECK: `chk_dvp_cantidad: `cantidad` > 0`

Triggers: `trg_venta_descuenta_stock (AFTER INSERT)`

## diagnostico

Diagnósticos CIE-10 de la consulta, uno por ojo (1:N con historia_clinica).

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_diagnostico` | int(11) | no | PK (auto) |  |  |
| `id_enfermedad` | int(11) | sí | IX FK | NULL | → enfermedades.id_enfermedad (borrar: RESTRICT, actualizar: RESTRICT) |
| `id_formula` | int(11) | sí | IX FK | NULL | → formula_medica.id_formula (borrar: SET NULL, actualizar: CASCADE) |
| `id_historia_clinica` | int(11) | sí | IX FK | NULL | → historia_clinica.id_historia_clinica (borrar: RESTRICT, actualizar: CASCADE) |
| `ojo` | varchar(2) | no |  |  |  |

Reglas CHECK: `chk_ojo: `ojo` in ('OD','OI','AO')`

## empresa

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `nit` | bigint(20) unsigned | no | PK |  | NIT sin guion: 9 dígitos + dígito de verificación |
| `nombre` | varchar(150) | no |  |  |  |
| `slug` | varchar(80) | sí | UK | NULL |  |
| `email` | varchar(150) | sí |  | NULL |  |
| `telefono` | varchar(30) | sí |  | NULL |  |
| `direccion` | varchar(200) | sí |  | NULL |  |
| `departamento` | varchar(100) | sí |  | NULL |  |
| `ciudad` | varchar(100) | sí |  | NULL |  |
| `camara_comercio` | varchar(500) | no |  |  | Ruta del PDF de cámara de comercio |
| `estado` | enum('activa','suspendida','pendiente') | no |  | 'activa' | pendiente = registrada desde /planes, esperando aprobación del pago |
| `prueba_usada` | tinyint(1) | no |  | 0 |  |
| `fecha_registro` | datetime | no |  | current_timestamp() |  |

## enfermedades

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_enfermedad` | int(11) | no | PK (auto) |  |  |
| `nombre_enfer` | varchar(150) | no |  |  |  |
| `descripcion` | text | no |  |  |  |
| `codigo` | varchar(15) | no |  |  |  |
| `nit_empresa` | bigint(20) unsigned | sí | IX FK | NULL | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |

## envio

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_envio` | int(11) | no | PK (auto) |  |  |
| `id_venta` | int(11) | no | IX FK |  | → venta.id_venta (borrar: RESTRICT, actualizar: RESTRICT) |
| `direccion_envio` | varchar(255) | sí |  | NULL |  |
| `ciudad` | varchar(100) | sí |  | NULL |  |
| `fecha_envio` | date | sí |  | NULL |  |
| `id_estado` | int(11) | no | IX FK |  | → estado.id_estado (borrar: RESTRICT, actualizar: RESTRICT) |
| `nit_transportadora` | int(11) | sí | IX FK | NULL | → transportadora.nit (borrar: RESTRICT, actualizar: RESTRICT) |

## especialidad

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_especialidad` | int(11) | no | PK (auto) |  |  |
| `nombre_especialidad` | varchar(100) | no |  |  |  |
| `descripcion` | text | sí |  | NULL |  |
| `nit_empresa` | bigint(20) unsigned | sí | IX FK | NULL | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |

## estado

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_estado` | int(11) | no | PK (auto) |  |  |
| `nom_estado` | varchar(50) | no |  |  |  |
| `dominio` | enum('general','cita','venta','envio','inventario','cuenta') | no |  | 'general' |  |

## evolucion_terapia

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_evolucion` | int(11) | no | PK (auto) |  |  |
| `id_agendamiento_procedimiento` | int(11) | no | IX FK |  | → agendamiento_procedimiento.id_agendamiento_procedimiento (borrar: RESTRICT, actualizar: RESTRICT) |
| `fecha` | datetime | no |  |  |  |
| `descripcion_evolucion` | text | sí |  | NULL |  |
| `observaciones` | text | sí |  | NULL |  |

## failed_jobs

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id` | bigint(20) unsigned | no | PK (auto) |  |  |
| `uuid` | varchar(255) | no | UK |  |  |
| `connection` | text | no |  |  |  |
| `queue` | text | no |  |  |  |
| `payload` | longtext | no |  |  |  |
| `exception` | longtext | no |  |  |  |
| `failed_at` | timestamp | no |  | current_timestamp() |  |

## formula_medica

Prescripción óptica (OD/OI). Dato sensible (Ley 1581/2012). Retención mínima 15 años (Res. 839/2017). doc_optometra = profesional que firma.

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_formula` | int(11) | no | PK (auto) |  |  |
| `documento_paciente` | varchar(20) | no | IX FK |  | → usuario.documento (borrar: RESTRICT, actualizar: CASCADE) |
| `doc_optometra` | varchar(20) | sí | IX FK | NULL | → optometra.doc_optometra (borrar: RESTRICT, actualizar: CASCADE) |
| `fecha_formula` | date | sí |  | NULL |  |
| `od_esfera` | decimal(5,2) | sí |  | NULL |  |
| `od_cilindro` | decimal(5,2) | sí |  | NULL |  |
| `od_eje` | smallint(5) unsigned | sí |  | NULL |  |
| `od_adc` | decimal(5,2) | sí |  | NULL |  |
| `od_dist_pupilar` | decimal(4,1) | sí |  | NULL |  |
| `od_av_lejos` | varchar(20) | sí |  | NULL |  |
| `od_av_cerca` | varchar(20) | sí |  | NULL |  |
| `oi_esfera` | decimal(5,2) | sí |  | NULL |  |
| `oi_cilindro` | decimal(5,2) | sí |  | NULL |  |
| `oi_eje` | smallint(5) unsigned | sí |  | NULL |  |
| `oi_adc` | decimal(5,2) | sí |  | NULL |  |
| `oi_dist_pupilar` | decimal(4,1) | sí |  | NULL |  |
| `oi_av_lejos` | varchar(20) | sí |  | NULL |  |
| `oi_av_cerca` | varchar(20) | sí |  | NULL |  |
| `correccion` | varchar(50) | sí |  | NULL |  |
| `forma_uso` | varchar(50) | sí |  | NULL |  |
| `numero_dispositivos` | int(11) | sí |  | NULL |  |
| `proximo_control` | date | sí |  | NULL |  |
| `recomendaciones` | text | sí |  | NULL |  |
| `created_at` | datetime | no |  | current_timestamp() |  |
| `updated_at` | datetime | sí |  | NULL (se actualiza sola) |  |
| `created_by` | varchar(30) | sí |  | NULL |  |

## historia_clinica

Atención clínica (1 por cita). Dato sensible (Ley 1581/2012), retención mínima 15 años (Res. 839/2017). Trigger: trg_historia_clinica_ai (cita -> Completado).

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_historia_clinica` | int(11) | no | PK (auto) |  |  |
| `fecha_registro` | datetime | no |  |  |  |
| `tratamiento` | text | sí |  | NULL |  |
| `antecedentes_clinicos` | text | sí |  | NULL |  |
| `id_asignacioncita` | int(11) | no | UK FK |  | → asignacion_cita.id_cita (borrar: RESTRICT, actualizar: RESTRICT) |
| `id_formula` | int(11) | sí | IX FK | NULL | → formula_medica.id_formula (borrar: RESTRICT, actualizar: RESTRICT) |
| `recomendaciones` | text | sí |  | NULL |  |
| `id_diagnostico` | int(11) | sí | IX FK | NULL | → diagnostico.id_diagnostico (borrar: RESTRICT, actualizar: RESTRICT) |
| `created_at` | datetime | no |  | current_timestamp() |  |
| `updated_at` | datetime | sí |  | NULL (se actualiza sola) |  |
| `created_by` | varchar(30) | sí |  | NULL |  |

Triggers: `trg_historia_clinica_ai (AFTER INSERT)`

## horario

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_horario` | int(11) | no | PK (auto) |  |  |
| `dia_semana` | enum('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado') | no |  |  |  |
| `hora_inicio` | time | no |  |  |  |
| `hora_fin` | time | no |  |  |  |
| `tipo_bloque` | enum('cita','terapia') | no |  |  | cita=30min / terapia=60min |
| `estado` | enum('libre','bloqueado') | no |  | 'libre' |  |
| `motivo_bloqueo` | varchar(255) | sí |  | NULL |  |
| `id_optometra` | varchar(20) | sí | IX FK | NULL | → optometra.doc_optometra (borrar: RESTRICT, actualizar: CASCADE) |
| `nit_empresa` | bigint(20) unsigned | sí | IX FK | NULL | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |

Reglas CHECK: `chk_horario: `hora_fin` > `hora_inicio``

## jobs

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id` | bigint(20) unsigned | no | PK (auto) |  |  |
| `queue` | varchar(255) | no | IX |  |  |
| `payload` | longtext | no |  |  |  |
| `attempts` | tinyint(3) unsigned | no |  |  |  |
| `reserved_at` | int(10) unsigned | sí |  | NULL |  |
| `available_at` | int(10) unsigned | no |  |  |  |
| `created_at` | int(10) unsigned | no |  |  |  |

## job_batches

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id` | varchar(255) | no | PK |  |  |
| `name` | varchar(255) | no |  |  |  |
| `total_jobs` | int(11) | no |  |  |  |
| `pending_jobs` | int(11) | no |  |  |  |
| `failed_jobs` | int(11) | no |  |  |  |
| `failed_job_ids` | longtext | no |  |  |  |
| `options` | mediumtext | sí |  | NULL |  |
| `cancelled_at` | int(11) | sí |  | NULL |  |
| `created_at` | int(11) | no |  |  |  |
| `finished_at` | int(11) | sí |  | NULL |  |

## licencia

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_licencia` | int(11) | no | PK (auto) |  |  |
| `nit_empresa` | bigint(20) unsigned | no | IX FK |  | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |
| `id_plan` | int(11) | no | IX FK |  | → plan.id_plan (borrar: RESTRICT, actualizar: RESTRICT) |
| `estado` | enum('pendiente','activa','vencida','rechazada','cancelada') | no |  | 'pendiente' |  |
| `fecha_inicio` | date | sí |  | NULL |  |
| `fecha_fin` | date | sí |  | NULL |  |
| `valor` | decimal(12,2) | no |  | 0.00 |  |
| `referencia_pago` | varchar(100) | sí |  | NULL |  |
| `observaciones` | varchar(500) | sí |  | NULL |  |
| `fecha_solicitud` | datetime | sí |  | NULL |  |
| `fecha_aprobacion` | datetime | sí |  | NULL |  |
| `aviso_pago_enviado` | datetime | sí |  | NULL |  |

## log_acceso

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_log` | int(11) | no | PK (auto) |  |  |
| `documento` | varchar(20) | no | IX |  |  |
| `nombre` | varchar(200) | no |  |  |  |
| `nit_empresa` | bigint(20) unsigned | sí | IX | NULL |  |
| `fecha_hora` | datetime | no | IX |  |  |

## log_cambio_cita

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_log` | int(11) | no | PK (auto) |  |  |
| `id_cita` | int(11) | no | IX |  |  |
| `id_usuario` | varchar(20) | no |  |  |  |
| `estado_anterior` | int(11) | sí |  | NULL |  |
| `estado_nuevo` | int(11) | sí |  | NULL |  |
| `nit_empresa` | bigint(20) unsigned | sí |  | NULL |  |
| `fecha_hora` | datetime | no |  | current_timestamp() |  |

## log_estado_usuario

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_log` | int(11) | no | PK (auto) |  |  |
| `documento` | varchar(20) | no | IX |  |  |
| `nombre` | varchar(200) | no |  |  |  |
| `estado_anterior` | int(11) | sí |  | NULL |  |
| `estado_nuevo` | int(11) | sí |  | NULL |  |
| `nit_empresa` | bigint(20) unsigned | sí |  | NULL |  |
| `fecha_hora` | datetime | no |  | current_timestamp() |  |

## log_historia_clinica

Versión anterior de una historia o fórmula cada vez que se edita (lo guarda la app: ConsultaController). No se purga: retención 15 años.

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_log` | bigint(20) unsigned | no | PK (auto) |  |  |
| `tabla` | enum('historia_clinica','formula_medica') | no | IX |  |  |
| `id_registro` | int(11) | no |  |  |  |
| `operacion` | enum('UPDATE','DELETE') | no |  |  |  |
| `datos_anteriores` | longtext | no |  |  |  |
| `usuario` | varchar(100) | no |  |  | U:<documento> usuario / O:<documento> optometra / cuenta BD |
| `fecha_hora` | datetime | no |  | current_timestamp() |  |

Reglas CHECK: `datos_anteriores: json_valid(`datos_anteriores`)`

## log_precio_producto

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_log` | int(11) | no | PK (auto) |  |  |
| `id_producto` | int(11) | no | IX |  |  |
| `producto` | varchar(255) | sí |  | NULL |  |
| `precio_anterior` | decimal(10,2) | sí |  | NULL |  |
| `precio_nuevo` | decimal(10,2) | sí |  | NULL |  |
| `fecha_hora` | datetime | no |  | current_timestamp() |  |

## lote

Inventario por lote. Triggers: trg_venta_descuenta_stock (la venta descuenta) y trg_lote_stock_bajo (alerta).

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_lote` | int(11) | no | PK (auto) |  |  |
| `precio_compra` | decimal(10,2) | no |  |  |  |
| `precio_venta` | decimal(10,2) | no |  |  |  |
| `stock_actual` | int(11) | no |  |  |  |
| `stock_minimo` | int(11) | no |  |  |  |
| `fecha_ingreso` | date | no |  |  |  |
| `id_producto` | int(11) | no | IX FK |  | → producto.id_producto (borrar: RESTRICT, actualizar: RESTRICT) |
| `id_estado` | int(11) | no | IX FK | 1 | → estado.id_estado (borrar: RESTRICT, actualizar: RESTRICT) |
| `nit_empresa` | bigint(20) unsigned | sí | IX FK | NULL | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |
| `created_at` | datetime | no |  | current_timestamp() |  |
| `updated_at` | datetime | sí |  | NULL (se actualiza sola) |  |

Reglas CHECK: `chk_lote_stock: `stock_actual` >= 0 and `stock_minimo` >= 0`; `chk_lote_precios: `precio_compra` >= 0 and `precio_venta` >= 0`

Triggers: `trg_lote_stock_bajo (AFTER UPDATE)`

## marca

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_marca` | int(11) | no | PK (auto) |  |  |
| `nom_marca` | varchar(100) | no |  |  |  |
| `id_estado` | int(11) | no | IX FK | 1 | → estado.id_estado (borrar: RESTRICT, actualizar: RESTRICT) |
| `nit_empresa` | bigint(20) unsigned | sí | IX FK | NULL | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |

## medicamento

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_medicamento` | int(11) | no | PK (auto) |  |  |
| `nombre` | varchar(100) | no |  |  |  |
| `descripcion` | varchar(255) | sí |  | NULL |  |
| `lote_med` | int(11) | no | IX |  |  |
| `precio` | decimal(10,2) | sí |  | NULL |  |
| `cantidad` | int(11) | sí |  | NULL |  |
| `id_estado` | int(11) | no | IX FK | 1 | → estado.id_estado (borrar: RESTRICT, actualizar: RESTRICT) |
| `nit_empresa` | bigint(20) unsigned | sí | IX FK | NULL | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |

## mensajes_contacto

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_contactenos` | int(11) | no | PK (auto) |  |  |
| `nombre_contactenos` | varchar(150) | no |  |  |  |
| `correo_contactenos` | varchar(50) | no |  |  |  |
| `telefono_contactenos` | varchar(20) | sí |  | NULL |  |
| `mensaje_contactenos` | text | no |  |  |  |
| `nit_empresa` | bigint(20) unsigned | sí | IX FK | NULL | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |
| `estado_contactenos` | enum('Por contactar','Contactado') | no |  | 'Por contactar' |  |
| `fecha_contactenos` | datetime | no |  | current_timestamp() |  |

## metodo_compra

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_metodo` | int(11) | no | PK (auto) |  |  |
| `nombre_metodo` | varchar(100) | no |  |  |  |
| `descripcion` | text | sí |  | NULL |  |
| `activo` | tinyint(1) | no |  | 1 |  |
| `nit_empresa` | bigint(20) unsigned | sí | IX FK | NULL | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |

## migrations

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id` | int(10) unsigned | no | PK (auto) |  |  |
| `migration` | varchar(255) | no |  |  |  |
| `batch` | int(11) | no |  |  |  |

## modelo

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_modelo` | int(11) | no | PK (auto) |  |  |
| `nom_modelo` | varchar(100) | no |  |  |  |
| `id_estado` | int(11) | no | IX FK | 1 | → estado.id_estado (borrar: RESTRICT, actualizar: RESTRICT) |
| `nit_empresa` | bigint(20) unsigned | sí | IX FK | NULL | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |

## motivos_cita

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_motivo` | int(11) | no | PK (auto) |  |  |
| `nombre_motivo` | varchar(100) | no |  |  |  |
| `descripcion` | text | sí |  | NULL |  |
| `nit_empresa` | bigint(20) unsigned | sí | IX FK | NULL | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |

## optometra

Optómetra: toda su información y su cuenta (correo + contrasena bcrypt). No tiene fila en usuario. contrasena NULL = sin cuenta. Trigger: trg_optometra_bitacora.

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `doc_optometra` | varchar(20) | no | PK |  |  |
| `nombre` | varchar(100) | no |  |  |  |
| `apellido` | varchar(100) | no |  |  |  |
| `telefono` | varchar(20) | no |  |  |  |
| `correo` | varchar(100) | sí | UK | NULL | Correo con el que inicia sesión (único entre usuario y optometra). NULL = sin cuenta |
| `contrasena` | varchar(255) | sí |  | NULL | Contraseña de su cuenta (bcrypt). NULL = registrado sin cuenta |
| `remember_token` | varchar(100) | sí |  | NULL | Opción "recordarme" del login |
| `fecha_creacion` | datetime | no |  |  |  |
| `ultimo_acceso` | datetime | sí |  | NULL |  |
| `foto` | varchar(255) | sí |  | NULL |  |
| `tarjeta_profesional` | int(11) | sí |  | NULL |  |
| `id_tipo_docu` | int(11) | no | IX FK |  | → tipo_documento.id_tipo_docu (borrar: RESTRICT, actualizar: RESTRICT) |
| `id_estado` | int(11) | no | IX FK |  | → estado.id_estado (borrar: RESTRICT, actualizar: RESTRICT) |
| `id_especialidad` | int(11) | sí | IX FK | NULL | → especialidad.id_especialidad (borrar: RESTRICT, actualizar: RESTRICT) |
| `nit_empresa` | bigint(20) unsigned | no | IX FK |  | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |
| `updated_at` | datetime | sí |  | NULL (se actualiza sola) | Última modificación |

Triggers: `trg_optometra_bitacora (AFTER UPDATE)`

## paciente

Datos clínicos del paciente (1:1 con usuario). Reservada: la app aún no la usa.

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `documento` | varchar(20) | no | PK FK |  | → usuario.documento (borrar: RESTRICT, actualizar: CASCADE) |
| `fecha_nacimiento` | date | sí |  | NULL |  |
| `sexo` | enum('F','M','X') | sí |  | NULL |  |
| `documento_acudiente` | varchar(20) | sí | IX FK | NULL | → usuario.documento (borrar: SET NULL, actualizar: CASCADE) Obligatorio para menores de 18 años |
| `parentesco_acudiente` | varchar(40) | sí |  | NULL |  |
| `nit_empresa` | bigint(20) unsigned | sí | IX FK | NULL | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |
| `created_at` | datetime | no |  | current_timestamp() |  |
| `updated_at` | datetime | sí |  | NULL (se actualiza sola) |  |

## pagina_empresa

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `nit_empresa` | bigint(20) unsigned | no | PK FK |  | → empresa.nit (borrar: CASCADE, actualizar: CASCADE) |
| `logo` | varchar(255) | sí |  | NULL |  |
| `hero_titulo` | varchar(80) | sí |  | NULL |  |
| `hero_subtitulo` | varchar(120) | sí |  | NULL |  |
| `hero_texto` | varchar(300) | sí |  | NULL |  |
| `hero_imagen` | varchar(255) | sí |  | NULL |  |
| `stat1_valor` | varchar(20) | sí |  | NULL |  |
| `stat1_texto` | varchar(60) | sí |  | NULL |  |
| `stat2_valor` | varchar(20) | sí |  | NULL |  |
| `stat2_texto` | varchar(60) | sí |  | NULL |  |
| `stat3_valor` | varchar(20) | sí |  | NULL |  |
| `stat3_texto` | varchar(60) | sí |  | NULL |  |
| `servicios_titulo` | varchar(120) | sí |  | NULL |  |
| `servicios_subtitulo` | varchar(200) | sí |  | NULL |  |
| `serv1_titulo` | varchar(120) | sí |  | NULL |  |
| `serv1_texto` | text | sí |  | NULL |  |
| `serv1_items` | text | sí |  | NULL | Un beneficio por línea |
| `serv1_imagen` | varchar(255) | sí |  | NULL |  |
| `serv2_titulo` | varchar(120) | sí |  | NULL |  |
| `serv2_texto` | text | sí |  | NULL |  |
| `serv2_items` | text | sí |  | NULL | Un beneficio por línea |
| `serv2_imagen` | varchar(255) | sí |  | NULL |  |
| `prof_nombre` | varchar(120) | sí |  | NULL |  |
| `prof_cargo` | varchar(120) | sí |  | NULL |  |
| `prof_texto` | text | sí |  | NULL |  |
| `prof_credenciales` | text | sí |  | NULL | Una por línea: Título / Institución |
| `prof_imagen` | varchar(255) | sí |  | NULL |  |
| `porque_titulo` | varchar(120) | sí |  | NULL |  |
| `porque_subtitulo` | varchar(200) | sí |  | NULL |  |
| `ventaja1_titulo` | varchar(120) | sí |  | NULL |  |
| `ventaja1_texto` | varchar(255) | sí |  | NULL |  |
| `ventaja2_titulo` | varchar(120) | sí |  | NULL |  |
| `ventaja2_texto` | varchar(255) | sí |  | NULL |  |
| `footer_texto` | varchar(300) | sí |  | NULL |  |
| `horario` | text | sí |  | NULL | Un horario por línea |
| `telefono` | varchar(40) | sí |  | NULL |  |
| `email` | varchar(150) | sí |  | NULL |  |
| `direccion` | varchar(200) | sí |  | NULL |  |
| `instagram` | varchar(255) | sí |  | NULL |  |
| `facebook` | varchar(255) | sí |  | NULL |  |
| `actualizado` | datetime | sí |  | NULL |  |

## password_reset_tokens

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `email` | varchar(255) | no | PK |  |  |
| `token` | varchar(255) | no |  |  |  |
| `created_at` | timestamp | sí |  | NULL |  |

## pedido

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_pedido` | int(11) | no | PK (auto) |  |  |
| `fecha_pedido` | date | no |  |  |  |
| `fecha_entrega_estimada` | date | sí |  | NULL |  |
| `total_pedido` | decimal(10,2) | no |  |  |  |
| `observaciones` | text | sí |  | NULL |  |
| `id_usuario` | varchar(20) | no | IX FK |  | → usuario.documento (borrar: RESTRICT, actualizar: CASCADE) |
| `id_proveedor` | varchar(20) | no | IX FK |  | → proveedor.nit (borrar: RESTRICT, actualizar: RESTRICT) |
| `id_estado` | int(11) | no | IX FK |  | → estado.id_estado (borrar: RESTRICT, actualizar: RESTRICT) |
| `nit_empresa` | bigint(20) unsigned | sí | IX FK | NULL | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |
| `created_at` | datetime | no |  | current_timestamp() |  |
| `updated_at` | datetime | sí |  | NULL (se actualiza sola) |  |

## plan

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_plan` | int(11) | no | PK (auto) |  |  |
| `nombre` | varchar(80) | no |  |  |  |
| `meses` | int(11) | no |  |  |  |
| `precio` | decimal(12,2) | no |  | 0.00 |  |
| `es_prueba` | tinyint(1) | no |  | 0 |  |
| `destacado` | tinyint(1) | no |  | 0 |  |
| `activo` | tinyint(1) | no |  | 1 |  |

## procedimiento

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_procedimiento` | int(11) | no | PK (auto) |  |  |
| `nombre` | varchar(100) | no |  |  |  |
| `descripcion` | varchar(255) | sí |  | NULL |  |
| `id_categoria_servicio` | int(11) | sí | IX FK | NULL | → categoria_servicio.id_categoria_servicio (borrar: RESTRICT, actualizar: RESTRICT) |
| `nit_empresa` | bigint(20) unsigned | sí | IX FK | NULL | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |

## producto

Catálogo de productos. Trigger: trg_producto_cambio_precio (bitácora de precios).

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_producto` | int(11) | no | PK (auto) |  |  |
| `nombre_producto` | varchar(150) | no |  |  |  |
| `descripcion` | text | sí |  | NULL |  |
| `precio` | decimal(10,2) | sí |  | NULL |  |
| `id_marca` | int(11) | sí | IX FK | NULL | → marca.id_marca (borrar: RESTRICT, actualizar: RESTRICT) |
| `id_modelo` | int(11) | sí | IX FK | NULL | → modelo.id_modelo (borrar: RESTRICT, actualizar: RESTRICT) |
| `id_talla` | int(11) | sí | IX FK | NULL | → talla.id_talla (borrar: RESTRICT, actualizar: RESTRICT) |
| `color` | varchar(50) | sí |  | NULL |  |
| `imagen` | varchar(255) | sí |  | NULL |  |
| `fecha_vencimiento` | date | sí |  | NULL |  |
| `id_categoria` | int(11) | sí | IX FK | NULL | → categoria_producto.id_categoria (borrar: RESTRICT, actualizar: RESTRICT) |
| `id_estado` | int(11) | no | IX FK |  | → estado.id_estado (borrar: RESTRICT, actualizar: RESTRICT) |
| `id_tipo_pro` | int(11) | no | IX FK |  | → tipo_producto.id_tipo_pro (borrar: RESTRICT, actualizar: RESTRICT) |
| `nit_empresa` | bigint(20) unsigned | sí | IX FK | NULL | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |
| `created_at` | datetime | no |  | current_timestamp() |  |
| `updated_at` | datetime | sí |  | NULL (se actualiza sola) |  |

Triggers: `trg_producto_cambio_precio (AFTER UPDATE)`

## proveedor

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `nit` | varchar(20) | no | PK |  |  |
| `nombre_prov` | varchar(100) | no |  |  |  |
| `telefono` | varchar(20) | no |  |  |  |
| `correo` | varchar(100) | no |  |  |  |
| `direccion` | varchar(100) | no |  |  |  |
| `id_ciudad` | int(11) | no | IX FK |  | → ciudad.id_ciudad (borrar: RESTRICT, actualizar: RESTRICT) |
| `nit_empresa` | bigint(20) unsigned | sí | IX FK | NULL | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |

## rol

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_rol` | int(11) | no | PK (auto) |  |  |
| `nombre_rol` | varchar(50) | no |  |  |  |
| `descripcion` | text | no |  |  |  |

## seguimiento_envio

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_seguimiento_envio` | int(11) | no | PK (auto) |  |  |
| `fecha` | datetime | no |  |  |  |
| `descripcion` | text | no |  |  |  |
| `id_envio` | int(11) | no | IX FK |  | → envio.id_envio (borrar: RESTRICT, actualizar: RESTRICT) |

## sessions

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id` | varchar(255) | no | PK |  |  |
| `user_id` | varchar(20) | sí | IX | NULL |  |
| `ip_address` | varchar(45) | sí |  | NULL |  |
| `user_agent` | text | sí |  | NULL |  |
| `payload` | longtext | no |  |  |  |
| `last_activity` | int(11) | no | IX |  |  |

## simulacion_visual

Simulaciones del MSD Vision Simulator mostradas a un paciente.

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_simulacion` | int(11) | no | PK (auto) |  |  |
| `id_optometra` | varchar(20) | no | IX FK |  | → optometra.doc_optometra (borrar: RESTRICT, actualizar: CASCADE) |
| `documento_paciente` | varchar(20) | sí | IX FK | NULL | → usuario.documento (borrar: SET NULL, actualizar: CASCADE) |
| `id_historia_clinica` | int(11) | sí | IX FK | NULL | → historia_clinica.id_historia_clinica (borrar: SET NULL, actualizar: CASCADE) |
| `condicion` | varchar(20) | no |  |  | amd / cat / glc / dme / dvh / rd / myopia / hyperopia / astigmatism / presbyopia / anatomia |
| `escena` | tinyint(1) | sí |  | NULL | 1 ciudad / 2 Nueva York / 3 París / 4 playa |
| `observaciones` | varchar(255) | sí |  | NULL |  |
| `fecha` | datetime | no |  | current_timestamp() |  |

## talla

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_talla` | int(11) | no | PK (auto) |  |  |
| `talla` | varchar(20) | no |  |  |  |
| `id_estado` | int(11) | no | IX FK | 1 | → estado.id_estado (borrar: RESTRICT, actualizar: RESTRICT) |
| `nit_empresa` | bigint(20) unsigned | sí | IX FK | NULL | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |

## tipo_cita

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_tipo_cita` | int(11) | no | PK (auto) |  |  |
| `nombre_tipo` | varchar(100) | no |  |  |  |
| `desc_tipo` | text | no |  |  |  |
| `nit_empresa` | bigint(20) unsigned | sí | IX FK | NULL | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |

## tipo_documento

Tipos de documento y su regla de validación (patron, long_min, long_max, solo_numeros).

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_tipo_docu` | int(11) | no | PK (auto) |  |  |
| `nom_ti_docu` | varchar(50) | no |  |  |  |
| `patron` | varchar(60) | sí |  | NULL | Expresión regular que debe cumplir el documento (ya normalizado) |
| `long_min` | tinyint(3) unsigned | sí |  | NULL |  |
| `long_max` | tinyint(3) unsigned | sí |  | NULL |  |
| `solo_numeros` | tinyint(1) | no |  | 1 |  |
| `ayuda` | varchar(150) | sí |  | NULL | Texto que ve el usuario en el formulario |

## tipo_producto

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_tipo_pro` | int(11) | no | PK (auto) |  |  |
| `nomb_tipo` | varchar(100) | no |  |  |  |
| `descripcion` | text | no |  |  |  |
| `id_estado` | int(11) | no | IX FK | 1 | → estado.id_estado (borrar: RESTRICT, actualizar: RESTRICT) |
| `nit_empresa` | bigint(20) unsigned | sí | IX FK | NULL | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |

## tipo_terapia

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_tipo_terapia` | int(11) | no | PK (auto) |  |  |
| `nomtipo_terp` | varchar(100) | no |  |  |  |
| `descripcion` | text | no |  |  |  |
| `nit_empresa` | bigint(20) unsigned | sí | IX FK | NULL | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |

## transportadora

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `nit` | int(11) | no | PK |  |  |
| `nombre` | varchar(100) | no |  |  |  |
| `telefono` | varchar(20) | no |  |  |  |
| `correo` | varchar(100) | no |  |  |  |
| `cobertura` | text | sí |  | NULL |  |
| `id_estado` | int(11) | no | IX FK |  | → estado.id_estado (borrar: RESTRICT, actualizar: RESTRICT) |
| `nit_empresa` | bigint(20) unsigned | sí | IX FK | NULL | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |

## usuario

Cuentas del sistema (superadmin, administradores, empleados y clientes). Los optómetras están en optometra. Triggers: trg_log_acceso, trg_usuario_cambio_estado.

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `documento` | varchar(20) | no | PK |  |  |
| `nombres` | varchar(100) | no |  |  |  |
| `apellido` | varchar(100) | no |  |  |  |
| `telefono` | varchar(20) | no | UK |  |  |
| `email` | varchar(100) | no | UK |  |  |
| `password` | varchar(255) | no |  |  |  |
| `fecha_creacion` | datetime | no |  |  |  |
| `ultimo_acceso` | datetime | sí |  | NULL |  |
| `foto` | varchar(255) | sí |  | NULL |  |
| `id_rol` | int(11) | no | IX FK |  | → rol.id_rol (borrar: RESTRICT, actualizar: RESTRICT) |
| `id_tipo_docu` | int(11) | no | IX FK |  | → tipo_documento.id_tipo_docu (borrar: RESTRICT, actualizar: RESTRICT) |
| `id_estado` | int(11) | no | IX FK |  | → estado.id_estado (borrar: RESTRICT, actualizar: RESTRICT) |
| `id_ciudad` | int(11) | sí | IX FK | NULL | → ciudad.id_ciudad (borrar: RESTRICT, actualizar: RESTRICT) |
| `remember_token` | varchar(100) | sí |  | NULL |  |
| `nit_empresa` | bigint(20) unsigned | sí | IX FK | NULL | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |
| `updated_at` | datetime | sí |  | NULL (se actualiza sola) | Última modificación |

Triggers: `trg_log_acceso (AFTER UPDATE)`, `trg_usuario_cambio_estado (AFTER UPDATE)`

## venta

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_venta` | int(11) | no | PK (auto) |  |  |
| `imagen_formula` | varchar(255) | sí |  | NULL |  |
| `fecha_venta` | date | no |  |  |  |
| `impuestos` | decimal(10,2) | sí |  | 0.00 |  |
| `descuento` | decimal(10,2) | sí |  | 0.00 |  |
| `total` | decimal(10,2) | no |  |  |  |
| `observaciones` | varchar(255) | sí |  | NULL |  |
| `id_usuario` | varchar(20) | no | IX FK |  | → usuario.documento (borrar: RESTRICT, actualizar: CASCADE) |
| `id_estado` | int(11) | no | IX FK |  | → estado.id_estado (borrar: RESTRICT, actualizar: RESTRICT) |
| `id_metodo_compra` | int(11) | sí | IX FK | NULL | → metodo_compra.id_metodo (borrar: RESTRICT, actualizar: RESTRICT) |
| `nit_empresa` | bigint(20) unsigned | sí | IX FK | NULL | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |
| `created_at` | datetime | no |  | current_timestamp() |  |
| `updated_at` | datetime | sí |  | NULL (se actualiza sola) |  |

## venta_medicamento

| Columna | Tipo | Nulo | Llave | Por defecto | Relación / Descripción |
|---|---|---|---|---|---|
| `id_venta_med` | int(11) | no | PK (auto) |  |  |
| `id_usuario` | varchar(20) | no | IX FK |  | → usuario.documento (borrar: RESTRICT, actualizar: CASCADE) |
| `fecha` | datetime | no |  |  |  |
| `total` | decimal(10,2) | no |  |  |  |
| `nit_empresa` | bigint(20) unsigned | sí | IX FK | NULL | → empresa.nit (borrar: RESTRICT, actualizar: CASCADE) |
