-- =============================================================================
-- VISIOPTICA — AJUSTES FINALES SEGÚN LA PLANTILLA DE AUDITORÍA (25-11-2026)
-- -----------------------------------------------------------------------------
-- SQL básico: ALTER TABLE, UPDATE, DELETE e índices. Sin procedimientos, sin
-- eventos, sin SET GLOBAL y sin DEFINER (sirve en XAMPP y en Hostinger).
-- Va DESPUÉS de 2026_11_25_triggers_simplificados.sql. Se puede importar más de una vez.
--
--  Criterio de la auditoría        Qué se hace aquí
--  1 Normalización (3FN)           Se quita optometra.especialidad (repetía especialidad.nombre_especialidad)
--  2 Estructura de tablas          Nombres en VARCHAR en vez de TEXT; created_at / updated_at en las tablas principales
--  3 Cardinalidad y relaciones     Llaves foráneas de simulacion_visual
--  5 Índices y rendimiento         Se quitan 7 índices repetidos (ya los cubre un índice compuesto)
--  6 Claves primarias              producto.id_producto pasa a AUTO_INCREMENT
--  8 Escalabilidad                 sessions y cache vacías
-- 11 Documentación                 Comentarios de las tablas al día con los 8 triggers reales
-- =============================================================================
SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- 1. Normalización: la especialidad del optómetra se lee de la tabla especialidad
-- -----------------------------------------------------------------------------
ALTER TABLE optometra DROP COLUMN IF EXISTS especialidad;

-- -----------------------------------------------------------------------------
-- 2. Estructura: nombres cortos en VARCHAR (TEXT es para textos largos)
-- -----------------------------------------------------------------------------
ALTER TABLE usuario            MODIFY nombres VARCHAR(100) NOT NULL, MODIFY apellido VARCHAR(100) NOT NULL;
ALTER TABLE optometra          MODIFY nombre VARCHAR(100) NOT NULL, MODIFY apellido VARCHAR(100) NOT NULL;
ALTER TABLE producto           MODIFY nombre_producto VARCHAR(150) NOT NULL, MODIFY color VARCHAR(50) NULL;
ALTER TABLE categoria_producto MODIFY nombre_categoria VARCHAR(100) NOT NULL;
ALTER TABLE ciudad             MODIFY nombre_ciudad VARCHAR(100) NOT NULL;
ALTER TABLE departamento       MODIFY nom_departamento VARCHAR(100) NOT NULL;
ALTER TABLE enfermedades       MODIFY nombre_enfer VARCHAR(150) NOT NULL;
ALTER TABLE estado             MODIFY nom_estado VARCHAR(50) NOT NULL;
ALTER TABLE marca              MODIFY nom_marca VARCHAR(100) NOT NULL;
ALTER TABLE modelo             MODIFY nom_modelo VARCHAR(100) NOT NULL;
ALTER TABLE rol                MODIFY nombre_rol VARCHAR(50) NOT NULL;
ALTER TABLE tipo_cita          MODIFY nombre_tipo VARCHAR(100) NOT NULL;
ALTER TABLE tipo_documento     MODIFY nom_ti_docu VARCHAR(50) NOT NULL;
ALTER TABLE tipo_producto      MODIFY nomb_tipo VARCHAR(100) NOT NULL;
ALTER TABLE tipo_terapia       MODIFY nomtipo_terp VARCHAR(100) NOT NULL;
ALTER TABLE envio              MODIFY ciudad VARCHAR(100) NULL, MODIFY direccion_envio VARCHAR(255) NULL;

-- Columnas de auditoría: cuándo se creó y cuándo se modificó por última vez.
-- La base las llena sola (DEFAULT CURRENT_TIMESTAMP / ON UPDATE CURRENT_TIMESTAMP).
ALTER TABLE usuario         ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Última modificación';
ALTER TABLE optometra       ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Última modificación';
ALTER TABLE asignacion_cita ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Cuándo se agendó',
                            ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Última modificación';
ALTER TABLE producto        ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE lote            ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE venta           ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE pedido          ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

-- -----------------------------------------------------------------------------
-- 3. Relaciones: simulacion_visual con su optómetra y su paciente
-- -----------------------------------------------------------------------------
-- Si quedó alguna simulación de alguien que ya no existe, se borra antes de crear la llave.
DELETE FROM simulacion_visual WHERE id_optometra NOT IN (SELECT doc_optometra FROM optometra);
UPDATE simulacion_visual SET documento_paciente = NULL
 WHERE documento_paciente IS NOT NULL AND documento_paciente NOT IN (SELECT documento FROM usuario);

ALTER TABLE simulacion_visual
  ADD CONSTRAINT fk_sim_optometra FOREIGN KEY IF NOT EXISTS (id_optometra)
      REFERENCES optometra (doc_optometra) ON UPDATE CASCADE,
  ADD CONSTRAINT fk_sim_paciente FOREIGN KEY IF NOT EXISTS (documento_paciente)
      REFERENCES usuario (documento) ON DELETE SET NULL ON UPDATE CASCADE;

-- -----------------------------------------------------------------------------
-- 5. Índices repetidos: cada uno ya lo cubre un índice compuesto que empieza
--    por la misma columna, así que solo ocupaban espacio y hacían lentos los INSERT.
-- -----------------------------------------------------------------------------
ALTER TABLE asignacion_cita  DROP INDEX IF EXISTS fk_asignacion_cita_empresa;  -- lo cubre idx_cita_empresa_fecha
ALTER TABLE asignacion_cita  DROP INDEX IF EXISTS id_optometra;                -- lo cubre idx_cita_optometra_fecha
ALTER TABLE formula_medica   DROP INDEX IF EXISTS fk_formula_usuario;          -- lo cubre idx_formula_paciente_fecha
ALTER TABLE historia_clinica DROP INDEX IF EXISTS id_asignacioncita;           -- igual a uk_hc_cita
ALTER TABLE lote             DROP INDEX IF EXISTS fk_lote_producto;            -- lo cubre idx_lote_producto_estado
ALTER TABLE venta            DROP INDEX IF EXISTS fk_venta_empresa;            -- lo cubre idx_venta_empresa_fecha
ALTER TABLE venta            DROP INDEX IF EXISTS id_usuario;                  -- lo cubre idx_venta_usuario_fecha

-- -----------------------------------------------------------------------------
-- 6. Claves primarias: el producto nuevo recibe su número solo
-- -----------------------------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;
ALTER TABLE producto MODIFY id_producto INT(11) NOT NULL AUTO_INCREMENT;
SET FOREIGN_KEY_CHECKS = 1;
ALTER TABLE producto AUTO_INCREMENT = 11;

-- -----------------------------------------------------------------------------
-- 8. Datos temporales de Laravel: no se guardan en el respaldo
-- -----------------------------------------------------------------------------
DELETE FROM sessions;
DELETE FROM cache;
DELETE FROM cache_locks;

-- -----------------------------------------------------------------------------
-- 11. Documentación: comentarios al día
-- -----------------------------------------------------------------------------
ALTER TABLE asignacion_cita COMMENT = 'Agenda: paciente + optómetra + fecha/hora. La app solo ofrece turnos libres. Triggers: trg_cita_cambio_estado (bitácora).';
ALTER TABLE historia_clinica COMMENT = 'Atención clínica (1 por cita). Dato sensible (Ley 1581/2012), retención mínima 15 años (Res. 839/2017). Trigger: trg_historia_clinica_ai (cita -> Completado).';
ALTER TABLE lote COMMENT = 'Inventario por lote. Triggers: trg_venta_descuenta_stock (la venta descuenta) y trg_lote_stock_bajo (alerta).';
ALTER TABLE log_historia_clinica COMMENT = 'Versión anterior de una historia o fórmula cada vez que se edita (lo guarda la app: ConsultaController). No se purga: retención 15 años.';
ALTER TABLE paciente COMMENT = 'Datos clínicos del paciente (1:1 con usuario). Reservada: la app aún no la usa.';
ALTER TABLE optometra COMMENT = 'Optómetra: toda su información y su cuenta (correo + contrasena bcrypt). No tiene fila en usuario. contrasena NULL = sin cuenta. Trigger: trg_optometra_bitacora.';
ALTER TABLE usuario COMMENT = 'Cuentas del sistema (superadmin, administradores, empleados y clientes). Los optómetras están en optometra. Triggers: trg_log_acceso, trg_usuario_cambio_estado.';
ALTER TABLE producto COMMENT = 'Catálogo de productos. Trigger: trg_producto_cambio_precio (bitácora de precios).';

-- Registro de esta versión (la misma que la migración de Laravel).
INSERT INTO migrations (migration, batch)
SELECT '2026_11_25_000001_auditoria_final', t.siguiente
  FROM (SELECT IFNULL(MAX(batch), 0) + 1 AS siguiente FROM migrations) t
 WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_11_25_000001_auditoria_final');
