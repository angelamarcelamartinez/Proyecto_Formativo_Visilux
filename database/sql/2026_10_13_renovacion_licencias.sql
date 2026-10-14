-- ============================================================================
-- VisiOptica: renovación de licencias
--   1) Nuevo estado «inactiva» (la licencia vencida que se reemplaza por una renovación).
--   2) Número de licencia de mínimo 10 dígitos, automático (AUTO_INCREMENT desde 1000000000).
-- Ejecutar UNA vez en phpMyAdmin sobre la base de datos del proyecto.
-- ============================================================================

-- 1) Estado «inactiva»
ALTER TABLE licencia
  MODIFY estado ENUM('pendiente','activa','vencida','inactiva','rechazada','cancelada') NOT NULL DEFAULT 'pendiente';

-- 2) Las licencias que ya existen pasan a tener 10 dígitos (se les suma 1.000.000.000)
--    y la bitácora de actividad sigue apuntando al número nuevo.
UPDATE actividad SET id_registro = CAST(CAST(id_registro AS UNSIGNED) + 1000000000 AS CHAR)
 WHERE tabla = 'licencia' AND id_registro REGEXP '^[0-9]{1,9}$';

UPDATE licencia SET id_licencia = id_licencia + 1000000000 WHERE id_licencia < 1000000000
 ORDER BY id_licencia DESC;

-- 3) Las licencias nuevas siguen desde el mayor número + 1 (siempre de 10 dígitos)
SET @siguiente = (SELECT GREATEST(IFNULL(MAX(id_licencia), 0) + 1, 1000000000) FROM licencia);
SET @sql = CONCAT('ALTER TABLE licencia AUTO_INCREMENT = ', @siguiente);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
