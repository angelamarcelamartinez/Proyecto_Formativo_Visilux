-- =============================================================================
-- VISIOPTICA — MEJORAS DE LA AUDITORÍA (lo que faltaba de DBA-01 a DBA-16)
-- + VALIDACIÓN DEL DOCUMENTO SEGÚN SU TIPO
-- -----------------------------------------------------------------------------
-- Va DESPUÉS de:
--   1. visioptica_Corregido_sin_P10.sql
--   2. 2026_11_24_modulo_optometra.sql
--   3. 2026_11_24_tabla_mensajes_contacto.sql
-- Se puede importar más de una vez: cada paso revisa si ya está hecho.
--
-- Qué hace (cada bloque dice qué hallazgo cierra):
--   A. Documento válido según su tipo (cédula, TI, pasaporte...), en la BD.
--   B. Multiempresa: nit_empresa en las tablas clínicas y de compras, llenado
--      por triggers; sin el DEFAULT 9000000001; logs con el mismo tipo.  (DBA-02)
--   C. Inventario: la venta descuenta stock, la anulación lo devuelve,
--      alertas de stock bajo, lote "Agotado" automático, log de precio. (DBA-08/09)
--   D. Modelo clínico: una historia por cita, diagnósticos ligados a la
--      historia (1:N) y tabla paciente con validación edad/documento.   (DBA-12)
--   E. Limpieza y seguridad: contraseña del optómetra fuera, datos de prueba,
--      una sola collation, estados por dominio.                (DBA-01/05/13/14)
--   G. Comentarios de tablas (diccionario mínimo).                    (DBA-16)
-- DBA-06 (usuarios/GRANT/TLS) y DBA-07 (backups) son del servidor, no de la
-- base: ver database/sql/LEEME_BASE_DE_DATOS.md.
-- =============================================================================
SET NAMES utf8mb4;

-- #############################################################################
-- A. DOCUMENTO VÁLIDO SEGÚN SU TIPO
-- #############################################################################
-- Las reglas viven en tipo_documento: Laravel (App\Support\TipoDocumento) y los
-- triggers leen la misma tabla. Para cambiar una regla basta un UPDATE aquí.
ALTER TABLE tipo_documento
  ADD COLUMN IF NOT EXISTS patron       VARCHAR(60)  NULL COMMENT 'Expresión regular que debe cumplir el documento (ya normalizado)',
  ADD COLUMN IF NOT EXISTS long_min     TINYINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS long_max     TINYINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS solo_numeros TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS ayuda        VARCHAR(150) NULL COMMENT 'Texto que ve el usuario en el formulario';

INSERT IGNORE INTO tipo_documento (id_tipo_docu, nom_ti_docu) VALUES (6, 'Registro Civil');

UPDATE tipo_documento SET patron = '^[1-9][0-9]{5,9}$',  long_min = 6,  long_max = 10, solo_numeros = 1,
       ayuda = 'Cédula de ciudadanía: solo números, de 6 a 10 dígitos.' WHERE id_tipo_docu = 1;
UPDATE tipo_documento SET patron = '^[1-9][0-9]{9,10}$', long_min = 10, long_max = 11, solo_numeros = 1,
       ayuda = 'Tarjeta de identidad: solo números, 10 u 11 dígitos.' WHERE id_tipo_docu = 2;
UPDATE tipo_documento SET patron = '^[1-9][0-9]{5,9}$',  long_min = 6,  long_max = 10, solo_numeros = 1,
       ayuda = 'Cédula de extranjería: solo números, de 6 a 10 dígitos.' WHERE id_tipo_docu = 3;
UPDATE tipo_documento SET patron = '^[A-Z0-9]{6,12}$',   long_min = 6,  long_max = 12, solo_numeros = 0,
       ayuda = 'Pasaporte: letras y números, de 6 a 12 caracteres, sin espacios.' WHERE id_tipo_docu = 4;
UPDATE tipo_documento SET patron = '^[1-9][0-9]{8,9}$',  long_min = 9,  long_max = 10, solo_numeros = 1,
       ayuda = 'NIT: 9 dígitos y, si quiere, el dígito de verificación (sin guion).' WHERE id_tipo_docu = 5;
UPDATE tipo_documento SET patron = '^[1-9][0-9]{9,10}$', long_min = 10, long_max = 11, solo_numeros = 1,
       ayuda = 'Registro civil (NUIP): solo números, 10 u 11 dígitos.' WHERE id_tipo_docu = 6;

DROP PROCEDURE IF EXISTS sp_validar_documento;
DROP TRIGGER IF EXISTS trg_usuario_documento_bi;
DROP TRIGGER IF EXISTS trg_usuario_documento_bu;
DROP TRIGGER IF EXISTS trg_optometra_documento_bi;
DROP TRIGGER IF EXISTS trg_optometra_documento_bu;

DELIMITER $$
-- Deja el documento limpio (sin espacios, puntos ni guiones, en mayúsculas) y
-- detiene el INSERT/UPDATE con un mensaje claro si no cumple la regla del tipo.
CREATE PROCEDURE sp_validar_documento(IN p_tipo INT, INOUT p_doc VARCHAR(20))
BEGIN
  DECLARE v_nombre VARCHAR(60);
  DECLARE v_patron VARCHAR(60);
  DECLARE v_min INT;
  DECLARE v_max INT;
  DECLARE v_numeros TINYINT;
  DECLARE v_ayuda VARCHAR(150);
  DECLARE v_msg VARCHAR(255);

  SET p_doc = UPPER(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(p_doc), ' ', ''), '.', ''), '-', ''), ',', ''));

  SELECT nom_ti_docu, patron, long_min, long_max, solo_numeros, ayuda
    INTO v_nombre, v_patron, v_min, v_max, v_numeros, v_ayuda
    FROM tipo_documento WHERE id_tipo_docu = p_tipo;

  IF v_nombre IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tipo de documento no válido.';
  END IF;
  IF p_doc IS NULL OR p_doc = '' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El número de documento es obligatorio.';
  END IF;
  IF v_numeros = 1 AND p_doc NOT REGEXP '^[0-9]+$' THEN
    SET v_msg = CONCAT(v_nombre, ': solo se permiten números.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;
  IF v_numeros = 0 AND p_doc NOT REGEXP '^[A-Z0-9]+$' THEN
    SET v_msg = CONCAT(v_nombre, ': solo letras y números, sin espacios ni símbolos.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;
  IF v_min IS NOT NULL AND (CHAR_LENGTH(p_doc) < v_min OR CHAR_LENGTH(p_doc) > v_max) THEN
    SET v_msg = CONCAT(v_nombre, ': debe tener de ', v_min, ' a ', v_max,
                       IF(v_numeros = 1, ' dígitos', ' caracteres'), ' (tiene ', CHAR_LENGTH(p_doc), ').');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;
  IF v_patron IS NOT NULL AND p_doc NOT REGEXP v_patron THEN
    SET v_msg = COALESCE(v_ayuda, CONCAT(v_nombre, ': formato no válido.'));
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;
END$$

CREATE TRIGGER trg_usuario_documento_bi BEFORE INSERT ON usuario FOR EACH ROW
BEGIN
  CALL sp_validar_documento(NEW.id_tipo_docu, NEW.documento);
END$$

-- Solo se revisa si cambian el número o el tipo: los registros viejos se
-- pueden seguir editando (nombre, estado...) sin que un dato antiguo lo impida.
CREATE TRIGGER trg_usuario_documento_bu BEFORE UPDATE ON usuario FOR EACH ROW
BEGIN
  IF NOT (OLD.documento <=> NEW.documento) OR NOT (OLD.id_tipo_docu <=> NEW.id_tipo_docu) THEN
    CALL sp_validar_documento(NEW.id_tipo_docu, NEW.documento);
  END IF;
END$$

CREATE TRIGGER trg_optometra_documento_bi BEFORE INSERT ON optometra FOR EACH ROW
BEGIN
  CALL sp_validar_documento(NEW.id_tipo_docu, NEW.doc_optometra);
END$$

CREATE TRIGGER trg_optometra_documento_bu BEFORE UPDATE ON optometra FOR EACH ROW
BEGIN
  IF NOT (OLD.doc_optometra <=> NEW.doc_optometra) OR NOT (OLD.id_tipo_docu <=> NEW.id_tipo_docu) THEN
    CALL sp_validar_documento(NEW.id_tipo_docu, NEW.doc_optometra);
  END IF;
END$$
DELIMITER ;

-- #############################################################################
-- B. MULTIEMPRESA (DBA-02)
-- #############################################################################
-- B.1 Logs y alertas: nit_empresa con el mismo tipo que empresa.nit (BIGINT UNSIGNED).
UPDATE log_acceso l JOIN usuario u ON u.documento = l.documento
   SET l.nit_empresa = u.nit_empresa WHERE l.nit_empresa IS NULL OR l.nit_empresa = '';
UPDATE log_estado_usuario l JOIN usuario u ON u.documento = l.documento
   SET l.nit_empresa = u.nit_empresa WHERE l.nit_empresa IS NULL OR l.nit_empresa = '';
UPDATE log_cambio_cita l JOIN asignacion_cita c ON c.id_cita = l.id_cita
   SET l.nit_empresa = c.nit_empresa WHERE l.nit_empresa IS NULL OR l.nit_empresa = '';
UPDATE alerta_stock a JOIN lote lo ON lo.id_lote = a.id_lote
   SET a.nit_empresa = lo.nit_empresa WHERE a.nit_empresa IS NULL OR a.nit_empresa = '';
UPDATE log_acceso         SET nit_empresa = NULL WHERE nit_empresa = '';
UPDATE log_estado_usuario SET nit_empresa = NULL WHERE nit_empresa = '';
UPDATE log_cambio_cita    SET nit_empresa = NULL WHERE nit_empresa = '';
UPDATE alerta_stock       SET nit_empresa = NULL WHERE nit_empresa = '';

ALTER TABLE log_acceso         MODIFY nit_empresa BIGINT(20) UNSIGNED NULL, ADD INDEX IF NOT EXISTS idx_log_acceso_empresa (nit_empresa, fecha_hora);
ALTER TABLE log_estado_usuario MODIFY nit_empresa BIGINT(20) UNSIGNED NULL;
ALTER TABLE log_cambio_cita    MODIFY nit_empresa BIGINT(20) UNSIGNED NULL;
ALTER TABLE alerta_stock       MODIFY nit_empresa BIGINT(20) UNSIGNED NULL, ADD INDEX IF NOT EXISTS idx_alerta_empresa (nit_empresa, fecha_hora);

-- B.2 nit_empresa en las tablas clínicas y de compra que no lo tenían.
ALTER TABLE historia_clinica           ADD COLUMN IF NOT EXISTS nit_empresa BIGINT(20) UNSIGNED NULL;
ALTER TABLE formula_medica             ADD COLUMN IF NOT EXISTS nit_empresa BIGINT(20) UNSIGNED NULL;
ALTER TABLE diagnostico                ADD COLUMN IF NOT EXISTS nit_empresa BIGINT(20) UNSIGNED NULL;
ALTER TABLE agendamiento_procedimiento ADD COLUMN IF NOT EXISTS nit_empresa BIGINT(20) UNSIGNED NULL;
ALTER TABLE evolucion_terapia          ADD COLUMN IF NOT EXISTS nit_empresa BIGINT(20) UNSIGNED NULL;
ALTER TABLE carrito                    ADD COLUMN IF NOT EXISTS nit_empresa BIGINT(20) UNSIGNED NULL;
ALTER TABLE compra_online              ADD COLUMN IF NOT EXISTS nit_empresa BIGINT(20) UNSIGNED NULL;
ALTER TABLE envio                      ADD COLUMN IF NOT EXISTS nit_empresa BIGINT(20) UNSIGNED NULL;
-- (DBA-12) diagnóstico ligado a su historia clínica; se llena más abajo.
ALTER TABLE diagnostico ADD COLUMN IF NOT EXISTS id_historia_clinica INT(11) NULL AFTER id_formula;

-- Se llenan desde el registro "padre" (cita, paciente, fórmula, venta).
UPDATE historia_clinica h JOIN asignacion_cita c ON c.id_cita = h.id_asignacioncita
   SET h.nit_empresa = c.nit_empresa WHERE h.nit_empresa IS NULL;
UPDATE formula_medica f JOIN usuario u ON u.documento = f.documento_paciente
   SET f.nit_empresa = u.nit_empresa WHERE f.nit_empresa IS NULL;
UPDATE formula_medica f JOIN historia_clinica h ON h.id_formula = f.id_formula
   SET f.nit_empresa = h.nit_empresa WHERE h.nit_empresa IS NOT NULL;
UPDATE diagnostico d JOIN formula_medica f ON f.id_formula = d.id_formula
   SET d.nit_empresa = f.nit_empresa WHERE d.nit_empresa IS NULL;
UPDATE agendamiento_procedimiento a JOIN historia_clinica h ON h.id_historia_clinica = a.id_historia_clinica
   SET a.nit_empresa = h.nit_empresa WHERE a.nit_empresa IS NULL;
UPDATE agendamiento_procedimiento a JOIN formula_medica f ON f.id_formula = a.id_formula
   SET a.nit_empresa = f.nit_empresa WHERE a.nit_empresa IS NULL;
UPDATE evolucion_terapia e JOIN agendamiento_procedimiento a ON a.id_agendamiento_procedimiento = e.id_agendamiento_procedimiento
   SET e.nit_empresa = a.nit_empresa WHERE e.nit_empresa IS NULL;
UPDATE carrito ca JOIN usuario u ON u.documento = ca.id_usuario
   SET ca.nit_empresa = u.nit_empresa WHERE ca.nit_empresa IS NULL;
UPDATE compra_online co JOIN usuario u ON u.documento = co.id_usuario
   SET co.nit_empresa = u.nit_empresa WHERE co.nit_empresa IS NULL;
UPDATE envio en JOIN venta v ON v.id_venta = en.id_venta
   SET en.nit_empresa = v.nit_empresa WHERE en.nit_empresa IS NULL;

ALTER TABLE historia_clinica
  ADD CONSTRAINT fk_hc_empresa FOREIGN KEY IF NOT EXISTS (nit_empresa) REFERENCES empresa (nit) ON UPDATE CASCADE,
  ADD INDEX IF NOT EXISTS idx_hc_empresa_fecha (nit_empresa, fecha_registro);
ALTER TABLE formula_medica
  ADD CONSTRAINT fk_formula_empresa FOREIGN KEY IF NOT EXISTS (nit_empresa) REFERENCES empresa (nit) ON UPDATE CASCADE,
  ADD INDEX IF NOT EXISTS idx_formula_empresa_control (nit_empresa, proximo_control);
ALTER TABLE diagnostico
  ADD CONSTRAINT fk_diag_empresa FOREIGN KEY IF NOT EXISTS (nit_empresa) REFERENCES empresa (nit) ON UPDATE CASCADE,
  ADD INDEX IF NOT EXISTS idx_diag_empresa (nit_empresa);
ALTER TABLE agendamiento_procedimiento
  ADD CONSTRAINT fk_agenda_empresa FOREIGN KEY IF NOT EXISTS (nit_empresa) REFERENCES empresa (nit) ON UPDATE CASCADE,
  ADD INDEX IF NOT EXISTS idx_agenda_empresa_fecha (nit_empresa, fecha_inicio);
ALTER TABLE evolucion_terapia
  ADD CONSTRAINT fk_evolucion_empresa FOREIGN KEY IF NOT EXISTS (nit_empresa) REFERENCES empresa (nit) ON UPDATE CASCADE,
  ADD INDEX IF NOT EXISTS idx_evolucion_empresa (nit_empresa, fecha);
ALTER TABLE carrito
  ADD CONSTRAINT fk_carrito_empresa FOREIGN KEY IF NOT EXISTS (nit_empresa) REFERENCES empresa (nit) ON UPDATE CASCADE,
  ADD INDEX IF NOT EXISTS idx_carrito_empresa (nit_empresa);
ALTER TABLE compra_online
  ADD CONSTRAINT fk_compra_online_empresa FOREIGN KEY IF NOT EXISTS (nit_empresa) REFERENCES empresa (nit) ON UPDATE CASCADE,
  ADD INDEX IF NOT EXISTS idx_compra_online_empresa_fecha (nit_empresa, fecha_venta);
ALTER TABLE envio
  ADD CONSTRAINT fk_envio_empresa FOREIGN KEY IF NOT EXISTS (nit_empresa) REFERENCES empresa (nit) ON UPDATE CASCADE,
  ADD INDEX IF NOT EXISTS idx_envio_empresa (nit_empresa);

-- Índices por empresa y fecha que pide DBA-10.
ALTER TABLE asignacion_cita ADD INDEX IF NOT EXISTS idx_cita_empresa_fecha (nit_empresa, fecha_cita);
ALTER TABLE venta           ADD INDEX IF NOT EXISTS idx_venta_empresa_fecha (nit_empresa, fecha_venta);

-- B.3 Sin "óptica por defecto": un INSERT que olvide la empresa ya no cae en Visilux.
--     (En citas, horarios, ventas, pedidos y lotes la pone el trigger desde el registro padre.)
ALTER TABLE asignacion_cita    ALTER COLUMN nit_empresa DROP DEFAULT;
ALTER TABLE categoria_producto ALTER COLUMN nit_empresa DROP DEFAULT;
ALTER TABLE categoria_servicio ALTER COLUMN nit_empresa DROP DEFAULT;
ALTER TABLE enfermedades       ALTER COLUMN nit_empresa DROP DEFAULT;
ALTER TABLE especialidad       ALTER COLUMN nit_empresa DROP DEFAULT;
ALTER TABLE horario            ALTER COLUMN nit_empresa DROP DEFAULT;
ALTER TABLE lote               ALTER COLUMN nit_empresa DROP DEFAULT;
ALTER TABLE marca              ALTER COLUMN nit_empresa DROP DEFAULT;
ALTER TABLE medicamento        ALTER COLUMN nit_empresa DROP DEFAULT;
ALTER TABLE metodo_compra      ALTER COLUMN nit_empresa DROP DEFAULT;
ALTER TABLE modelo             ALTER COLUMN nit_empresa DROP DEFAULT;
ALTER TABLE motivos_cita       ALTER COLUMN nit_empresa DROP DEFAULT;
ALTER TABLE pedido             ALTER COLUMN nit_empresa DROP DEFAULT;
ALTER TABLE procedimiento      ALTER COLUMN nit_empresa DROP DEFAULT;
ALTER TABLE producto           ALTER COLUMN nit_empresa DROP DEFAULT;
ALTER TABLE proveedor          ALTER COLUMN nit_empresa DROP DEFAULT;
ALTER TABLE talla              ALTER COLUMN nit_empresa DROP DEFAULT;
ALTER TABLE tipo_cita          ALTER COLUMN nit_empresa DROP DEFAULT;
ALTER TABLE tipo_producto      ALTER COLUMN nit_empresa DROP DEFAULT;
ALTER TABLE tipo_terapia       ALTER COLUMN nit_empresa DROP DEFAULT;
ALTER TABLE transportadora     ALTER COLUMN nit_empresa DROP DEFAULT;
ALTER TABLE venta              ALTER COLUMN nit_empresa DROP DEFAULT;
ALTER TABLE venta_medicamento  ALTER COLUMN nit_empresa DROP DEFAULT;

-- B.4 Triggers que ponen (y protegen) la empresa en cada registro nuevo.
DROP TRIGGER IF EXISTS trg_cita_empresa_bi;
DROP TRIGGER IF EXISTS trg_horario_empresa_bi;
DROP TRIGGER IF EXISTS trg_hc_empresa_bi;
DROP TRIGGER IF EXISTS trg_formula_empresa_bi;
DROP TRIGGER IF EXISTS trg_diagnostico_bi;
DROP TRIGGER IF EXISTS trg_agenda_proc_empresa_bi;
DROP TRIGGER IF EXISTS trg_evolucion_empresa_bi;
DROP TRIGGER IF EXISTS trg_carrito_empresa_bi;
DROP TRIGGER IF EXISTS trg_compra_online_empresa_bi;
DROP TRIGGER IF EXISTS trg_envio_empresa_bi;
DROP TRIGGER IF EXISTS trg_venta_empresa_bi;
DROP TRIGGER IF EXISTS trg_pedido_empresa_bi;
DROP TRIGGER IF EXISTS trg_venta_med_empresa_bi;
DROP TRIGGER IF EXISTS trg_lote_empresa_bi;

DELIMITER $$
-- Cita: la empresa sale del paciente (o del optómetra) y el optómetra debe ser de esa óptica.
CREATE TRIGGER trg_cita_empresa_bi BEFORE INSERT ON asignacion_cita FOR EACH ROW
BEGIN
  DECLARE v_nit_opt BIGINT UNSIGNED;
  SELECT nit_empresa INTO v_nit_opt FROM optometra WHERE doc_optometra = NEW.id_optometra;
  IF NEW.nit_empresa IS NULL THEN
    SET NEW.nit_empresa = COALESCE((SELECT nit_empresa FROM usuario WHERE documento = NEW.id_usuario), v_nit_opt);
  END IF;
  IF v_nit_opt IS NOT NULL AND NOT (v_nit_opt <=> NEW.nit_empresa) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El optómetra elegido no pertenece a esta óptica.';
  END IF;
END$$

CREATE TRIGGER trg_horario_empresa_bi BEFORE INSERT ON horario FOR EACH ROW
BEGIN
  IF NEW.nit_empresa IS NULL AND NEW.id_optometra IS NOT NULL THEN
    SET NEW.nit_empresa = (SELECT nit_empresa FROM optometra WHERE doc_optometra = NEW.id_optometra);
  END IF;
END$$

CREATE TRIGGER trg_hc_empresa_bi BEFORE INSERT ON historia_clinica FOR EACH ROW
BEGIN
  SET NEW.nit_empresa = COALESCE((SELECT nit_empresa FROM asignacion_cita WHERE id_cita = NEW.id_asignacioncita), NEW.nit_empresa);
END$$

CREATE TRIGGER trg_formula_empresa_bi BEFORE INSERT ON formula_medica FOR EACH ROW
BEGIN
  SET NEW.nit_empresa = COALESCE((SELECT nit_empresa FROM usuario WHERE documento = NEW.documento_paciente), NEW.nit_empresa);
END$$

-- Diagnóstico: empresa de la fórmula y, si la fórmula ya tiene historia, queda ligado a ella (DBA-12).
CREATE TRIGGER trg_diagnostico_bi BEFORE INSERT ON diagnostico FOR EACH ROW
BEGIN
  IF NEW.id_formula IS NOT NULL THEN
    SET NEW.nit_empresa = COALESCE((SELECT nit_empresa FROM formula_medica WHERE id_formula = NEW.id_formula), NEW.nit_empresa);
    IF NEW.id_historia_clinica IS NULL THEN
      SET NEW.id_historia_clinica = (SELECT MIN(id_historia_clinica) FROM historia_clinica WHERE id_formula = NEW.id_formula);
    END IF;
  END IF;
END$$

CREATE TRIGGER trg_agenda_proc_empresa_bi BEFORE INSERT ON agendamiento_procedimiento FOR EACH ROW
BEGIN
  SET NEW.nit_empresa = COALESCE(
      (SELECT nit_empresa FROM historia_clinica WHERE id_historia_clinica = NEW.id_historia_clinica),
      (SELECT nit_empresa FROM formula_medica WHERE id_formula = NEW.id_formula),
      NEW.nit_empresa);
END$$

CREATE TRIGGER trg_evolucion_empresa_bi BEFORE INSERT ON evolucion_terapia FOR EACH ROW
BEGIN
  SET NEW.nit_empresa = COALESCE(
      (SELECT nit_empresa FROM agendamiento_procedimiento WHERE id_agendamiento_procedimiento = NEW.id_agendamiento_procedimiento),
      NEW.nit_empresa);
END$$

CREATE TRIGGER trg_carrito_empresa_bi BEFORE INSERT ON carrito FOR EACH ROW
BEGIN
  SET NEW.nit_empresa = COALESCE((SELECT nit_empresa FROM usuario WHERE documento = NEW.id_usuario), NEW.nit_empresa);
END$$

CREATE TRIGGER trg_compra_online_empresa_bi BEFORE INSERT ON compra_online FOR EACH ROW
BEGIN
  SET NEW.nit_empresa = COALESCE((SELECT nit_empresa FROM usuario WHERE documento = NEW.id_usuario), NEW.nit_empresa);
END$$

CREATE TRIGGER trg_envio_empresa_bi BEFORE INSERT ON envio FOR EACH ROW
BEGIN
  SET NEW.nit_empresa = COALESCE((SELECT nit_empresa FROM venta WHERE id_venta = NEW.id_venta), NEW.nit_empresa);
END$$

CREATE TRIGGER trg_venta_empresa_bi BEFORE INSERT ON venta FOR EACH ROW
BEGIN
  IF NEW.nit_empresa IS NULL THEN
    SET NEW.nit_empresa = (SELECT nit_empresa FROM usuario WHERE documento = NEW.id_usuario);
  END IF;
END$$

CREATE TRIGGER trg_pedido_empresa_bi BEFORE INSERT ON pedido FOR EACH ROW
BEGIN
  IF NEW.nit_empresa IS NULL THEN
    SET NEW.nit_empresa = (SELECT nit_empresa FROM usuario WHERE documento = NEW.id_usuario);
  END IF;
END$$

CREATE TRIGGER trg_venta_med_empresa_bi BEFORE INSERT ON venta_medicamento FOR EACH ROW
BEGIN
  IF NEW.nit_empresa IS NULL THEN
    SET NEW.nit_empresa = (SELECT nit_empresa FROM usuario WHERE documento = NEW.id_usuario);
  END IF;
END$$

CREATE TRIGGER trg_lote_empresa_bi BEFORE INSERT ON lote FOR EACH ROW
BEGIN
  IF NEW.nit_empresa IS NULL THEN
    SET NEW.nit_empresa = (SELECT nit_empresa FROM producto WHERE id_producto = NEW.id_producto);
  END IF;
  -- Un lote que entra sin unidades queda "Agotado" desde el principio.
  IF NEW.stock_actual = 0 AND NEW.id_estado = 1 THEN
    SET NEW.id_estado = COALESCE((SELECT id_estado FROM estado WHERE nom_estado = 'Agotado' LIMIT 1), NEW.id_estado);
  END IF;
END$$
DELIMITER ;

-- #############################################################################
-- C. INVENTARIO (DBA-08 / DBA-09)
-- #############################################################################
-- Las ventas que ya existían NUNCA descontaron inventario: quedan marcadas con mueve_stock = 0
-- para que anularlas no "devuelva" unidades que nunca salieron. Las nuevas llevan 1.
ALTER TABLE detalle_venta_producto ADD COLUMN IF NOT EXISTS mueve_stock TINYINT(1) NOT NULL DEFAULT 0
  COMMENT '1 = esta línea descontó inventario (ventas hechas desde estas mejoras)';
ALTER TABLE detalle_venta_producto ALTER COLUMN mueve_stock SET DEFAULT 1;

DROP PROCEDURE IF EXISTS sp_descontar_stock;
DROP PROCEDURE IF EXISTS sp_devolver_stock;
DROP TRIGGER IF EXISTS trg_lote_estado_bu;
DROP TRIGGER IF EXISTS trg_lote_au;
DROP TRIGGER IF EXISTS trg_dvp_stock_ai;
DROP TRIGGER IF EXISTS trg_dvp_stock_au;
DROP TRIGGER IF EXISTS trg_dvp_stock_ad;
DROP TRIGGER IF EXISTS trg_venta_stock_au;

DELIMITER $$
-- Descuenta unidades de los lotes del producto, del más antiguo al más nuevo (FIFO).
-- Si el producto no maneja lotes (servicio, producto sin inventario) no hace nada.
-- Si maneja lotes y no alcanza, detiene la venta.
CREATE PROCEDURE sp_descontar_stock(IN p_producto INT, IN p_cantidad INT)
BEGIN
  DECLARE v_pendiente INT DEFAULT p_cantidad;
  DECLARE v_lote INT;
  DECLARE v_stock INT;
  DECLARE v_disponible INT;
  DECLARE v_msg VARCHAR(255);

  IF p_cantidad > 0 AND EXISTS (SELECT 1 FROM lote WHERE id_producto = p_producto) THEN
    SELECT COALESCE(SUM(stock_actual), 0) INTO v_disponible
      FROM lote WHERE id_producto = p_producto AND id_estado IN (1, 9);
    IF v_disponible < p_cantidad THEN
      SET v_msg = CONCAT('Stock insuficiente para el producto ', p_producto, ': hay ', v_disponible, ' y se piden ', p_cantidad, '.');
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
    END IF;

    WHILE v_pendiente > 0 DO
      SELECT id_lote, stock_actual INTO v_lote, v_stock
        FROM lote
       WHERE id_producto = p_producto AND id_estado IN (1, 9) AND stock_actual > 0
       ORDER BY fecha_ingreso, id_lote
       LIMIT 1
       FOR UPDATE;
      UPDATE lote SET stock_actual = stock_actual - LEAST(v_stock, v_pendiente) WHERE id_lote = v_lote;
      SET v_pendiente = v_pendiente - LEAST(v_stock, v_pendiente);
    END WHILE;
  END IF;
END$$

-- Devuelve unidades al lote más reciente del producto (anulación o corrección de una venta).
CREATE PROCEDURE sp_devolver_stock(IN p_producto INT, IN p_cantidad INT)
BEGIN
  IF p_cantidad > 0 THEN
    UPDATE lote SET stock_actual = stock_actual + p_cantidad
     WHERE id_lote = (SELECT id_lote FROM (SELECT id_lote FROM lote WHERE id_producto = p_producto
                                            ORDER BY fecha_ingreso DESC, id_lote DESC LIMIT 1) x);
  END IF;
END$$

-- Lote: "Agotado" cuando llega a 0 y vuelve a "Activo" cuando recibe unidades.
CREATE TRIGGER trg_lote_estado_bu BEFORE UPDATE ON lote FOR EACH ROW
BEGIN
  DECLARE v_agotado INT;
  SELECT id_estado INTO v_agotado FROM estado WHERE nom_estado = 'Agotado' LIMIT 1;
  IF v_agotado IS NOT NULL THEN
    IF NEW.stock_actual = 0 AND NEW.id_estado = 1 THEN
      SET NEW.id_estado = v_agotado;
    ELSEIF NEW.stock_actual > 0 AND NEW.id_estado = v_agotado THEN
      SET NEW.id_estado = 1;
    END IF;
  END IF;
END$$

-- Lote: alerta de stock bajo (DBA-09) y registro del cambio de precio de venta (DBA-08:
-- el precio real está en lote.precio_venta; el trigger viejo vigilaba producto.precio, que nadie usa).
CREATE TRIGGER trg_lote_au AFTER UPDATE ON lote FOR EACH ROW
BEGIN
  IF NEW.stock_actual <= NEW.stock_minimo AND OLD.stock_actual > OLD.stock_minimo THEN
    INSERT INTO alerta_stock (id_lote, id_producto, producto, stock_actual, stock_minimo, nit_empresa)
    SELECT NEW.id_lote, NEW.id_producto, p.nombre_producto, NEW.stock_actual, NEW.stock_minimo, NEW.nit_empresa
      FROM producto p WHERE p.id_producto = NEW.id_producto;
  END IF;
  IF NOT (OLD.precio_venta <=> NEW.precio_venta) THEN
    INSERT INTO log_precio_producto (id_producto, producto, precio_anterior, precio_nuevo)
    SELECT NEW.id_producto, CONCAT(p.nombre_producto, ' (lote ', NEW.id_lote, ')'), OLD.precio_venta, NEW.precio_venta
      FROM producto p WHERE p.id_producto = NEW.id_producto;
  END IF;
END$$

-- Detalle de venta: al vender se descuenta; al corregir o borrar el detalle se ajusta.
-- Las ventas anuladas (Cancelado = 4) no mueven inventario.
CREATE TRIGGER trg_dvp_stock_ai AFTER INSERT ON detalle_venta_producto FOR EACH ROW
BEGIN
  IF NEW.mueve_stock = 1 AND (SELECT id_estado FROM venta WHERE id_venta = NEW.id_venta) <> 4 THEN
    CALL sp_descontar_stock(NEW.id_producto, NEW.cantidad);
  END IF;
END$$

CREATE TRIGGER trg_dvp_stock_au AFTER UPDATE ON detalle_venta_producto FOR EACH ROW
BEGIN
  IF OLD.mueve_stock = 1 AND NEW.mueve_stock = 1
     AND (SELECT id_estado FROM venta WHERE id_venta = NEW.id_venta) <> 4
     AND (OLD.id_producto <> NEW.id_producto OR OLD.cantidad <> NEW.cantidad) THEN
    CALL sp_devolver_stock(OLD.id_producto, OLD.cantidad);
    CALL sp_descontar_stock(NEW.id_producto, NEW.cantidad);
  END IF;
END$$

CREATE TRIGGER trg_dvp_stock_ad AFTER DELETE ON detalle_venta_producto FOR EACH ROW
BEGIN
  IF OLD.mueve_stock = 1 AND (SELECT id_estado FROM venta WHERE id_venta = OLD.id_venta) <> 4 THEN
    CALL sp_devolver_stock(OLD.id_producto, OLD.cantidad);
  END IF;
END$$

-- Venta: si se anula devuelve todo; si se reactiva lo vuelve a descontar.
CREATE TRIGGER trg_venta_stock_au AFTER UPDATE ON venta FOR EACH ROW
BEGIN
  DECLARE v_fin INT DEFAULT 0;
  DECLARE v_producto INT;
  DECLARE v_cantidad INT;
  DECLARE c CURSOR FOR SELECT id_producto, cantidad FROM detalle_venta_producto WHERE id_venta = NEW.id_venta AND mueve_stock = 1;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_fin = 1;

  IF NOT (OLD.id_estado <=> NEW.id_estado) AND (OLD.id_estado = 4 OR NEW.id_estado = 4) THEN
    OPEN c;
    lectura: LOOP
      FETCH c INTO v_producto, v_cantidad;
      IF v_fin = 1 THEN LEAVE lectura; END IF;
      IF NEW.id_estado = 4 THEN
        CALL sp_devolver_stock(v_producto, v_cantidad);
      ELSE
        CALL sp_descontar_stock(v_producto, v_cantidad);
      END IF;
    END LOOP;
    CLOSE c;
  END IF;
END$$
DELIMITER ;

-- #############################################################################
-- D. MODELO CLÍNICO (DBA-12)
-- #############################################################################
-- D.1 Una historia clínica por cita (además del trigger trg_historia_clinica_bi).
ALTER TABLE historia_clinica ADD UNIQUE INDEX IF NOT EXISTS uk_hc_cita (id_asignacioncita);

-- D.2 Diagnósticos 1:N con la historia (una consulta puede tener varios, uno por ojo).
--     historia_clinica.id_diagnostico se conserva (diagnóstico principal) para no romper la app.
UPDATE diagnostico d JOIN historia_clinica h ON h.id_formula = d.id_formula
   SET d.id_historia_clinica = h.id_historia_clinica WHERE d.id_historia_clinica IS NULL;
UPDATE diagnostico d JOIN historia_clinica h ON h.id_diagnostico = d.id_diagnostico
   SET d.id_historia_clinica = h.id_historia_clinica WHERE d.id_historia_clinica IS NULL;
ALTER TABLE diagnostico
  ADD CONSTRAINT fk_diag_historia FOREIGN KEY IF NOT EXISTS (id_historia_clinica)
      REFERENCES historia_clinica (id_historia_clinica) ON DELETE RESTRICT ON UPDATE CASCADE;

DROP TRIGGER IF EXISTS trg_hc_diagnosticos_ai;
DELIMITER $$
CREATE TRIGGER trg_hc_diagnosticos_ai AFTER INSERT ON historia_clinica FOR EACH ROW
BEGIN
  IF NEW.id_formula IS NOT NULL THEN
    UPDATE diagnostico SET id_historia_clinica = NEW.id_historia_clinica
     WHERE id_formula = NEW.id_formula AND id_historia_clinica IS NULL;
  END IF;
END$$
DELIMITER ;

-- D.3 Datos clínicos del paciente (1:1 con usuario): nacimiento, sexo y acudiente.
CREATE TABLE IF NOT EXISTS paciente (
  documento           VARCHAR(20) NOT NULL,
  fecha_nacimiento    DATE NULL,
  sexo                ENUM('F','M','X') NULL,
  documento_acudiente VARCHAR(20) NULL COMMENT 'Obligatorio para menores de 18 años',
  parentesco_acudiente VARCHAR(40) NULL,
  nit_empresa         BIGINT(20) UNSIGNED NULL,
  created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (documento),
  KEY idx_paciente_empresa (nit_empresa),
  KEY idx_paciente_acudiente (documento_acudiente),
  CONSTRAINT fk_paciente_usuario   FOREIGN KEY (documento) REFERENCES usuario (documento) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_paciente_acudiente FOREIGN KEY (documento_acudiente) REFERENCES usuario (documento) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_paciente_empresa   FOREIGN KEY (nit_empresa) REFERENCES empresa (nit) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Datos clínicos del paciente (1:1 con usuario). Dato sensible, Ley 1581/2012.';

DROP TRIGGER IF EXISTS trg_paciente_bi;
DROP TRIGGER IF EXISTS trg_paciente_bu;
DROP PROCEDURE IF EXISTS sp_validar_paciente;
DELIMITER $$
-- El tipo de documento debe corresponder a la edad: registro civil < 7 años,
-- tarjeta de identidad 7 a 17, cédula de ciudadanía 18+. Pasaporte y cédula de
-- extranjería sirven a cualquier edad. Un menor de 18 necesita acudiente.
CREATE PROCEDURE sp_validar_paciente(IN p_doc VARCHAR(20), IN p_nacimiento DATE, IN p_acudiente VARCHAR(20))
BEGIN
  DECLARE v_tipo INT;
  DECLARE v_edad INT;
  SELECT id_tipo_docu INTO v_tipo FROM usuario WHERE documento = p_doc;
  IF p_nacimiento IS NOT NULL THEN
    IF p_nacimiento > CURDATE() THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La fecha de nacimiento no puede ser futura.';
    END IF;
    SET v_edad = TIMESTAMPDIFF(YEAR, p_nacimiento, CURDATE());
    IF v_tipo = 1 AND v_edad < 18 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Un menor de 18 años no tiene cédula de ciudadanía: use tarjeta de identidad o registro civil.';
    ELSEIF v_tipo = 2 AND (v_edad < 7 OR v_edad >= 18) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La tarjeta de identidad es para personas de 7 a 17 años.';
    ELSEIF v_tipo = 6 AND v_edad >= 7 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El registro civil es para menores de 7 años; a partir de esa edad use tarjeta de identidad.';
    END IF;
    IF v_edad < 18 AND p_acudiente IS NULL THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Un paciente menor de edad debe tener un acudiente registrado.';
    END IF;
  END IF;
  IF p_acudiente IS NOT NULL AND p_acudiente = p_doc THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El acudiente no puede ser el mismo paciente.';
  END IF;
END$$

CREATE TRIGGER trg_paciente_bi BEFORE INSERT ON paciente FOR EACH ROW
BEGIN
  CALL sp_validar_paciente(NEW.documento, NEW.fecha_nacimiento, NEW.documento_acudiente);
  SET NEW.nit_empresa = COALESCE((SELECT nit_empresa FROM usuario WHERE documento = NEW.documento), NEW.nit_empresa);
END$$

CREATE TRIGGER trg_paciente_bu BEFORE UPDATE ON paciente FOR EACH ROW
BEGIN
  CALL sp_validar_paciente(NEW.documento, NEW.fecha_nacimiento, NEW.documento_acudiente);
END$$
DELIMITER ;

-- #############################################################################
-- E. SEGURIDAD Y LIMPIEZA (DBA-01 / DBA-05 / DBA-13 / DBA-14)
-- #############################################################################
-- E.1 Cuenta del optómetra (DBA-01): la contraseña en texto plano ya quedó cifrada (bcrypt) en
--     visioptica_Corregido. Desde el 25-11-2026 el optómetra inicia sesión con SU cuenta de la
--     tabla optometra (correo + contrasena): ver 2026_11_25_optometra_cuenta_propia.sql.

-- E.2 Datos de prueba que no deben llegar a producción (solo si nadie los usa).
DELETE c FROM ciudad c
 WHERE c.id_ciudad = 777
   AND NOT EXISTS (SELECT 1 FROM usuario u WHERE u.id_ciudad = c.id_ciudad);
DELETE d FROM departamento d
 WHERE d.id_departamento = 555
   AND NOT EXISTS (SELECT 1 FROM ciudad c WHERE c.id_departamento = d.id_departamento);
DELETE FROM cache WHERE expiration < UNIX_TIMESTAMP();
DELETE FROM cache_locks WHERE expiration < UNIX_TIMESTAMP();

-- E.3 Una sola collation (las tablas de Laravel venían en utf8mb4_unicode_ci y
--     las de negocio en utf8mb4_general_ci: "Illegal mix of collations" al cruzarlas).
ALTER TABLE password_reset_tokens CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE sessions              CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE cache                 CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE cache_locks           CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE jobs                  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE job_batches           CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE failed_jobs           CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE migrations            CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- E.4 Estados por dominio (DBA-14): para saber qué estado sirve en cada tabla.
ALTER TABLE estado ADD COLUMN IF NOT EXISTS dominio
  ENUM('general','cita','venta','envio','inventario','cuenta') NOT NULL DEFAULT 'general';
UPDATE estado SET dominio = 'cuenta'     WHERE nom_estado IN ('Activo','Inactivo','Suspendido');
UPDATE estado SET dominio = 'cita'       WHERE nom_estado = 'Confirmada';
UPDATE estado SET dominio = 'envio'      WHERE nom_estado IN ('Enviado','Entregado');
UPDATE estado SET dominio = 'inventario' WHERE nom_estado = 'Agotado';

-- #############################################################################
-- F. SIN EVENTOS PROGRAMADOS
-- #############################################################################
-- Se decidió no usar eventos (el planificador de XAMPP viene apagado). Si una versión
-- anterior de este script creó ev_purga_operativa, aquí se elimina.
DROP EVENT IF EXISTS ev_purga_operativa;

-- #############################################################################
-- G. DICCIONARIO MÍNIMO (DBA-16)
-- #############################################################################
ALTER TABLE historia_clinica COMMENT = 'Registro de cada atención clínica. Dato sensible (Ley 1581/2012). Retención mínima 15 años desde la última atención (Res. 839/2017). No se borra.';
ALTER TABLE formula_medica   COMMENT = 'Prescripción óptica (OD/OI). Dato sensible (Ley 1581/2012). Retención mínima 15 años (Res. 839/2017). doc_optometra = profesional que firma.';
ALTER TABLE diagnostico      COMMENT = 'Diagnósticos CIE-10 de la consulta, uno por ojo (1:N con historia_clinica).';
ALTER TABLE asignacion_cita  COMMENT = 'Agenda: paciente + optómetra + fecha/hora. Sin doble reserva (trg_cita_valida_horario_*).';
ALTER TABLE optometra        COMMENT = 'Optómetra: TODA su información y su cuenta (correo + contrasena bcrypt). No tiene fila en usuario. contrasena NULL = registrado sin cuenta.';
ALTER TABLE usuario          COMMENT = 'Cuentas del sistema (superadmin, administradores, empleados y clientes). Los optómetras NO van aquí: están en optometra.';
ALTER TABLE tipo_documento   COMMENT = 'Tipos de documento y su regla de validación (patron, long_min, long_max, solo_numeros).';
ALTER TABLE lote             COMMENT = 'Inventario por lote. precio_venta es el precio de venta; las ventas descuentan stock_actual (FIFO).';
ALTER TABLE simulacion_visual COMMENT = 'Simulaciones del MSD Vision Simulator mostradas a un paciente.';

-- Registro en Laravel
INSERT IGNORE INTO migrations (migration, batch)
SELECT '2026_11_24_002000_mejoras_auditoria_y_documentos', COALESCE(MAX(batch), 0) + 1 FROM migrations
 WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_11_24_002000_mejoras_auditoria_y_documentos');

-- FIN.
