-- VisiOptica: chat interno entre la recepcionista (rol 4) y el optómetra (rol 7) de la misma óptica.
-- Ejecútalo una sola vez en la base de datos (phpMyAdmin → SQL). No borra nada.
CREATE TABLE IF NOT EXISTS `chat_mensaje` (
  `id_chat` bigint(20) NOT NULL AUTO_INCREMENT,
  `nit_empresa` varchar(20) NOT NULL,
  `de_documento` int(11) NOT NULL COMMENT 'usuario.documento de quien escribe',
  `para_documento` int(11) NOT NULL COMMENT 'usuario.documento de quien recibe',
  `mensaje` text NOT NULL,
  `leido` tinyint(1) NOT NULL DEFAULT 0,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_chat`),
  KEY `idx_chat_par` (`de_documento`,`para_documento`,`id_chat`),
  KEY `idx_chat_pendientes` (`para_documento`,`leido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
