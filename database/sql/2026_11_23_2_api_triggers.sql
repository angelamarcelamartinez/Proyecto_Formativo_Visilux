-- =====================================================================
-- Visilux · 2026-11-23 · PARTE 2: sección API (triggers 2, 3 y 4)
--  - Tablas de apoyo: log_cambio_cita, log_estado_usuario, log_precio_producto.
--  - TRIGGER 2: trg_cita_cambio_estado    (asignacion_cita)
--  - TRIGGER 3: trg_usuario_cambio_estado (usuario)
--  - TRIGGER 4: trg_producto_cambio_precio (producto)  [reemplaza a trg_lote_stock_bajo]
-- Es independiente de la parte 1. Ejecutar en la base `visilux`.
-- =====================================================================

-- ---------------------------------------------------------------------
-- Tablas de apoyo y triggers de la sección API
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

-- Historial de cambios de precio de los productos (lo llena el TRIGGER 4).
-- Sin nit_empresa porque la tabla `producto` es el catálogo general (no tiene esa columna).
CREATE TABLE IF NOT EXISTS `log_precio_producto` (
  `id_log`          INT(11) NOT NULL AUTO_INCREMENT,
  `id_producto`     INT(11) NOT NULL,
  `producto`        VARCHAR(255) DEFAULT NULL,
  `precio_anterior` DECIMAL(10,2) DEFAULT NULL,
  `precio_nuevo`    DECIMAL(10,2) DEFAULT NULL,
  `fecha_hora`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `idx_log_precio_producto` (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Los triggers usan BEGIN ... END con IF, por eso se cambia el delimitador a $$
-- (así phpMyAdmin no corta el trigger en el primer punto y coma).
DELIMITER $$

DROP TRIGGER IF EXISTS `trg_cita_cambio_estado`$$
-- TRIGGER 2: historial de cada cambio de estado de una cita (pendiente → confirmada, cancelada, etc.).
CREATE TRIGGER `trg_cita_cambio_estado` AFTER UPDATE ON `asignacion_cita` FOR EACH ROW
BEGIN
  -- Solo si el estado cambió de verdad (<=> compara sin problemas cuando hay NULL)
  IF NOT (OLD.id_estado <=> NEW.id_estado) THEN
    INSERT INTO `log_cambio_cita` (`id_cita`, `id_usuario`, `estado_anterior`, `estado_nuevo`, `nit_empresa`)
    VALUES (NEW.id_cita, NEW.id_usuario, OLD.id_estado, NEW.id_estado, NEW.nit_empresa);
  END IF;
END$$

DROP TRIGGER IF EXISTS `trg_usuario_cambio_estado`$$
-- TRIGGER 3: historial de activaciones / inactivaciones de usuarios.
CREATE TRIGGER `trg_usuario_cambio_estado` AFTER UPDATE ON `usuario` FOR EACH ROW
BEGIN
  IF NOT (OLD.id_estado <=> NEW.id_estado) THEN
    INSERT INTO `log_estado_usuario` (`documento`, `nombre`, `estado_anterior`, `estado_nuevo`, `nit_empresa`)
    VALUES (NEW.documento, CONCAT(NEW.nombres, ' ', NEW.apellido), OLD.id_estado, NEW.id_estado, NEW.nit_empresa);
  END IF;
END$$

-- El trigger anterior de stock de lotes se reemplaza por el de precios de productos.
DROP TRIGGER IF EXISTS `trg_lote_stock_bajo`$$

DROP TRIGGER IF EXISTS `trg_producto_cambio_precio`$$
-- TRIGGER 4: historial de cambios de precio de un producto (precio anterior → precio nuevo).
CREATE TRIGGER `trg_producto_cambio_precio` AFTER UPDATE ON `producto` FOR EACH ROW
BEGIN
  -- Solo si el precio cambió de verdad (<=> también compara bien cuando el precio era NULL)
  IF NOT (OLD.precio <=> NEW.precio) THEN
    INSERT INTO `log_precio_producto` (`id_producto`, `producto`, `precio_anterior`, `precio_nuevo`)
    VALUES (NEW.id_producto, NEW.nombre_producto, OLD.precio, NEW.precio);
  END IF;
END$$

DELIMITER ;
