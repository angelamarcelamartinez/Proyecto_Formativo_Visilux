-- =============================================================================
-- VISIOPTICA — SOLO LOS TRIGGERS NECESARIOS (25-11-2026)
-- -----------------------------------------------------------------------------
-- Decisión del equipo: pocos triggers, fáciles de explicar. Lo demás lo valida la
-- aplicación (Laravel), que ya revisa documento, rangos de la fórmula, horarios
-- libres, cita cancelada, óptica de cada registro, etc.
--
-- QUEDAN 8 TRIGGERS:
--   Bitácora (los 5 básicos)
--     1. trg_log_acceso              usuario    cada ingreso de un usuario -> log_acceso
--     2. trg_optometra_bitacora      optometra  lo mismo que 1 y 3, para el optómetra
--                                               (ingreso -> log_acceso, cambio de estado -> log_estado_usuario)
--     3. trg_usuario_cambio_estado   usuario    activar / inactivar -> log_estado_usuario
--     4. trg_cita_cambio_estado      asignacion_cita  cambio de estado -> log_cambio_cita
--     5. trg_producto_cambio_precio  producto   precio anterior y nuevo -> log_precio_producto
--   Inventario
--     6. trg_lote_stock_bajo         lote       el stock llega al mínimo -> alerta_stock
--     7. trg_venta_descuenta_stock   detalle_venta_producto  la venta descuenta del lote (o se rechaza si no hay)
--   Necesario para que la app funcione bien
--     8. trg_historia_clinica_ai     historia_clinica  al guardar la consulta, la cita queda "Completado"
--
-- Que un documento o correo no se repita entre usuario y optometra lo valida Laravel
-- (registro, Equipo, recepción, contratar): ya no hay trigger para eso.
--
-- Va al FINAL (después de 2026_11_25_optometra_cuenta_propia.sql).
-- Se puede importar más de una vez.
-- =============================================================================
SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- 1. Quitar los triggers que sobran
-- -----------------------------------------------------------------------------
-- Validación del documento (la hace Laravel: App\Rules\DocumentoValido)
DROP TRIGGER IF EXISTS trg_usuario_documento_bi;
DROP TRIGGER IF EXISTS trg_usuario_documento_bu;
DROP TRIGGER IF EXISTS trg_optometra_documento_bi;
DROP TRIGGER IF EXISTS trg_optometra_documento_bu;
-- Solo el superadmin sin óptica (Laravel siempre guarda la óptica)
DROP TRIGGER IF EXISTS trg_usuario_valida_empresa_ins;
DROP TRIGGER IF EXISTS trg_usuario_valida_empresa_upd;
-- Copias entre usuario y optometra (ya no existen) y "no repetir datos entre tablas" (lo valida Laravel)
DROP TRIGGER IF EXISTS trg_usuario_optometra_ai;
DROP TRIGGER IF EXISTS trg_usuario_optometra_au;
DROP TRIGGER IF EXISTS trg_usuario_unico_bi;
DROP TRIGGER IF EXISTS trg_usuario_unico_bu;
DROP TRIGGER IF EXISTS trg_optometra_unico_bi;
DROP TRIGGER IF EXISTS trg_optometra_unico_bu;
-- Doble reserva de citas (Laravel solo ofrece los turnos libres: App\Support\AgendaCitas)
DROP TRIGGER IF EXISTS trg_cita_valida_horario_ins;
DROP TRIGGER IF EXISTS trg_cita_valida_horario_upd;
-- Óptica de cada registro (Laravel la guarda; en historias, fórmulas, carrito... se sabe por el registro padre)
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
-- Reglas de la historia y la fórmula (las valida ConsultaController)
DROP TRIGGER IF EXISTS trg_historia_clinica_bi;
DROP TRIGGER IF EXISTS trg_formula_medica_bi;
DROP TRIGGER IF EXISTS trg_formula_medica_bu;
DROP TRIGGER IF EXISTS trg_hc_diagnosticos_ai;
-- Bitácora detallada de historias y fórmulas (P-11)
DROP TRIGGER IF EXISTS trg_hc_audit_ins;
DROP TRIGGER IF EXISTS trg_hc_audit_upd;
DROP TRIGGER IF EXISTS trg_hc_audit_del;
DROP TRIGGER IF EXISTS trg_fm_audit_ins;
DROP TRIGGER IF EXISTS trg_fm_audit_upd;
DROP TRIGGER IF EXISTS trg_fm_audit_del;
-- Paciente (tabla que la app no usa)
DROP TRIGGER IF EXISTS trg_paciente_bi;
DROP TRIGGER IF EXISTS trg_paciente_bu;
-- Inventario complejo (se reemplaza por los dos triggers sencillos de abajo)
DROP TRIGGER IF EXISTS trg_dvp_stock_ai;
DROP TRIGGER IF EXISTS trg_dvp_stock_au;
DROP TRIGGER IF EXISTS trg_dvp_stock_ad;
DROP TRIGGER IF EXISTS trg_venta_stock_au;
DROP TRIGGER IF EXISTS trg_lote_estado_bu;
DROP TRIGGER IF EXISTS trg_lote_au;

-- Procedimientos que solo usaban esos triggers
DROP PROCEDURE IF EXISTS sp_validar_documento;
DROP PROCEDURE IF EXISTS sp_validar_paciente;
DROP PROCEDURE IF EXISTS sp_descontar_stock;
DROP PROCEDURE IF EXISTS sp_devolver_stock;

-- -----------------------------------------------------------------------------
-- 2. Columnas que solo llenaban esos triggers
-- -----------------------------------------------------------------------------
-- nit_empresa en tablas "hijas": la óptica se sabe por su registro padre
-- (historia -> cita, fórmula -> paciente, carrito -> cliente, envío -> venta...).
-- Así lo filtra App\Support\Alcance.
ALTER TABLE historia_clinica           DROP FOREIGN KEY IF EXISTS fk_hc_empresa;
ALTER TABLE formula_medica             DROP FOREIGN KEY IF EXISTS fk_formula_empresa;
ALTER TABLE diagnostico                DROP FOREIGN KEY IF EXISTS fk_diag_empresa;
ALTER TABLE agendamiento_procedimiento DROP FOREIGN KEY IF EXISTS fk_agenda_empresa;
ALTER TABLE evolucion_terapia          DROP FOREIGN KEY IF EXISTS fk_evolucion_empresa;
ALTER TABLE carrito                    DROP FOREIGN KEY IF EXISTS fk_carrito_empresa;
ALTER TABLE compra_online              DROP FOREIGN KEY IF EXISTS fk_compra_online_empresa;
ALTER TABLE envio                      DROP FOREIGN KEY IF EXISTS fk_envio_empresa;

ALTER TABLE historia_clinica           DROP INDEX IF EXISTS idx_hc_empresa_fecha,        DROP COLUMN IF EXISTS nit_empresa;
ALTER TABLE formula_medica             DROP INDEX IF EXISTS idx_formula_empresa_control, DROP COLUMN IF EXISTS nit_empresa;
ALTER TABLE diagnostico                DROP INDEX IF EXISTS idx_diag_empresa,            DROP COLUMN IF EXISTS nit_empresa;
ALTER TABLE agendamiento_procedimiento DROP INDEX IF EXISTS idx_agenda_empresa_fecha,    DROP COLUMN IF EXISTS nit_empresa;
ALTER TABLE evolucion_terapia          DROP INDEX IF EXISTS idx_evolucion_empresa,       DROP COLUMN IF EXISTS nit_empresa;
ALTER TABLE carrito                    DROP INDEX IF EXISTS idx_carrito_empresa,         DROP COLUMN IF EXISTS nit_empresa;
ALTER TABLE compra_online              DROP INDEX IF EXISTS idx_compra_online_empresa_fecha, DROP COLUMN IF EXISTS nit_empresa;
ALTER TABLE envio                      DROP INDEX IF EXISTS idx_envio_empresa,           DROP COLUMN IF EXISTS nit_empresa;

-- Marca de "esta venta ya descontó inventario" (era para devolver stock al anular).
ALTER TABLE detalle_venta_producto DROP COLUMN IF EXISTS mueve_stock;

-- -----------------------------------------------------------------------------
-- 3. Los triggers que quedan (los 4 básicos ya existen; aquí van los demás)
-- -----------------------------------------------------------------------------
DROP TRIGGER IF EXISTS trg_lote_stock_bajo;
DROP TRIGGER IF EXISTS trg_venta_descuenta_stock;
DROP TRIGGER IF EXISTS trg_historia_clinica_ai;
DROP TRIGGER IF EXISTS trg_log_acceso_optometra;
DROP TRIGGER IF EXISTS trg_optometra_bitacora;

DELIMITER $$

-- 2. El optómetra tiene su cuenta en su propia tabla, así que su bitácora va aquí:
--    - inicia sesión (cambia ultimo_acceso)     -> log_acceso          (como trg_log_acceso)
--    - lo activan o inactivan (cambia id_estado) -> log_estado_usuario (como trg_usuario_cambio_estado)
CREATE TRIGGER trg_optometra_bitacora AFTER UPDATE ON optometra FOR EACH ROW
BEGIN
  IF NEW.ultimo_acceso IS NOT NULL AND NOT (OLD.ultimo_acceso <=> NEW.ultimo_acceso) THEN
    INSERT INTO log_acceso (documento, nombre, nit_empresa, fecha_hora)
    VALUES (NEW.doc_optometra, CONCAT(NEW.nombre, ' ', NEW.apellido), NEW.nit_empresa, NEW.ultimo_acceso);
  END IF;
  IF NOT (OLD.id_estado <=> NEW.id_estado) THEN
    INSERT INTO log_estado_usuario (documento, nombre, estado_anterior, estado_nuevo, nit_empresa)
    VALUES (NEW.doc_optometra, CONCAT(NEW.nombre, ' ', NEW.apellido), OLD.id_estado, NEW.id_estado, NEW.nit_empresa);
  END IF;
END$$

-- 6. Stock bajo: cuando el stock de un lote baja hasta su mínimo, se crea una alerta.
CREATE TRIGGER trg_lote_stock_bajo AFTER UPDATE ON lote FOR EACH ROW
BEGIN
  IF NEW.stock_actual <= NEW.stock_minimo AND OLD.stock_actual > OLD.stock_minimo THEN
    INSERT INTO alerta_stock (id_lote, id_producto, producto, stock_actual, stock_minimo, nit_empresa)
    SELECT NEW.id_lote, NEW.id_producto, p.nombre_producto, NEW.stock_actual, NEW.stock_minimo, NEW.nit_empresa
      FROM producto p WHERE p.id_producto = NEW.id_producto;
  END IF;
END$$

-- 7. Al vender un producto se descuenta la cantidad del lote más antiguo que tenga suficientes
--    unidades; si ningún lote alcanza, la venta se rechaza. Al bajar el stock puede saltar el trigger 6.
CREATE TRIGGER trg_venta_descuenta_stock AFTER INSERT ON detalle_venta_producto FOR EACH ROW
BEGIN
  DECLARE v_lote INT DEFAULT NULL;

  -- Lote más antiguo que tenga TODAS las unidades que se venden.
  SELECT id_lote INTO v_lote
    FROM lote
   WHERE id_producto = NEW.id_producto AND stock_actual >= NEW.cantidad
   ORDER BY fecha_ingreso, id_lote
   LIMIT 1;

  IF v_lote IS NOT NULL THEN
    UPDATE lote SET stock_actual = stock_actual - NEW.cantidad WHERE id_lote = v_lote;
  ELSEIF EXISTS (SELECT 1 FROM lote WHERE id_producto = NEW.id_producto) THEN
    -- El producto maneja inventario pero ningún lote alcanza: no se vende.
    -- (Un producto sin lotes, como un accesorio, se vende sin descontar nada.)
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock insuficiente para ese producto.';
  END IF;
END$$

-- 8. Al guardar la historia clínica de una cita, la cita queda en estado "Completado".
CREATE TRIGGER trg_historia_clinica_ai AFTER INSERT ON historia_clinica FOR EACH ROW
BEGIN
  UPDATE asignacion_cita
     SET id_estado = COALESCE((SELECT id_estado FROM estado WHERE nom_estado = 'Completado' LIMIT 1), id_estado)
   WHERE id_cita = NEW.id_asignacioncita;
END$$

DELIMITER ;
