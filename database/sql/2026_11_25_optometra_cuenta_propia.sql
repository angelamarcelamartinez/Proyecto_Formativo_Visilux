-- =============================================================================
-- VISIOPTICA — EL OPTÓMETRA TIENE SU CUENTA EN SU PROPIA TABLA (25-11-2026)
-- -----------------------------------------------------------------------------
-- Decisión del equipo: sin datos repetidos entre tablas.
--   * TODA la información del optómetra vive en `optometra`: documento, nombre,
--     apellido, celular, correo, contraseña (bcrypt), estado, óptica, tarjeta
--     profesional y especialidad. El optómetra NO tiene fila en `usuario`.
--   * Login (App\Auth\DosTablasUserProvider): busca el correo en `usuario`; si no
--     está, en `optometra`; si tampoco está, la persona no tiene cuenta.
--   * Un optómetra con contrasena = NULL está registrado (horarios, citas) pero
--     todavía no tiene cuenta: el administrador se la crea desde Equipo.
--   * Documento, correo y celular no se pueden repetir entre `usuario` y `optometra`.
--
-- Va DESPUÉS de 2026_11_24_mejoras_auditoria.sql. Se puede importar más de una vez:
-- el paso de datos (punto 3) solo se hace la primera vez.
-- =============================================================================
SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- 1. Columnas
-- -----------------------------------------------------------------------------
ALTER TABLE optometra
  MODIFY correo VARCHAR(100) NULL COMMENT 'Correo con el que inicia sesión (único entre usuario y optometra). NULL = sin cuenta',
  MODIFY contrasena VARCHAR(255) NULL COMMENT 'Contraseña de su cuenta (bcrypt). NULL = registrado sin cuenta',
  ADD COLUMN IF NOT EXISTS remember_token VARCHAR(100) NULL COMMENT 'Opción "recordarme" del login' AFTER contrasena;

-- La bitácora guarda quién hizo cada acción. El optómetra no está en `usuario`,
-- así que sus acciones quedan en doc_optometra.
ALTER TABLE actividad
  ADD COLUMN IF NOT EXISTS doc_optometra VARCHAR(20) NULL COMMENT 'Optómetra que hizo la acción (si no fue un usuario)' AFTER documento,
  ADD INDEX IF NOT EXISTS idx_actividad_optometra (doc_optometra);
ALTER TABLE actividad
  ADD CONSTRAINT fk_actividad_optometra FOREIGN KEY IF NOT EXISTS (doc_optometra)
      REFERENCES optometra (doc_optometra) ON DELETE SET NULL ON UPDATE CASCADE;

-- -----------------------------------------------------------------------------
-- 2. Ya no hay sincronización usuario -> optometra
-- -----------------------------------------------------------------------------
DROP TRIGGER IF EXISTS trg_usuario_optometra_ai;
DROP TRIGGER IF EXISTS trg_usuario_optometra_au;
DROP TRIGGER IF EXISTS trg_usuario_unico_bi;
DROP TRIGGER IF EXISTS trg_usuario_unico_bu;
DROP TRIGGER IF EXISTS trg_optometra_unico_bi;
DROP TRIGGER IF EXISTS trg_optometra_unico_bu;

-- -----------------------------------------------------------------------------
-- 3. Pasar los optómetras de `usuario` a `optometra` (solo la primera vez)
-- -----------------------------------------------------------------------------
DELIMITER $$
BEGIN NOT ATOMIC
  IF NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_11_25_optometra_cuenta_propia') THEN

    -- Un optómetra que además sea cliente (carrito, ventas, citas como paciente...) no se puede
    -- quitar de `usuario` sin perder esos datos: se avisa antes de cambiar nada.
    IF EXISTS (SELECT 1 FROM usuario u WHERE u.id_rol = 7 AND (
         EXISTS (SELECT 1 FROM carrito x WHERE x.id_usuario = u.documento)
      OR EXISTS (SELECT 1 FROM venta x WHERE x.id_usuario = u.documento)
      OR EXISTS (SELECT 1 FROM venta_medicamento x WHERE x.id_usuario = u.documento)
      OR EXISTS (SELECT 1 FROM pedido x WHERE x.id_usuario = u.documento)
      OR EXISTS (SELECT 1 FROM compra_online x WHERE x.id_usuario = u.documento)
      OR EXISTS (SELECT 1 FROM asignacion_cita x WHERE x.id_usuario = u.documento)
      OR EXISTS (SELECT 1 FROM formula_medica x WHERE x.documento_paciente = u.documento)
      OR EXISTS (SELECT 1 FROM paciente x WHERE x.documento = u.documento OR x.documento_acudiente = u.documento))) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT =
        'Hay un optometra (rol 7) que tambien es cliente o paciente en la tabla usuario. Revise ese caso antes de importar.';
    END IF;

    -- Optómetras que nunca tuvieron usuario: su contraseña vieja (la de texto plano que DBA-01
    -- cifró) deja de servir. Quedan registrados SIN cuenta hasta que el administrador se la cree.
    UPDATE optometra
       SET contrasena = NULL
     WHERE doc_optometra NOT IN (SELECT documento FROM usuario WHERE id_rol = 7);
    UPDATE optometra SET correo = NULL WHERE correo IN ('', '0');

    -- Optómetras con usuario: su cuenta (correo, contraseña, estado) pasa a su fila de `optometra`.
    INSERT INTO optometra (doc_optometra, nombre, apellido, telefono, correo, contrasena, fecha_creacion,
                           ultimo_acceso, id_tipo_docu, id_estado, nit_empresa, especialidad)
    SELECT u.documento, u.nombres, u.apellido, u.telefono, u.email, u.password, IFNULL(u.fecha_creacion, NOW()),
           u.ultimo_acceso, u.id_tipo_docu, u.id_estado, u.nit_empresa, 'Optometría Clínica'
      FROM usuario u
     WHERE u.id_rol = 7
       AND NOT EXISTS (SELECT 1 FROM optometra o WHERE o.doc_optometra = u.documento);

    UPDATE optometra o
      JOIN usuario u ON u.documento = o.doc_optometra AND u.id_rol = 7
       SET o.nombre = u.nombres,
           o.apellido = u.apellido,
           o.telefono = u.telefono,
           o.correo = u.email,
           o.contrasena = u.password,
           o.remember_token = u.remember_token,
           o.id_estado = u.id_estado,
           o.nit_empresa = u.nit_empresa,
           o.id_tipo_docu = u.id_tipo_docu,
           o.ultimo_acceso = IFNULL(u.ultimo_acceso, o.ultimo_acceso);

    -- Su historial en la bitácora pasa a doc_optometra.
    UPDATE actividad a
      JOIN usuario u ON u.documento = a.documento AND u.id_rol = 7
       SET a.doc_optometra = a.documento,
           a.documento = NULL;

    DELETE FROM usuario WHERE id_rol = 7;

    INSERT INTO migrations (migration, batch)
    SELECT '2026_11_25_optometra_cuenta_propia', IFNULL(MAX(batch), 0) + 1 FROM migrations;
  END IF;
END$$
DELIMITER ;

-- El correo es el usuario del login: no se repite entre optómetras (varios NULL sí se permiten).
ALTER TABLE optometra ADD UNIQUE INDEX IF NOT EXISTS uk_optometra_correo_login (correo);

-- -----------------------------------------------------------------------------
-- 4. Sin datos repetidos entre `usuario` y `optometra`
--    (van después de trg_*_documento_*, que limpian el documento antes de comparar)
-- -----------------------------------------------------------------------------
DELIMITER $$

CREATE TRIGGER trg_usuario_unico_bi BEFORE INSERT ON usuario FOR EACH ROW
FOLLOWS trg_usuario_documento_bi
BEGIN
  IF NEW.id_rol = 7 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El optómetra se registra en la tabla optometra, no en usuario.';
  END IF;
  IF EXISTS (SELECT 1 FROM optometra WHERE doc_optometra = NEW.documento) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ese documento ya pertenece a un optómetra.';
  END IF;
  IF EXISTS (SELECT 1 FROM optometra WHERE correo = NEW.email) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ese correo ya pertenece a un optómetra.';
  END IF;
  IF EXISTS (SELECT 1 FROM optometra WHERE telefono = NEW.telefono) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ese celular ya pertenece a un optómetra.';
  END IF;
END$$

CREATE TRIGGER trg_usuario_unico_bu BEFORE UPDATE ON usuario FOR EACH ROW
FOLLOWS trg_usuario_documento_bu
BEGIN
  IF NEW.id_rol = 7 AND NOT (OLD.id_rol <=> 7) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El optómetra se registra en la tabla optometra, no en usuario.';
  END IF;
  IF NOT (OLD.documento <=> NEW.documento)
     AND EXISTS (SELECT 1 FROM optometra WHERE doc_optometra = NEW.documento) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ese documento ya pertenece a un optómetra.';
  END IF;
  IF NOT (OLD.email <=> NEW.email)
     AND EXISTS (SELECT 1 FROM optometra WHERE correo = NEW.email) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ese correo ya pertenece a un optómetra.';
  END IF;
  IF NOT (OLD.telefono <=> NEW.telefono)
     AND EXISTS (SELECT 1 FROM optometra WHERE telefono = NEW.telefono) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ese celular ya pertenece a un optómetra.';
  END IF;
END$$

CREATE TRIGGER trg_optometra_unico_bi BEFORE INSERT ON optometra FOR EACH ROW
FOLLOWS trg_optometra_documento_bi
BEGIN
  IF EXISTS (SELECT 1 FROM usuario WHERE documento = NEW.doc_optometra) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ese documento ya pertenece a un usuario del sistema.';
  END IF;
  IF NEW.correo IS NOT NULL AND EXISTS (SELECT 1 FROM usuario WHERE email = NEW.correo) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ese correo ya pertenece a un usuario del sistema.';
  END IF;
  IF EXISTS (SELECT 1 FROM usuario WHERE telefono = NEW.telefono) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ese celular ya pertenece a un usuario del sistema.';
  END IF;
  IF NEW.contrasena IS NOT NULL AND NEW.correo IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Un optómetra con cuenta necesita un correo para iniciar sesión.';
  END IF;
END$$

CREATE TRIGGER trg_optometra_unico_bu BEFORE UPDATE ON optometra FOR EACH ROW
FOLLOWS trg_optometra_documento_bu
BEGIN
  IF NOT (OLD.doc_optometra <=> NEW.doc_optometra)
     AND EXISTS (SELECT 1 FROM usuario WHERE documento = NEW.doc_optometra) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ese documento ya pertenece a un usuario del sistema.';
  END IF;
  IF NOT (OLD.correo <=> NEW.correo) AND NEW.correo IS NOT NULL
     AND EXISTS (SELECT 1 FROM usuario WHERE email = NEW.correo) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ese correo ya pertenece a un usuario del sistema.';
  END IF;
  IF NOT (OLD.telefono <=> NEW.telefono)
     AND EXISTS (SELECT 1 FROM usuario WHERE telefono = NEW.telefono) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ese celular ya pertenece a un usuario del sistema.';
  END IF;
  IF NEW.contrasena IS NOT NULL AND NEW.correo IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Un optómetra con cuenta necesita un correo para iniciar sesión.';
  END IF;
END$$

-- Bitácora de ingresos del optómetra (igual que trg_log_acceso de usuario).
DROP TRIGGER IF EXISTS trg_log_acceso_optometra$$
CREATE TRIGGER trg_log_acceso_optometra AFTER UPDATE ON optometra FOR EACH ROW
BEGIN
  IF NEW.ultimo_acceso IS NOT NULL AND NOT (OLD.ultimo_acceso <=> NEW.ultimo_acceso) THEN
    INSERT INTO log_acceso (documento, nombre, nit_empresa, fecha_hora)
    VALUES (NEW.doc_optometra, CONCAT(NEW.nombre, ' ', NEW.apellido), NEW.nit_empresa, NEW.ultimo_acceso);
  END IF;
END$$

DELIMITER ;

-- -----------------------------------------------------------------------------
-- 5. Documentación
-- -----------------------------------------------------------------------------
ALTER TABLE optometra COMMENT = 'Optómetra: TODA su información y su cuenta (correo + contrasena bcrypt). No tiene fila en usuario. contrasena NULL = registrado sin cuenta.';
ALTER TABLE usuario   COMMENT = 'Cuentas del sistema (superadmin, administradores, empleados y clientes). Los optómetras NO van aquí: están en optometra.';
