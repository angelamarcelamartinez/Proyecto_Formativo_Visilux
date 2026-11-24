-- =====================================================================
-- Visilux · 2026-11-23 · PARTE 1: cuenta obligatoria + registro de accesos
--  - Quita la tabla usuarios_no_registrados.
--  - Crea log_acceso y el TRIGGER 1 (trg_log_acceso).
-- Ejecutar en phpMyAdmin (pestaña SQL) sobre la base `visilux`.
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

DELIMITER $$

DROP TRIGGER IF EXISTS `trg_log_acceso`$$
-- TRIGGER 1: cada vez que la app guarda un nuevo `ultimo_acceso` (login exitoso) se anota quién y cuándo.
CREATE TRIGGER `trg_log_acceso` AFTER UPDATE ON `usuario` FOR EACH ROW
BEGIN
  IF NEW.ultimo_acceso IS NOT NULL AND NOT (OLD.ultimo_acceso <=> NEW.ultimo_acceso) THEN
    INSERT INTO `log_acceso` (`documento`, `nombre`, `nit_empresa`, `fecha_hora`)
    VALUES (NEW.documento, CONCAT(NEW.nombres, ' ', NEW.apellido), NEW.nit_empresa, NEW.ultimo_acceso);
  END IF;
END$$

DELIMITER ;
