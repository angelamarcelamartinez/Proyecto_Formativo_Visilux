-- =====================================================================
--  VisiOptica · Tabla de mensajes del formulario "Contáctanos"
--  ---------------------------------------------------------------
--  Solo hace falta si tu base NO tiene la tabla `mensajes_contacto`
--  (sin ella falla Admin › Mensajes de contacto y no se guardan los
--  mensajes que llegan desde la página Conócenos).
--  Ya trae las columnas del script 2026_10_19_mensajes_contacto.sql,
--  así que no hace falta correr ese después.
--  Si la tabla ya existe, este script no cambia nada.
-- =====================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `mensajes_contacto` (
  `id_contactenos`       INT(11)             NOT NULL AUTO_INCREMENT,
  `nombre_contactenos`   VARCHAR(150)        NOT NULL,
  `correo_contactenos`   VARCHAR(50)         NOT NULL,
  `telefono_contactenos` VARCHAR(20)         DEFAULT NULL,
  `mensaje_contactenos`  TEXT                NOT NULL,
  `nit_empresa`          BIGINT(20) UNSIGNED DEFAULT NULL,
  `estado_contactenos`   ENUM('Por contactar','Contactado') NOT NULL DEFAULT 'Por contactar',
  `fecha_contactenos`    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_contactenos`),
  KEY `idx_mensajes_nit` (`nit_empresa`),
  CONSTRAINT `fk_mensajes_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
