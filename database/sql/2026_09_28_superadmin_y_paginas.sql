-- =====================================================================
-- VisiOptica: panel del superadmin y página editable de cada óptica
-- Ejecutar UNA vez en phpMyAdmin (base `optometria`, pestaña SQL).
-- Se puede volver a ejecutar sin problema: no duplica nada.
-- =====================================================================

-- 1) Dirección web de cada óptica: /optica/{slug}
ALTER TABLE empresa
  ADD COLUMN IF NOT EXISTS slug VARCHAR(80) NULL AFTER nombre;

ALTER TABLE empresa
  ADD UNIQUE KEY IF NOT EXISTS uk_empresa_slug (slug);

UPDATE empresa SET slug = 'visilux' WHERE nit = '900000000-1' AND slug IS NULL;


-- 2) Contenido de la página pública de cada óptica.
--    Una fila por empresa. Si una óptica no tiene fila, Laravel la crea
--    sola con textos de ejemplo la primera vez que se abre su página.
--    Los campos "lista" guardan un elemento por línea.
CREATE TABLE IF NOT EXISTS pagina_empresa (
  nit_empresa         VARCHAR(20)  NOT NULL,
  logo                VARCHAR(255) NULL,

  hero_titulo         VARCHAR(80)  NULL,
  hero_subtitulo      VARCHAR(120) NULL,
  hero_texto          VARCHAR(300) NULL,
  hero_imagen         VARCHAR(255) NULL,

  stat1_valor         VARCHAR(20)  NULL,
  stat1_texto         VARCHAR(60)  NULL,
  stat2_valor         VARCHAR(20)  NULL,
  stat2_texto         VARCHAR(60)  NULL,
  stat3_valor         VARCHAR(20)  NULL,
  stat3_texto         VARCHAR(60)  NULL,

  servicios_titulo    VARCHAR(120) NULL,
  servicios_subtitulo VARCHAR(200) NULL,
  serv1_titulo        VARCHAR(120) NULL,
  serv1_texto         TEXT         NULL,
  serv1_items         TEXT         NULL COMMENT 'Un beneficio por línea',
  serv1_imagen        VARCHAR(255) NULL,
  serv2_titulo        VARCHAR(120) NULL,
  serv2_texto         TEXT         NULL,
  serv2_items         TEXT         NULL COMMENT 'Un beneficio por línea',
  serv2_imagen        VARCHAR(255) NULL,

  prof_nombre         VARCHAR(120) NULL,
  prof_cargo          VARCHAR(120) NULL,
  prof_texto          TEXT         NULL,
  prof_credenciales   TEXT         NULL COMMENT 'Una por línea: Título | Institución',
  prof_imagen         VARCHAR(255) NULL,

  porque_titulo       VARCHAR(120) NULL,
  porque_subtitulo    VARCHAR(200) NULL,
  ventaja1_titulo     VARCHAR(120) NULL,
  ventaja1_texto      VARCHAR(255) NULL,
  ventaja2_titulo     VARCHAR(120) NULL,
  ventaja2_texto      VARCHAR(255) NULL,

  footer_texto        VARCHAR(300) NULL,
  horario             TEXT         NULL COMMENT 'Un horario por línea',
  telefono            VARCHAR(40)  NULL,
  email               VARCHAR(150) NULL,
  direccion           VARCHAR(200) NULL,
  instagram           VARCHAR(255) NULL,
  facebook            VARCHAR(255) NULL,

  actualizado         DATETIME     NULL,
  PRIMARY KEY (nit_empresa),
  CONSTRAINT fk_pagina_empresa FOREIGN KEY (nit_empresa)
    REFERENCES empresa (nit) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
