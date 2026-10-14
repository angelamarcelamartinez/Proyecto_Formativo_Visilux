-- =====================================================================
-- Horarios de atención de citas de LUNES a SÁBADO para cada optómetra
-- activo: mañana 08:00–12:00 y tarde 14:00–18:00 (turnos de 30 min).
--
-- Se puede ejecutar varias veces sin duplicar: solo crea el bloque si
-- ese optómetra no tiene ya un bloque de citas que se cruce con él.
-- Después se pueden ajustar o bloquear desde Admin > Horarios.
-- =====================================================================

SET NAMES utf8mb4;

INSERT INTO `horario` (`dia_semana`, `hora_inicio`, `hora_fin`, `tipo_bloque`, `estado`, `motivo_bloqueo`, `id_optometra`)
SELECT d.dia, b.ini, b.fin, 'cita', 'libre', NULL, o.doc_optometra
FROM `optometra` o
CROSS JOIN (
    SELECT 'Lunes' AS dia UNION ALL SELECT 'Martes' UNION ALL SELECT 'Miércoles'
    UNION ALL SELECT 'Jueves' UNION ALL SELECT 'Viernes' UNION ALL SELECT 'Sábado'
) d
CROSS JOIN (
    SELECT '08:00:00' AS ini, '12:00:00' AS fin UNION ALL SELECT '14:00:00', '18:00:00'
) b
WHERE o.id_estado = 1
  AND NOT EXISTS (
      SELECT 1 FROM `horario` h
      WHERE h.id_optometra = o.doc_optometra
        AND h.dia_semana = d.dia
        AND h.tipo_bloque = 'cita'
        AND h.hora_inicio < b.fin
        AND h.hora_fin > b.ini
  );
