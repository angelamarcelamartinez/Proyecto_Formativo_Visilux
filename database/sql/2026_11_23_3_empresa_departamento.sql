-- =====================================================================
-- Visilux · 2026-11-23 · PARTE 3: departamento de la óptica
--  Agrega la columna `departamento` a la tabla `empresa`.
--  El registro de /planes/{plan}/contratar guarda aquí el departamento elegido
--  de la API de departamentos de Colombia (api-colombia.com); la ciudad sigue en `ciudad`.
-- Ejecutar en la base `visilux` (phpMyAdmin → pestaña SQL).
-- =====================================================================

ALTER TABLE `empresa`
  ADD COLUMN IF NOT EXISTS `departamento` VARCHAR(100) DEFAULT NULL AFTER `direccion`;
