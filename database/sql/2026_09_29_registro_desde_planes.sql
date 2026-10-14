-- =====================================================================
-- VisiOptica: registro de ópticas desde la página de planes
-- Ejecutar UNA vez en phpMyAdmin (base `optometria`, pestaña SQL),
-- después de 2026_09_28_superadmin_y_paginas.sql.
-- =====================================================================

-- 1) Una óptica que se registra y paga queda "pendiente" hasta que el
--    superadmin aprueba el pago. Ahí pasa a "activa".
ALTER TABLE empresa
  MODIFY estado ENUM('activa','suspendida','pendiente') NOT NULL DEFAULT 'activa'
  COMMENT 'pendiente = registrada desde /planes, esperando aprobación del pago';


-- 2) La vista del estado de las licencias ahora reconoce ese caso.
CREATE OR REPLACE VIEW v_estado_licencias AS
SELECT
  e.nit AS nit,
  e.nombre AS empresa,
  e.email AS email,
  e.estado AS estado_empresa,
  e.prueba_usada AS prueba_usada,
  l.id_licencia AS id_licencia,
  p.nombre AS plan,
  p.es_prueba AS es_prueba,
  l.fecha_inicio AS fecha_inicio,
  l.fecha_fin AS fecha_fin,
  TO_DAYS(l.fecha_fin) - TO_DAYS(CURDATE()) AS dias_restantes,
  CASE
    WHEN e.estado = 'pendiente'  THEN 'pendiente_pago'
    WHEN e.estado = 'suspendida' THEN 'suspendida'
    WHEN l.id_licencia IS NULL   THEN 'sin_licencia'
    WHEN l.fecha_fin < CURDATE() THEN 'vencida'
    WHEN TO_DAYS(l.fecha_fin) - TO_DAYS(CURDATE()) <= 30 THEN 'por_vencer'
    ELSE 'vigente'
  END AS estado_licencia,
  (SELECT COUNT(*) FROM usuario u WHERE u.nit_empresa = e.nit) AS usuarios_registrados,
  (SELECT COUNT(*) FROM licencia lp WHERE lp.nit_empresa = e.nit AND lp.estado = 'pendiente') AS solicitudes_pendientes
FROM empresa e
LEFT JOIN licencia l ON l.id_licencia = (
  SELECT l2.id_licencia FROM licencia l2
  WHERE l2.nit_empresa = e.nit AND l2.estado IN ('activa', 'vencida')
  ORDER BY l2.fecha_fin DESC
  LIMIT 1
)
LEFT JOIN plan p ON p.id_plan = l.id_plan;
