-- Deja la columna del certificado de la Cámara de Comercio como `empresa.camara_comercio` (el nombre que usa el código).
-- Es seguro correrlo varias veces: renombra si hace falta, crea si no existe y no hace nada si ya está bien.
-- Antes de correrlo, selecciona tu base de datos (visilux / optometria) en phpMyAdmin.

SET @bd := DATABASE();

SET @tiene_buena := (SELECT COUNT(*) FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = @bd AND TABLE_NAME = 'empresa' AND COLUMN_NAME = 'camara_comercio');
SET @tiene_otra  := (SELECT COUNT(*) FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = @bd AND TABLE_NAME = 'empresa' AND COLUMN_NAME = 'camara_de_comercio');

-- Las filas sin certificado no pueden quedar en NULL porque la columna es obligatoria: se les pone ''.
SET @limpiar := IF(@tiene_otra > 0 AND @tiene_buena = 0,
    'UPDATE empresa SET camara_de_comercio = '''' WHERE camara_de_comercio IS NULL',
    'SELECT 1');
PREPARE limpiar FROM @limpiar;
EXECUTE limpiar;
DEALLOCATE PREPARE limpiar;

SET @sql := CASE
    WHEN @tiene_buena > 0 THEN 'SELECT ''camara_comercio ya existe: nada que hacer'' AS resultado'
    WHEN @tiene_otra  > 0 THEN 'ALTER TABLE empresa CHANGE COLUMN camara_de_comercio camara_comercio VARCHAR(500) NOT NULL DEFAULT '''' COMMENT ''Ruta del PDF de cámara de comercio'''
    ELSE 'ALTER TABLE empresa ADD COLUMN camara_comercio VARCHAR(500) NOT NULL DEFAULT '''' COMMENT ''Ruta del PDF de cámara de comercio'''
END;

PREPARE paso FROM @sql;
EXECUTE paso;
DEALLOCATE PREPARE paso;
