-- =====================================================================
-- Visilux · 2026-11-23
--  1) Cuenta obligatoria: se elimina la tabla de usuarios no registrados.
--  2) Registro de accesos (log_acceso) + trigger 1.
--  3) Sección "API" del admin: tablas de apoyo y triggers 2, 3 y 4.
-- Ejecutar en phpMyAdmin (pestaña SQL) sobre la base `visilux`.
-- Compatible con MariaDB 10.4 / MySQL 8. Cada trigger tiene UNA sola
-- sentencia, así que NO hace falta cambiar el DELIMITER.
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1) Quitar usuarios_no_registrados (y lo que la apuntaba)
-- ---------------------------------------------------------------------
ALTER TABLE `compra_online` DROP FOREIGN KEY IF EXISTS `fk_compra_usuario_no_reg`;
ALTER TABLE `compra_online` DROP INDEX IF EXISTS `fk_compra_usuario_no_reg`;
ALTER TABLE `compra_online` DROP COLUMN IF EXISTS `id_usuario_nr`;
DROP TABLE IF EXISTS `usuarios_no_registrados`;

-- ---------------------------------------------------------------------
-- 2) Registro de accesos
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `log_acceso` (
  `id_log`      INT(11) NOT NULL AUTO_INCREMENT,
  `documento`   INT(11) NOT NULL,
  `nombre`      VARCHAR(200) NOT NULL,
  `nit_empresa` VARCHAR(20) DEFAULT NULL,
  `fecha_hora`  DATETIME NOT NULL,
  PRIMARY KEY (`id_log`),
  KEY `idx_log_acceso_doc` (`documento`),
  KEY `idx_log_acceso_fecha` (`fecha_hora`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TRIGGER IF EXISTS `trg_log_acceso`;
-- TRIGGER 1: cada vez que la app guarda un nuevo `ultimo_acceso` (login exitoso) se anota quién y cuándo.
CREATE TRIGGER `trg_log_acceso` AFTER UPDATE ON `usuario` FOR EACH ROW
  INSERT INTO `log_acceso` (`documento`, `nombre`, `nit_empresa`, `fecha_hora`)
  SELECT NEW.documento, CONCAT(NEW.nombres, ' ', NEW.apellido), NEW.nit_empresa, NEW.ultimo_acceso
  FROM DUAL
  WHERE NEW.ultimo_acceso IS NOT NULL AND NOT (OLD.ultimo_acceso <=> NEW.ultimo_acceso);

-- ---------------------------------------------------------------------
-- 3) Tablas de apoyo y triggers de la sección API
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `log_cambio_cita` (
  `id_log`          INT(11) NOT NULL AUTO_INCREMENT,
  `id_cita`         INT(11) NOT NULL,
  `id_usuario`      INT(11) NOT NULL,
  `estado_anterior` INT(11) DEFAULT NULL,
  `estado_nuevo`    INT(11) DEFAULT NULL,
  `nit_empresa`     VARCHAR(20) DEFAULT NULL,
  `fecha_hora`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `idx_log_cita` (`id_cita`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `log_estado_usuario` (
  `id_log`          INT(11) NOT NULL AUTO_INCREMENT,
  `documento`       INT(11) NOT NULL,
  `nombre`          VARCHAR(200) NOT NULL,
  `estado_anterior` INT(11) DEFAULT NULL,
  `estado_nuevo`    INT(11) DEFAULT NULL,
  `nit_empresa`     VARCHAR(20) DEFAULT NULL,
  `fecha_hora`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `idx_log_estado_usuario` (`documento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `alerta_stock` (
  `id_alerta`    INT(11) NOT NULL AUTO_INCREMENT,
  `id_lote`      INT(11) NOT NULL,
  `id_producto`  INT(11) NOT NULL,
  `producto`     VARCHAR(255) DEFAULT NULL,
  `stock_actual` INT(11) NOT NULL,
  `stock_minimo` INT(11) NOT NULL,
  `nit_empresa`  VARCHAR(20) DEFAULT NULL,
  `fecha_hora`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_alerta`),
  KEY `idx_alerta_lote` (`id_lote`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TRIGGER IF EXISTS `trg_cita_cambio_estado`;
-- TRIGGER 2: historial de cada cambio de estado de una cita (pendiente → confirmada, cancelada, etc.).
CREATE TRIGGER `trg_cita_cambio_estado` AFTER UPDATE ON `asignacion_cita` FOR EACH ROW
  INSERT INTO `log_cambio_cita` (`id_cita`, `id_usuario`, `estado_anterior`, `estado_nuevo`, `nit_empresa`)
  SELECT NEW.id_cita, NEW.id_usuario, OLD.id_estado, NEW.id_estado, NEW.nit_empresa
  FROM DUAL
  WHERE NOT (OLD.id_estado <=> NEW.id_estado);

DROP TRIGGER IF EXISTS `trg_usuario_cambio_estado`;
-- TRIGGER 3: historial de activaciones / inactivaciones de usuarios.
CREATE TRIGGER `trg_usuario_cambio_estado` AFTER UPDATE ON `usuario` FOR EACH ROW
  INSERT INTO `log_estado_usuario` (`documento`, `nombre`, `estado_anterior`, `estado_nuevo`, `nit_empresa`)
  SELECT NEW.documento, CONCAT(NEW.nombres, ' ', NEW.apellido), OLD.id_estado, NEW.id_estado, NEW.nit_empresa
  FROM DUAL
  WHERE NOT (OLD.id_estado <=> NEW.id_estado);

DROP TRIGGER IF EXISTS `trg_lote_stock_bajo`;
-- TRIGGER 4: avisa cuando un lote CRUZA hacia abajo su stock mínimo (una sola alerta por cruce).
CREATE TRIGGER `trg_lote_stock_bajo` AFTER UPDATE ON `lote` FOR EACH ROW
  INSERT INTO `alerta_stock` (`id_lote`, `id_producto`, `producto`, `stock_actual`, `stock_minimo`, `nit_empresa`)
  SELECT NEW.id_lote, NEW.id_producto,
         (SELECT p.nombre_producto FROM `producto` p WHERE p.id_producto = NEW.id_producto),
         NEW.stock_actual, NEW.stock_minimo, NEW.nit_empresa
  FROM DUAL
  WHERE NEW.stock_actual <= NEW.stock_minimo AND OLD.stock_actual > OLD.stock_minimo;
