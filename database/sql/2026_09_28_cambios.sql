-- =====================================================================
-- Cambios a la base de datos (28/09/2026)
-- Ejecutar UNA sola vez en phpMyAdmin (local y en Hostinger) si no se
-- corre `php artisan migrate`. Es lo mismo que hace la migración
-- 2026_09_28_120000_add_tipo_documento_to_usuarios_no_registrados.
-- =====================================================================

-- 1) Tipo de documento para los usuarios no registrados (opcional para
--    no afectar las solicitudes que ya existen).
SET NAMES utf8mb4;

ALTER TABLE `usuarios_no_registrados`
  ADD COLUMN `id_tipo_docu` INT(11) NULL DEFAULT NULL AFTER `id_usuario_nr`,
  ADD CONSTRAINT `fk_usuario_nr_tipo_docu`
      FOREIGN KEY (`id_tipo_docu`) REFERENCES `tipo_documento` (`id_tipo_docu`);

-- 2) El teléfono del optómetra era INT(11): un celular de 10 dígitos
--    (ej. 3001234567) no cabe y la base de datos rechaza el guardado.
ALTER TABLE `optometra`
  MODIFY `telefono` VARCHAR(20) NOT NULL;

-- 3) El horario #4 quedó sin día (se intentó guardar un miércoles cuando
--    el panel tenía el error de las tildes). Revísalo en el panel o
--    asígnale el día correcto, por ejemplo:
-- UPDATE `horario` SET `dia_semana` = 'Miércoles' WHERE `id_horario` = 4;

-- 4) Las solicitudes de cita de visitantes (sin cuenta) guardan el turno
--    escogido, para que quede APARTADO y nadie más pueda tomarlo.
--    Mientras la solicitud esté "Pendiente" el turno sigue bloqueado; al
--    pasarla a "Cancelado" o "Completado" (ya se creó la cita real) se libera.
ALTER TABLE `usuarios_no_registrados`
  ADD COLUMN `fecha_cita` DATE NULL DEFAULT NULL AFTER `motivo_cita`,
  ADD COLUMN `hora_cita` TIME NULL DEFAULT NULL AFTER `fecha_cita`,
  ADD COLUMN `id_optometra` INT(11) NULL DEFAULT NULL AFTER `hora_cita`,
  ADD COLUMN `id_estado` INT(11) NULL DEFAULT NULL AFTER `id_optometra`,
  ADD CONSTRAINT `fk_usuario_nr_optometra`
      FOREIGN KEY (`id_optometra`) REFERENCES `optometra` (`doc_optometra`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuario_nr_estado`
      FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_estado`);
