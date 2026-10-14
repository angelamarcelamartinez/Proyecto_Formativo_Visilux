-- =====================================================================
--  NIT de las empresas (ópticas) como número entero
--  ---------------------------------------------------------------
--  Antes:  empresa.nit = '900123456-7'   (VARCHAR con puntos/guion)
--  Ahora:  empresa.nit = 9001234567      (BIGINT UNSIGNED, sin guion:
--                                         9 dígitos + dígito de verificación)
--
--  Se usa BIGINT porque un NIT completo tiene 10 dígitos y no cabe en INT
--  (el máximo de INT es 2.147.483.647).
--
--  También se convierten TODAS las columnas `nit_empresa` que apuntan a
--  empresa(nit). Las llaves foráneas se borran y se vuelven a crear con el
--  mismo nombre y las mismas reglas (ON UPDATE / ON DELETE).
--
--  Cómo ejecutarlo: phpMyAdmin → base `prueba_bd` → pestaña SQL →
--  pegar todo este archivo → Continuar.  (Haz una copia de la base antes.)
--  Se puede ejecutar más de una vez sin dañar nada.
--
--  La columna empresa.camara_de_comercio conserva su nombre (el código usa ese nombre).
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP PROCEDURE IF EXISTS visilux_nit_a_entero;

DELIMITER $$

CREATE PROCEDURE visilux_nit_a_entero()
BEGIN
    DECLARE fin INT DEFAULT 0;
    DECLARE v_tabla, v_columna, v_fk, v_upd, v_del, v_nulo VARCHAR(64);
    DECLARE v_default TEXT;
    DECLARE v_sql TEXT;

    -- Llaves foráneas que apuntan a empresa(nit)
    DECLARE c_fk CURSOR FOR
        SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, UPDATE_RULE, DELETE_RULE FROM tmp_fk_nit;

    -- Columnas nit_empresa de las tablas (no de las vistas)
    DECLARE c_col CURSOR FOR
        SELECT c.TABLE_NAME, c.IS_NULLABLE, c.COLUMN_DEFAULT
        FROM information_schema.COLUMNS c
        JOIN information_schema.TABLES t
          ON t.TABLE_SCHEMA = c.TABLE_SCHEMA AND t.TABLE_NAME = c.TABLE_NAME AND t.TABLE_TYPE = 'BASE TABLE'
        WHERE c.TABLE_SCHEMA = DATABASE() AND c.COLUMN_NAME = 'nit_empresa';

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET fin = 1;

    -- 1. Guardar y borrar las llaves foráneas hacia empresa(nit)
    DROP TEMPORARY TABLE IF EXISTS tmp_fk_nit;
    CREATE TEMPORARY TABLE tmp_fk_nit AS
        SELECT k.CONSTRAINT_NAME, k.TABLE_NAME, k.COLUMN_NAME, r.UPDATE_RULE, r.DELETE_RULE
        FROM information_schema.KEY_COLUMN_USAGE k
        JOIN information_schema.REFERENTIAL_CONSTRAINTS r
          ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
         AND r.TABLE_NAME = k.TABLE_NAME
        WHERE k.TABLE_SCHEMA = DATABASE()
          AND k.REFERENCED_TABLE_NAME = 'empresa'
          AND k.REFERENCED_COLUMN_NAME = 'nit';

    SET fin = 0;
    OPEN c_fk;
    bucle_fk: LOOP
        FETCH c_fk INTO v_fk, v_tabla, v_columna, v_upd, v_del;
        IF fin = 1 THEN LEAVE bucle_fk; END IF;
        SET @s = CONCAT('ALTER TABLE `', v_tabla, '` DROP FOREIGN KEY `', v_fk, '`');
        PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
    END LOOP;
    CLOSE c_fk;

    -- 2. empresa.nit: quitar puntos, guion y espacios, y volverlo BIGINT
    UPDATE empresa SET nit = REGEXP_REPLACE(nit, '[^0-9]', '');
    ALTER TABLE empresa MODIFY nit BIGINT UNSIGNED NOT NULL COMMENT 'NIT sin guion: 9 dígitos + dígito de verificación';

    -- El registro de actividad guarda el NIT como id del registro cuando la tabla es empresa
    UPDATE actividad SET id_registro = REGEXP_REPLACE(id_registro, '[^0-9]', '') WHERE tabla = 'empresa';

    -- 3. Cada columna nit_empresa
    SET fin = 0;
    OPEN c_col;
    bucle_col: LOOP
        FETCH c_col INTO v_tabla, v_nulo, v_default;
        IF fin = 1 THEN LEAVE bucle_col; END IF;

        SET @s = CONCAT('UPDATE `', v_tabla, '` SET nit_empresa = NULLIF(REGEXP_REPLACE(nit_empresa, ''[^0-9]'', ''''), '''') ',
                        'WHERE nit_empresa IS NOT NULL');
        PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

        -- El valor por defecto '900000000-1' pasa a 9000000001
        SET v_default = IF(v_default IS NULL OR UPPER(v_default) = 'NULL', '', REGEXP_REPLACE(v_default, '[^0-9]', ''));
        SET v_sql = CONCAT('ALTER TABLE `', v_tabla, '` MODIFY nit_empresa BIGINT UNSIGNED',
            IF(v_nulo = 'NO', ' NOT NULL', ' NULL'),
            IF(v_default <> '', CONCAT(' DEFAULT ', v_default), IF(v_nulo = 'NO', '', ' DEFAULT NULL')));
        SET @s = v_sql;
        PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
    END LOOP;
    CLOSE c_col;

    -- 4. Volver a crear las llaves foráneas con sus mismas reglas
    SET fin = 0;
    OPEN c_fk;
    bucle_fk2: LOOP
        FETCH c_fk INTO v_fk, v_tabla, v_columna, v_upd, v_del;
        IF fin = 1 THEN LEAVE bucle_fk2; END IF;
        SET @s = CONCAT('ALTER TABLE `', v_tabla, '` ADD CONSTRAINT `', v_fk, '` FOREIGN KEY (`', v_columna,
                        '`) REFERENCES `empresa` (`nit`)',
                        IF(v_del = 'RESTRICT', '', CONCAT(' ON DELETE ', v_del)),
                        IF(v_upd = 'RESTRICT', '', CONCAT(' ON UPDATE ', v_upd)));
        PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
    END LOOP;
    CLOSE c_fk;

    DROP TEMPORARY TABLE IF EXISTS tmp_fk_nit;

    -- (La columna del certificado se queda con el nombre `camara_de_comercio`; el código ya usa ese nombre.)
END$$

DELIMITER ;

CALL visilux_nit_a_entero();
DROP PROCEDURE IF EXISTS visilux_nit_a_entero;

SET FOREIGN_KEY_CHECKS = 1;

-- Revisión rápida: debe mostrar el NIT sin guion (ej. 9000000001)
SELECT nit, nombre FROM empresa;
