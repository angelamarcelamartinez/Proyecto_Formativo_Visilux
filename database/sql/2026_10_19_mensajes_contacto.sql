-- ============================================================================
-- VisiOptica: mensajes del formulario "Contáctanos" (página Conócenos)
-- La tabla `mensajes_contacto` ya existe (id_contactenos, nombre_contactenos,
-- correo_contactenos, telefono_contactenos, mensaje_contactenos).
-- Este script le agrega lo que necesita el panel del administrador:
--   nit_empresa        -> de qué óptica es el mensaje (cada admin ve solo los suyos)
--   estado_contactenos -> 'Por contactar' o 'Contactado'
--   fecha_contactenos  -> cuándo llegó
-- Ejecutar UNA vez en phpMyAdmin, base visilux_2, pestaña SQL.
-- ============================================================================

ALTER TABLE mensajes_contacto
  ADD COLUMN IF NOT EXISTS nit_empresa BIGINT(20) UNSIGNED DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS estado_contactenos ENUM('Por contactar','Contactado') NOT NULL DEFAULT 'Por contactar',
  ADD COLUMN IF NOT EXISTS fecha_contactenos DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- El id debe numerarse solo. Si esta línea da error porque id_contactenos no es
-- llave primaria, ejecuta en su lugar:
--   ALTER TABLE mensajes_contacto ADD PRIMARY KEY (id_contactenos), MODIFY id_contactenos INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE mensajes_contacto MODIFY id_contactenos INT(11) NOT NULL AUTO_INCREMENT;
