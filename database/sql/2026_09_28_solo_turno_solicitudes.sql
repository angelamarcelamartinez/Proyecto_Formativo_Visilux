-- =====================================================================
-- SOLO si ya habías ejecutado antes 2026_09_28_cambios.sql
-- (la versión anterior no traía esto). Si lo ejecutas por primera vez
-- completo, NO necesitas este archivo.
--
-- Las solicitudes de cita de visitantes guardan el turno escogido para
-- que quede apartado mientras estén "Pendiente".
-- =====================================================================
SET NAMES utf8mb4;

ALTER TABLE `usuarios_no_registrados`
  ADD COLUMN `fecha_cita` DATE NULL DEFAULT NULL AFTER `motivo_cita`,
  ADD COLUMN `hora_cita` TIME NULL DEFAULT NULL AFTER `fecha_cita`,
  ADD COLUMN `id_optometra` INT(11) NULL DEFAULT NULL AFTER `hora_cita`,
  ADD COLUMN `id_estado` INT(11) NULL DEFAULT NULL AFTER `id_optometra`,
  ADD CONSTRAINT `fk_usuario_nr_optometra`
      FOREIGN KEY (`id_optometra`) REFERENCES `optometra` (`doc_optometra`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuario_nr_estado`
      FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_estado`);
