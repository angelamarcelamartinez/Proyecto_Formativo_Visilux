-- Arregla: Unknown column 'camara_de_comercio' in 'field list' (al contratar un plan / registrar óptica).
-- La tabla empresa de algunas bases trae la columna como `camara_comercio`; el código usa `camara_de_comercio`.
-- Es seguro correrlo varias veces: renombra si hace falta, crea si no existe y no hace nada si ya está bien.
-- Antes de correrlo, selecciona tu base de datos (visilux / optometria) en phpMyAdmin.

SET @bd := DATABASE();

SET @tiene_nueva := (SELECT COUNT(*) FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = @bd AND TABLE_NAME = 'empresa' AND COLUMN_NAME = 'camara_de_comercio');
SET @tiene_vieja := (SELECT COUNT(*) FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = @bd AND TABLE_NAME = 'empresa' AND COLUMN_NAME = 'camara_comercio');

SET @sql := CASE
    WHEN @tiene_nueva > 0 THEN 'SELECT ''camara_de_comercio ya existe: nada que hacer'' AS resultado'
    WHEN @tiene_vieja > 0 THEN 'ALTER TABLE empresa CHANGE COLUMN camara_comercio camara_de_comercio VARCHAR(500) NULL DEFAULT NULL COMMENT ''Ruta del PDF de cámara de comercio'''
    ELSE 'ALTER TABLE empresa ADD COLUMN camara_de_comercio VARCHAR(500) NULL DEFAULT NULL COMMENT ''Ruta del PDF de cámara de comercio'''
END;

PREPARE paso FROM @sql;
EXECUTE paso;
DEALLOCATE PREPARE paso;
