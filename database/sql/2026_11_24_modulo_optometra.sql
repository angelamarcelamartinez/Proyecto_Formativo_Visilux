-- =====================================================================
--  VisiOptica · MÓDULO DEL OPTÓMETRA (17/11/2026)
--  ---------------------------------------------------------------
--  Reemplaza al 2026_11_17_rol_optometra.sql que menciona config/visioptica.php.
--  Se ejecuta DESPUÉS de prueba_bd_nit_entero.sql y de los scripts 2026_11_23_*.
--  Convive con los triggers trg_log_acceso y trg_usuario_cambio_estado (tienen otros nombres).
--
--  Qué hace este script:
--   1. Crea el rol "Optómetra" (id_rol = 7).
--   2. Ajusta la tabla `optometra` (celular, óptica, tarjeta profesional).
--      Desde el 25-11-2026 el optómetra inicia sesión con SU cuenta de la
--      tabla `optometra` (2026_11_25_optometra_cuenta_propia.sql).
--   3. Crea la tabla `simulacion_visual`: deja constancia de cuándo el
--      optómetra le explicó una enfermedad al paciente con el simulador.
--   4. Agrega al catálogo `enfermedades` las patologías del simulador
--      que todavía no estén (catarata, glaucoma, DMAE, etc.).
--   5. Crea los TRIGGERS:
--        trg_historia_clinica_bi         → una sola historia por cita y
--              nunca sobre una cita cancelada.
--        trg_historia_clinica_ai         → al guardar la historia, la cita
--              pasa a "Completado".
--        trg_formula_medica_bi / _bu     → valida los rangos de la fórmula
--              (esfera, cilindro, eje, adición, DP, próximo control).
--
--  Cómo ejecutarlo: phpMyAdmin → tu base (visilux_2 / u716029766_visilux_db)
--  → pestaña SQL → pegar TODO el archivo → Continuar.
--  Se puede ejecutar más de una vez: no duplica nada.
--
--  Después: en el panel del administrador → Equipo → Nuevo empleado → rol
--  "Optómetra". Si el optómetra YA existe en la tabla `optometra` (por
--  ejemplo 50001001 – Catalina Esquivel), elígelo en "¿Ya está en la lista?":
--  se le crea la cuenta en su mismo registro (conserva horarios y citas).
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- 1) Rol Optómetra
-- ---------------------------------------------------------------------
INSERT INTO `rol` (`id_rol`, `nombre_rol`, `descripcion`)
SELECT 7, 'Optómetra', 'Atiende citas, historias clínicas, fórmulas, terapias y simulador visual'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `rol` WHERE `id_rol` = 7);


-- ---------------------------------------------------------------------
-- 2) Tabla optometra
--    - telefono INT(11) no alcanza para un celular de 10 dígitos.
--    - contrasena: la de su cuenta (bcrypt); NULL = registrado sin cuenta.
--    - tarjeta_profesional puede quedar vacía hasta que el optómetra la
--      registre en "Mi perfil".
-- ---------------------------------------------------------------------
-- nit_empresa: a qué óptica pertenece el optómetra (si la columna ya existe, no se toca).
ALTER TABLE `optometra` ADD COLUMN IF NOT EXISTS `nit_empresa` BIGINT(20) UNSIGNED NULL DEFAULT NULL;

ALTER TABLE `optometra`
  MODIFY `telefono` VARCHAR(20) NOT NULL,
  MODIFY `contrasena` VARCHAR(255) NULL DEFAULT NULL,
  MODIFY `tarjeta_profesional` INT(11) NULL DEFAULT NULL,
  MODIFY `especialidad` TEXT NULL;


-- ---------------------------------------------------------------------
-- 3) Registro de uso del simulador visual con el paciente
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `simulacion_visual` (
  `id_simulacion`       INT(11)      NOT NULL AUTO_INCREMENT,
  -- VARCHAR(20): sirve igual si doc_optometra / documento son INT o VARCHAR(20)
  -- (el script visioptica_Corregido.sql los pasa a VARCHAR). Por eso no lleva FK a optometra.
  `id_optometra`        VARCHAR(20)  NOT NULL,
  `documento_paciente`  VARCHAR(20)  DEFAULT NULL,
  `id_historia_clinica` INT(11)      DEFAULT NULL,
  `condicion`           VARCHAR(20)  NOT NULL COMMENT 'amd | cat | glc | dme | dvh | rd | myopia | hyperopia | astigmatism | presbyopia | anatomia',
  `escena`              TINYINT(1)   DEFAULT NULL COMMENT '1 ciudad | 2 Nueva York | 3 París | 4 playa',
  `observaciones`       VARCHAR(255) DEFAULT NULL,
  `fecha`               DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_simulacion`),
  KEY `idx_sim_optometra` (`id_optometra`),
  KEY `idx_sim_paciente` (`documento_paciente`),
  KEY `idx_sim_historia` (`id_historia_clinica`),
  CONSTRAINT `fk_sim_historia` FOREIGN KEY (`id_historia_clinica`)
      REFERENCES `historia_clinica` (`id_historia_clinica`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------------------
-- 4) Enfermedades que muestra el simulador (solo si no existen por código)
-- ---------------------------------------------------------------------
INSERT INTO `enfermedades` (`nombre_enfer`, `descripcion`, `codigo`)
SELECT * FROM (
    SELECT 'Catarata' AS n, 'Opacidad del cristalino que nubla la visión' AS d, 'H26.9' AS c
    UNION ALL SELECT 'Glaucoma', 'Daño del nervio óptico, pérdida del campo visual periférico', 'H40.9'
    UNION ALL SELECT 'Degeneración macular asociada a la edad (DMAE)', 'Deterioro de la mácula: pérdida de la visión central', 'H35.3'
    UNION ALL SELECT 'Edema macular diabético', 'Acumulación de líquido en la mácula por diabetes', 'H36.0'
    UNION ALL SELECT 'Hemorragia vítrea', 'Sangrado dentro del vítreo, frecuente en diabetes', 'H43.1'
    UNION ALL SELECT 'Desprendimiento de retina', 'Separación de la retina de su capa de soporte', 'H33.0'
) AS nuevas
WHERE NOT EXISTS (SELECT 1 FROM `enfermedades` e WHERE e.`codigo` = nuevas.c);


-- ---------------------------------------------------------------------
-- 5) TRIGGERS
-- ---------------------------------------------------------------------
DROP TRIGGER IF EXISTS `trg_usuario_optometra_ai`;
DROP TRIGGER IF EXISTS `trg_usuario_optometra_au`;
DROP TRIGGER IF EXISTS `trg_historia_clinica_bi`;
DROP TRIGGER IF EXISTS `trg_historia_clinica_ai`;
DROP TRIGGER IF EXISTS `trg_formula_medica_bi`;
DROP TRIGGER IF EXISTS `trg_formula_medica_bu`;

DELIMITER $$

-- (25-11-2026) Ya no hay triggers trg_usuario_optometra_*: el optómetra NO tiene fila en `usuario`;
-- toda su información y su cuenta están en `optometra`. Ver 2026_11_25_optometra_cuenta_propia.sql.

-- Antes de guardar una historia clínica: una por cita y nunca sobre una cita cancelada.
CREATE TRIGGER `trg_historia_clinica_bi`
BEFORE INSERT ON `historia_clinica`
FOR EACH ROW
BEGIN
    IF NEW.fecha_registro IS NULL THEN
        SET NEW.fecha_registro = NOW();
    END IF;

    IF EXISTS (SELECT 1 FROM historia_clinica WHERE id_asignacioncita = NEW.id_asignacioncita) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Esa cita ya tiene una historia clínica registrada.';
    END IF;

    IF EXISTS (SELECT 1 FROM asignacion_cita c
               JOIN estado e ON e.id_estado = c.id_estado
               WHERE c.id_cita = NEW.id_asignacioncita AND e.nom_estado = 'Cancelado') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'No se puede registrar la historia de una cita cancelada.';
    END IF;
END$$

-- Historia guardada → la cita queda "Completado".
CREATE TRIGGER `trg_historia_clinica_ai`
AFTER INSERT ON `historia_clinica`
FOR EACH ROW
BEGIN
    DECLARE v_completado INT DEFAULT NULL;

    SELECT id_estado INTO v_completado FROM estado WHERE nom_estado = 'Completado' LIMIT 1;

    IF v_completado IS NOT NULL THEN
        UPDATE asignacion_cita SET id_estado = v_completado WHERE id_cita = NEW.id_asignacioncita;
    END IF;
END$$

-- Fórmula: valores dentro de rangos clínicos razonables.
CREATE TRIGGER `trg_formula_medica_bi`
BEFORE INSERT ON `formula_medica`
FOR EACH ROW
BEGIN
    IF NEW.fecha_formula IS NULL THEN
        SET NEW.fecha_formula = CURDATE();
    END IF;

    IF NEW.od_esfera NOT BETWEEN -30 AND 30 OR NEW.oi_esfera NOT BETWEEN -30 AND 30 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La esfera debe estar entre -30.00 y +30.00 D.';
    END IF;
    IF NEW.od_cilindro NOT BETWEEN -10 AND 10 OR NEW.oi_cilindro NOT BETWEEN -10 AND 10 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El cilindro debe estar entre -10.00 y +10.00 D.';
    END IF;
    IF NEW.od_eje > 180 OR NEW.oi_eje > 180 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El eje debe estar entre 0 y 180 grados.';
    END IF;
    IF NEW.od_adc NOT BETWEEN 0 AND 4 OR NEW.oi_adc NOT BETWEEN 0 AND 4 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La adición debe estar entre 0.00 y +4.00 D.';
    END IF;
    IF NEW.od_dist_pupilar NOT BETWEEN 10 AND 80 OR NEW.oi_dist_pupilar NOT BETWEEN 10 AND 80 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La distancia pupilar no es válida.';
    END IF;
    IF NEW.proximo_control IS NOT NULL AND NEW.proximo_control < NEW.fecha_formula THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El próximo control no puede ser anterior a la fecha de la fórmula.';
    END IF;
END$$

CREATE TRIGGER `trg_formula_medica_bu`
BEFORE UPDATE ON `formula_medica`
FOR EACH ROW
BEGIN
    IF NEW.fecha_formula IS NULL THEN
        SET NEW.fecha_formula = IFNULL(OLD.fecha_formula, CURDATE());
    END IF;

    IF NEW.od_esfera NOT BETWEEN -30 AND 30 OR NEW.oi_esfera NOT BETWEEN -30 AND 30 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La esfera debe estar entre -30.00 y +30.00 D.';
    END IF;
    IF NEW.od_cilindro NOT BETWEEN -10 AND 10 OR NEW.oi_cilindro NOT BETWEEN -10 AND 10 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El cilindro debe estar entre -10.00 y +10.00 D.';
    END IF;
    IF NEW.od_eje > 180 OR NEW.oi_eje > 180 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El eje debe estar entre 0 y 180 grados.';
    END IF;
    IF NEW.od_adc NOT BETWEEN 0 AND 4 OR NEW.oi_adc NOT BETWEEN 0 AND 4 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La adición debe estar entre 0.00 y +4.00 D.';
    END IF;
    IF NEW.od_dist_pupilar NOT BETWEEN 10 AND 80 OR NEW.oi_dist_pupilar NOT BETWEEN 10 AND 80 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La distancia pupilar no es válida.';
    END IF;
    IF NEW.proximo_control IS NOT NULL AND NEW.proximo_control < NEW.fecha_formula THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El próximo control no puede ser anterior a la fecha de la fórmula.';
    END IF;
END$$

DELIMITER ;

-- Revisión rápida
SELECT id_rol, nombre_rol FROM rol WHERE id_rol = 7;
SHOW TRIGGERS WHERE `Trigger` LIKE 'trg_%';
