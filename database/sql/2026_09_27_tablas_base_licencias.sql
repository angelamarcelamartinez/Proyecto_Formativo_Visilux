-- =====================================================================
-- VisiOptica: tablas base del sistema de licencias
-- Ejecutar PRIMERO (antes de 2026_09_28_superadmin_y_paginas.sql) en
-- phpMyAdmin, base `optometria`, pestaña SQL.
-- Se puede volver a ejecutar sin problema: no duplica ni borra nada.
--
-- Corrige el error:  Table 'optometria.licencia' doesn't exist
-- =====================================================================
SET NAMES utf8mb4;

-- 1) Ópticas clientes (identificadas por NIT)
CREATE TABLE IF NOT EXISTS empresa (
  nit            VARCHAR(20)  NOT NULL,
  nombre         VARCHAR(150) NOT NULL,
  slug           VARCHAR(80)  NULL,
  email          VARCHAR(150) NULL,
  telefono       VARCHAR(30)  NULL,
  direccion      VARCHAR(200) NULL,
  ciudad         VARCHAR(100) NULL,
  estado         ENUM('activa','suspendida','pendiente') NOT NULL DEFAULT 'activa',
  prueba_usada   TINYINT(1)   NOT NULL DEFAULT 0,
  fecha_registro DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (nit)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2) Planes (los precios son el valor TOTAL del plan; los edita el superadmin)
CREATE TABLE IF NOT EXISTS plan (
  id_plan   INT(11)      NOT NULL AUTO_INCREMENT,
  nombre    VARCHAR(80)  NOT NULL,
  meses     INT(11)      NOT NULL,
  precio    DECIMAL(12,2) NOT NULL DEFAULT 0,
  es_prueba TINYINT(1)   NOT NULL DEFAULT 0,
  destacado TINYINT(1)   NOT NULL DEFAULT 0,
  activo    TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (id_plan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3) Licencias (una fila por plan contratado o solicitado)
CREATE TABLE IF NOT EXISTS licencia (
  id_licencia        INT(11)       NOT NULL AUTO_INCREMENT,
  nit_empresa        VARCHAR(20)   NOT NULL,
  id_plan            INT(11)       NOT NULL,
  estado             ENUM('pendiente','activa','vencida','rechazada','cancelada') NOT NULL DEFAULT 'pendiente',
  fecha_inicio       DATE          NULL,
  fecha_fin          DATE          NULL,
  valor              DECIMAL(12,2) NOT NULL DEFAULT 0,
  referencia_pago    VARCHAR(100)  NULL,
  observaciones      VARCHAR(500)  NULL,
  fecha_solicitud    DATETIME      NULL,
  fecha_aprobacion   DATETIME      NULL,
  aviso_pago_enviado DATETIME      NULL,
  PRIMARY KEY (id_licencia),
  KEY idx_licencia_empresa (nit_empresa, estado),
  KEY idx_licencia_plan (id_plan),
  CONSTRAINT fk_licencia_empresa FOREIGN KEY (nit_empresa) REFERENCES empresa (nit) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_licencia_plan FOREIGN KEY (id_plan) REFERENCES plan (id_plan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4) Planes iniciales (solo si la tabla está vacía). Revisa los precios en el panel del superadmin.
INSERT INTO plan (nombre, meses, precio, es_prueba, destacado, activo)
SELECT * FROM (
  SELECT 'FREE 1 mes' AS nombre, 1 AS meses, 0 AS precio, 1 AS es_prueba, 0 AS destacado, 1 AS activo
  UNION ALL SELECT '6 meses', 6, 299999, 0, 1, 1
  UNION ALL SELECT '9 meses', 9, 449999, 0, 0, 1
  UNION ALL SELECT '12 meses', 12, 599999, 0, 0, 1
) AS iniciales
WHERE NOT EXISTS (SELECT 1 FROM plan);

-- 5) Óptica principal (la que se muestra en "/") y su licencia de 1 año,
--    solo si no existen todavía.
INSERT INTO empresa (nit, nombre, slug, email, estado)
SELECT '900000000-1', 'Visilux', 'visilux', 'info@visioptica.com', 'activa'
WHERE NOT EXISTS (SELECT 1 FROM empresa WHERE nit = '900000000-1');

INSERT INTO licencia (nit_empresa, id_plan, estado, fecha_inicio, fecha_fin, valor, fecha_solicitud, fecha_aprobacion)
SELECT '900000000-1', (SELECT id_plan FROM plan WHERE meses = 12 LIMIT 1), 'activa',
       CURDATE(), DATE_ADD(CURDATE(), INTERVAL 12 MONTH), 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM licencia WHERE nit_empresa = '900000000-1')
  AND EXISTS (SELECT 1 FROM plan WHERE meses = 12);

-- 6) A qué óptica pertenece cada registro (si tus tablas ya tienen la columna, no pasa nada)
ALTER TABLE usuario                ADD COLUMN IF NOT EXISTS nit_empresa VARCHAR(20) NULL DEFAULT '900000000-1';
ALTER TABLE asignacion_cita        ADD COLUMN IF NOT EXISTS nit_empresa VARCHAR(20) NULL DEFAULT '900000000-1';
ALTER TABLE usuarios_no_registrados ADD COLUMN IF NOT EXISTS nit_empresa VARCHAR(20) NULL DEFAULT '900000000-1';
ALTER TABLE pedido                 ADD COLUMN IF NOT EXISTS nit_empresa VARCHAR(20) NULL DEFAULT '900000000-1';
ALTER TABLE lote                   ADD COLUMN IF NOT EXISTS nit_empresa VARCHAR(20) NULL DEFAULT '900000000-1';
ALTER TABLE venta                  ADD COLUMN IF NOT EXISTS nit_empresa VARCHAR(20) NULL DEFAULT '900000000-1';
ALTER TABLE venta_medicamento      ADD COLUMN IF NOT EXISTS nit_empresa VARCHAR(20) NULL DEFAULT '900000000-1';
