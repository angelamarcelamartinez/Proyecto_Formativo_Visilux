/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.14-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: prueba_bd
-- ------------------------------------------------------
-- Server version	10.11.14-MariaDB-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `actividad`
--

DROP TABLE IF EXISTS `actividad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `actividad` (
  `id_actividad` bigint(20) NOT NULL AUTO_INCREMENT,
  `nit_empresa` bigint(20) unsigned DEFAULT NULL,
  `documento` int(11) DEFAULT NULL COMMENT 'Usuario que hizo la acción',
  `accion` varchar(30) NOT NULL COMMENT 'login | crear | editar | eliminar | licencia',
  `tabla` varchar(60) DEFAULT NULL,
  `id_registro` varchar(60) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_actividad`),
  KEY `idx_actividad_empresa_fecha` (`nit_empresa`,`fecha`),
  CONSTRAINT `fk_actividad_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `actividad`
--

LOCK TABLES `actividad` WRITE;
/*!40000 ALTER TABLE `actividad` DISABLE KEYS */;
INSERT INTO `actividad` VALUES
(1,9000000001,NULL,'editar','usuario','11111111','Cuenta inactivada por 4 intentos fallidos de inicio de sesión: pipepatarroyo2006@gmail.com','2026-10-13 11:09:14'),
(2,9000000001,999999999,'editar','usuario','11111111','Usuario activado por el administrador: pipepatarroyo2006@gmail.com','2026-10-13 11:09:55'),
(3,9000000001,NULL,'editar','usuario','11111111','Cuenta inactivada por 4 intentos fallidos de inicio de sesión: pipepatarroyo2006@gmail.com','2026-10-13 11:26:45');
/*!40000 ALTER TABLE `actividad` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agendamiento_procedimiento`
--

DROP TABLE IF EXISTS `agendamiento_procedimiento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `agendamiento_procedimiento` (
  `id_agendamiento_procedimiento` int(11) NOT NULL AUTO_INCREMENT,
  `fecha_inicio` datetime NOT NULL,
  `fecha_final` datetime DEFAULT NULL,
  `id_tipo_terapia` int(11) NOT NULL,
  `id_formula` int(11) DEFAULT NULL,
  `tipo` enum('examen visual','terapia visual','adaptacion lentes contacto') NOT NULL DEFAULT 'terapia visual',
  `estado` varchar(50) DEFAULT NULL,
  `id_historia_clinica` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_agendamiento_procedimiento`),
  KEY `id_tipo_terapia` (`id_tipo_terapia`),
  KEY `id_formula` (`id_formula`),
  KEY `id_historia_clinica` (`id_historia_clinica`),
  CONSTRAINT `agend_proc_ibfk_1` FOREIGN KEY (`id_tipo_terapia`) REFERENCES `tipo_terapia` (`id_tipo_terapia`),
  CONSTRAINT `agend_proc_ibfk_2` FOREIGN KEY (`id_formula`) REFERENCES `formula_medica` (`id_formula`),
  CONSTRAINT `agend_proc_ibfk_3` FOREIGN KEY (`id_historia_clinica`) REFERENCES `historia_clinica` (`id_historia_clinica`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agendamiento_procedimiento`
--

LOCK TABLES `agendamiento_procedimiento` WRITE;
/*!40000 ALTER TABLE `agendamiento_procedimiento` DISABLE KEYS */;
INSERT INTO `agendamiento_procedimiento` VALUES
(1,'2026-06-10 09:00:00','2026-06-10 10:00:00',1,NULL,'terapia visual','Activo',2),
(2,'2026-06-12 10:00:00','2026-06-12 11:00:00',2,NULL,'terapia visual','Activo',2),
(3,'2026-06-14 08:00:00','2026-06-14 09:00:00',3,NULL,'examen visual','Activo',3),
(4,'2026-06-17 09:00:00','2026-06-17 10:00:00',4,NULL,'adaptacion lentes contacto','Activo',4),
(5,'2026-06-19 10:00:00','2026-06-19 11:00:00',5,NULL,'terapia visual','Activo',1);
/*!40000 ALTER TABLE `agendamiento_procedimiento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asignacion_cita`
--

DROP TABLE IF EXISTS `asignacion_cita`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `asignacion_cita` (
  `id_cita` int(11) NOT NULL AUTO_INCREMENT,
  `fecha_cita` datetime NOT NULL,
  `hora_cita` time NOT NULL,
  `observaciones` text DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_optometra` int(11) NOT NULL,
  `id_tipo_cita` int(11) NOT NULL,
  `id_estado` int(11) NOT NULL,
  `id_motivo` int(11) DEFAULT NULL,
  `nit_empresa` bigint(20) unsigned DEFAULT 9000000001,
  PRIMARY KEY (`id_cita`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_optometra` (`id_optometra`),
  KEY `id_tipo_cita` (`id_tipo_cita`),
  KEY `id_estado` (`id_estado`),
  KEY `id_motivo` (`id_motivo`),
  KEY `fk_asignacion_cita_empresa` (`nit_empresa`),
  CONSTRAINT `asignacion_cita_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`documento`),
  CONSTRAINT `asignacion_cita_ibfk_2` FOREIGN KEY (`id_optometra`) REFERENCES `optometra` (`doc_optometra`),
  CONSTRAINT `asignacion_cita_ibfk_3` FOREIGN KEY (`id_tipo_cita`) REFERENCES `tipo_cita` (`id_tipo_cita`),
  CONSTRAINT `asignacion_cita_ibfk_4` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_estado`),
  CONSTRAINT `asignacion_cita_ibfk_5` FOREIGN KEY (`id_motivo`) REFERENCES `motivos_cita` (`id_motivo`),
  CONSTRAINT `fk_asignacion_cita_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asignacion_cita`
--

LOCK TABLES `asignacion_cita` WRITE;
/*!40000 ALTER TABLE `asignacion_cita` DISABLE KEYS */;
INSERT INTO `asignacion_cita` VALUES
(1,'2026-06-05 00:00:00','08:00:00',NULL,11111111,50001001,1,3,1,9000000001),
(2,'2026-06-05 00:00:00','09:00:00',NULL,22222222,50001001,3,3,2,9000000001),
(3,'2026-06-06 00:00:00','10:00:00',NULL,33333333,50001001,4,3,3,9000000001),
(4,'2026-06-06 00:00:00','11:00:00',NULL,44444444,50001001,2,3,4,9000000001),
(5,'2026-06-07 00:00:00','08:30:00',NULL,55555555,50001001,5,3,5,9000000001);
/*!40000 ALTER TABLE `asignacion_cita` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES
('visilux-cache-424f74a6a7ed4d4ed4761507ebcd209a6ef0937b','i:9;',1791907792),
('visilux-cache-424f74a6a7ed4d4ed4761507ebcd209a6ef0937b:timer','i:1791907792;',1791907792),
('visilux-cache-login_estado_previo:11111111','i:1;',2107268805),
('visilux-cache-pipepatarroyo2006@gmail.com|::1:timer','i:1791908849;',1791908849),
('visilux-cache-verificar-email424f74a6a7ed4d4ed4761507ebcd209a6ef0937b','i:1;',1791909309),
('visilux-cache-verificar-email424f74a6a7ed4d4ed4761507ebcd209a6ef0937b:timer','i:1791909309;',1791909309);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `carrito`
--

DROP TABLE IF EXISTS `carrito`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `carrito` (
  `id_carrito` int(11) NOT NULL AUTO_INCREMENT,
  `fecha_actual` date NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_estado` int(11) NOT NULL,
  PRIMARY KEY (`id_carrito`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_estado` (`id_estado`),
  CONSTRAINT `carrito_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`documento`),
  CONSTRAINT `carrito_ibfk_2` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_estado`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carrito`
--

LOCK TABLES `carrito` WRITE;
/*!40000 ALTER TABLE `carrito` DISABLE KEYS */;
INSERT INTO `carrito` VALUES
(1,'2026-06-01',11111111,1),
(2,'2026-06-02',22222222,1),
(3,'2026-06-03',33333333,3),
(4,'2026-05-28',44444444,5),
(5,'2026-05-30',55555555,5);
/*!40000 ALTER TABLE `carrito` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categoria_producto`
--

DROP TABLE IF EXISTS `categoria_producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categoria_producto` (
  `id_categoria` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_categoria` text NOT NULL,
  `id_estado` int(11) NOT NULL DEFAULT 1,
  `nit_empresa` bigint(20) unsigned DEFAULT 9000000001,
  PRIMARY KEY (`id_categoria`),
  KEY `fk_categoria_estado` (`id_estado`),
  KEY `fk_categoria_producto_empresa` (`nit_empresa`),
  CONSTRAINT `fk_categoria_estado` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_estado`),
  CONSTRAINT `fk_categoria_producto_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categoria_producto`
--

LOCK TABLES `categoria_producto` WRITE;
/*!40000 ALTER TABLE `categoria_producto` DISABLE KEYS */;
INSERT INTO `categoria_producto` VALUES
(1,'Óptica',1,9000000001),
(2,'Lentes de Sol',1,9000000001),
(3,'Contactología',1,9000000001),
(4,'Pediátrica',1,9000000001),
(5,'Deportiva',1,9000000001);
/*!40000 ALTER TABLE `categoria_producto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categoria_servicio`
--

DROP TABLE IF EXISTS `categoria_servicio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categoria_servicio` (
  `id_categoria_servicio` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_categoria` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `nit_empresa` bigint(20) unsigned DEFAULT 9000000001,
  PRIMARY KEY (`id_categoria_servicio`),
  KEY `fk_categoria_servicio_empresa` (`nit_empresa`),
  CONSTRAINT `fk_categoria_servicio_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categoria_servicio`
--

LOCK TABLES `categoria_servicio` WRITE;
/*!40000 ALTER TABLE `categoria_servicio` DISABLE KEYS */;
INSERT INTO `categoria_servicio` VALUES
(1,'Terapias','Sesiones de terapia visual y rehabilitación ocular',9000000001),
(2,'Exámenes Visuales','Evaluaciones de agudeza visual, refracción y salud ocular',9000000001),
(3,'Entrega de Gafas','Entrega y ajuste de monturas con lentes formulados',9000000001),
(4,'Lentes de Contacto','Adaptación, entrega y control de lentes de contacto',9000000001);
/*!40000 ALTER TABLE `categoria_servicio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ciudad`
--

DROP TABLE IF EXISTS `ciudad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ciudad` (
  `id_ciudad` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_ciudad` text NOT NULL,
  `id_departamento` int(11) NOT NULL,
  PRIMARY KEY (`id_ciudad`),
  KEY `id_departamento` (`id_departamento`),
  CONSTRAINT `ciudad_ibfk_1` FOREIGN KEY (`id_departamento`) REFERENCES `departamento` (`id_departamento`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ciudad`
--

LOCK TABLES `ciudad` WRITE;
/*!40000 ALTER TABLE `ciudad` DISABLE KEYS */;
INSERT INTO `ciudad` VALUES
(1,'Bogota',1),
(2,'Ibague',2);
/*!40000 ALTER TABLE `ciudad` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compra_online`
--

DROP TABLE IF EXISTS `compra_online`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `compra_online` (
  `id_compra_on` int(11) NOT NULL AUTO_INCREMENT,
  `imagen_formula` varchar(255) DEFAULT NULL,
  `fecha_venta` date NOT NULL,
  `impuestos` decimal(10,2) DEFAULT 0.00,
  `descuento` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_estado` int(11) NOT NULL,
  `id_metodo_compra` int(11) DEFAULT NULL,
  `id_usuario_nr` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_compra_on`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_estado` (`id_estado`),
  KEY `id_metodo_compra` (`id_metodo_compra`),
  KEY `fk_compra_usuario_no_reg` (`id_usuario_nr`),
  CONSTRAINT `compra_online_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`documento`),
  CONSTRAINT `compra_online_ibfk_2` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_estado`),
  CONSTRAINT `compra_online_ibfk_3` FOREIGN KEY (`id_metodo_compra`) REFERENCES `metodo_compra` (`id_metodo`),
  CONSTRAINT `fk_compra_usuario_no_reg` FOREIGN KEY (`id_usuario_nr`) REFERENCES `usuarios_no_registrados` (`id_usuario_nr`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compra_online`
--

LOCK TABLES `compra_online` WRITE;
/*!40000 ALTER TABLE `compra_online` DISABLE KEYS */;
INSERT INTO `compra_online` VALUES
(1,NULL,'2026-05-20',24000.00,0.00,150000.00,NULL,11111111,5,1,NULL),
(2,NULL,'2026-05-22',8000.00,5000.00,50000.00,NULL,22222222,5,4,NULL),
(3,NULL,'2026-05-25',16000.00,0.00,100000.00,NULL,33333333,6,3,NULL),
(4,NULL,'2026-05-30',5600.00,0.00,35000.00,NULL,44444444,5,5,1),
(5,NULL,'2026-06-01',13600.00,10000.00,85000.00,NULL,55555555,3,2,2);
/*!40000 ALTER TABLE `compra_online` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departamento`
--

DROP TABLE IF EXISTS `departamento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `departamento` (
  `id_departamento` int(11) NOT NULL AUTO_INCREMENT,
  `nom_departamento` text NOT NULL,
  PRIMARY KEY (`id_departamento`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departamento`
--

LOCK TABLES `departamento` WRITE;
/*!40000 ALTER TABLE `departamento` DISABLE KEYS */;
INSERT INTO `departamento` VALUES
(1,'Cundinamarca'),
(2,'Tolima');
/*!40000 ALTER TABLE `departamento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_carrito_producto`
--

DROP TABLE IF EXISTS `detalle_carrito_producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_carrito_producto` (
  `id_detalle_carr_pro` int(11) NOT NULL AUTO_INCREMENT,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `id_carrito` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  PRIMARY KEY (`id_detalle_carr_pro`),
  KEY `id_carrito` (`id_carrito`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `detalle_carrito_producto_ibfk_1` FOREIGN KEY (`id_carrito`) REFERENCES `carrito` (`id_carrito`),
  CONSTRAINT `detalle_carrito_producto_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_carrito_producto`
--

LOCK TABLES `detalle_carrito_producto` WRITE;
/*!40000 ALTER TABLE `detalle_carrito_producto` DISABLE KEYS */;
INSERT INTO `detalle_carrito_producto` VALUES
(1,1,150000.00,150000.00,1,1),
(2,2,65000.00,130000.00,1,6),
(3,1,220000.00,220000.00,2,3),
(4,1,35000.00,35000.00,3,9),
(5,1,130000.00,130000.00,4,8);
/*!40000 ALTER TABLE `detalle_carrito_producto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_formula_medica_medicamento`
--

DROP TABLE IF EXISTS `detalle_formula_medica_medicamento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_formula_medica_medicamento` (
  `id_detalle` int(11) NOT NULL AUTO_INCREMENT,
  `id_formula` int(11) DEFAULT NULL,
  `id_medicamento` int(11) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `dosis` text DEFAULT NULL,
  PRIMARY KEY (`id_detalle`),
  KEY `id_formula` (`id_formula`),
  KEY `id_medicamento` (`id_medicamento`),
  CONSTRAINT `fk_detalle_med_formula` FOREIGN KEY (`id_formula`) REFERENCES `formula_medica` (`id_formula`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_detalle_med_medicamento` FOREIGN KEY (`id_medicamento`) REFERENCES `medicamento` (`id_medicamento`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_formula_medica_medicamento`
--

LOCK TABLES `detalle_formula_medica_medicamento` WRITE;
/*!40000 ALTER TABLE `detalle_formula_medica_medicamento` DISABLE KEYS */;
INSERT INTO `detalle_formula_medica_medicamento` VALUES
(1,1,1,1,'1 gota cada 8 horas'),
(2,2,2,2,'2 gotas cada 12 horas'),
(3,3,3,1,'1 gota cada 6 horas'),
(4,4,4,1,'1 gota antes de dormir'),
(5,5,5,1,'1 gota cada 8 horas');
/*!40000 ALTER TABLE `detalle_formula_medica_medicamento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_formula_medica_procedimiento`
--

DROP TABLE IF EXISTS `detalle_formula_medica_procedimiento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_formula_medica_procedimiento` (
  `id_detalle` int(11) NOT NULL AUTO_INCREMENT,
  `id_formula` int(11) DEFAULT NULL,
  `id_procedimiento` int(11) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_detalle`),
  KEY `id_formula` (`id_formula`),
  KEY `id_procedimiento` (`id_procedimiento`),
  CONSTRAINT `fk_detalle_proc_formula` FOREIGN KEY (`id_formula`) REFERENCES `formula_medica` (`id_formula`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_detalle_proc_procedimiento` FOREIGN KEY (`id_procedimiento`) REFERENCES `procedimiento` (`id_procedimiento`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_formula_medica_procedimiento`
--

LOCK TABLES `detalle_formula_medica_procedimiento` WRITE;
/*!40000 ALTER TABLE `detalle_formula_medica_procedimiento` DISABLE KEYS */;
INSERT INTO `detalle_formula_medica_procedimiento` VALUES
(1,1,1,5),
(2,2,2,1),
(3,3,3,3),
(4,4,1,10),
(5,5,2,1);
/*!40000 ALTER TABLE `detalle_formula_medica_procedimiento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_pedido_producto`
--

DROP TABLE IF EXISTS `detalle_pedido_producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_pedido_producto` (
  `id_detalle_pe_pro` int(11) NOT NULL AUTO_INCREMENT,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_proveedor_producto` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_detalle_pe_pro`),
  KEY `id_producto` (`id_producto`),
  KEY `id_pedido` (`id_pedido`),
  KEY `id_proveedor_producto` (`id_proveedor_producto`),
  CONSTRAINT `detalle_pedido_producto_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`),
  CONSTRAINT `detalle_pedido_producto_ibfk_2` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`),
  CONSTRAINT `detalle_pedido_producto_ibfk_3` FOREIGN KEY (`id_proveedor_producto`) REFERENCES `detalle_prov_prod` (`id_proveedor_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_pedido_producto`
--

LOCK TABLES `detalle_pedido_producto` WRITE;
/*!40000 ALTER TABLE `detalle_pedido_producto` DISABLE KEYS */;
INSERT INTO `detalle_pedido_producto` VALUES
(1,10,75000.00,750000.00,1,1,1),
(2,10,32000.00,320000.00,5,2,5),
(3,6,90000.00,540000.00,3,3,3),
(4,5,38000.00,190000.00,8,4,NULL),
(5,6,75000.00,450000.00,1,5,1);
/*!40000 ALTER TABLE `detalle_pedido_producto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_productos`
--

DROP TABLE IF EXISTS `detalle_productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_productos` (
  `id_detalle_producto` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `id_venta` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_detalle_producto`),
  KEY `id_producto` (`id_producto`),
  KEY `id_venta` (`id_venta`),
  CONSTRAINT `detalle_prod_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`),
  CONSTRAINT `detalle_prod_ibfk_2` FOREIGN KEY (`id_venta`) REFERENCES `venta` (`id_venta`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_productos`
--

LOCK TABLES `detalle_productos` WRITE;
/*!40000 ALTER TABLE `detalle_productos` DISABLE KEYS */;
INSERT INTO `detalle_productos` VALUES
(1,1,1,150000.00,1),
(2,5,2,32500.00,2),
(3,3,1,220000.00,3),
(4,8,1,130000.00,4),
(5,6,2,32500.00,5);
/*!40000 ALTER TABLE `detalle_productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_prov_prod`
--

DROP TABLE IF EXISTS `detalle_prov_prod`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_prov_prod` (
  `id_proveedor_producto` int(11) NOT NULL AUTO_INCREMENT,
  `precio_ref` decimal(10,2) NOT NULL,
  `tiempo_entrega` int(11) NOT NULL,
  `nit_prov` varchar(20) NOT NULL,
  `id_producto` int(11) NOT NULL,
  PRIMARY KEY (`id_proveedor_producto`),
  KEY `nit_prov` (`nit_prov`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `detalle_prov_prod_ibfk_1` FOREIGN KEY (`nit_prov`) REFERENCES `proveedor` (`nit`),
  CONSTRAINT `detalle_prov_prod_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_prov_prod`
--

LOCK TABLES `detalle_prov_prod` WRITE;
/*!40000 ALTER TABLE `detalle_prov_prod` DISABLE KEYS */;
INSERT INTO `detalle_prov_prod` VALUES
(1,75000.00,5,'900123456-1',1),
(2,115000.00,7,'900123456-1',2),
(3,90000.00,5,'800234567-2',3),
(4,190000.00,10,'800234567-2',4),
(5,32000.00,3,'400678901-6',5);
/*!40000 ALTER TABLE `detalle_prov_prod` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_venta_medicamento`
--

DROP TABLE IF EXISTS `detalle_venta_medicamento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_venta_medicamento` (
  `id_detalle_venta_med` int(11) NOT NULL AUTO_INCREMENT,
  `id_venta_med` int(11) NOT NULL,
  `id_medicamento` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `dosis` text DEFAULT NULL,
  PRIMARY KEY (`id_detalle_venta_med`),
  KEY `id_venta_med` (`id_venta_med`),
  KEY `id_medicamento` (`id_medicamento`),
  CONSTRAINT `det_vent_med_ibfk_1` FOREIGN KEY (`id_venta_med`) REFERENCES `venta_medicamento` (`id_venta_med`),
  CONSTRAINT `det_vent_med_ibfk_2` FOREIGN KEY (`id_medicamento`) REFERENCES `medicamento` (`id_medicamento`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_venta_medicamento`
--

LOCK TABLES `detalle_venta_medicamento` WRITE;
/*!40000 ALTER TABLE `detalle_venta_medicamento` DISABLE KEYS */;
INSERT INTO `detalle_venta_medicamento` VALUES
(1,1,1,1,25000.00,'1 gota cada 8 horas'),
(2,2,2,2,15000.00,'2 gotas cada 12 horas'),
(3,3,3,1,32000.00,'1 gota cada 6 horas'),
(4,4,4,1,45000.00,'1 gota antes de dormir'),
(5,5,5,1,38000.00,'1 gota cada 8 horas');
/*!40000 ALTER TABLE `detalle_venta_medicamento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_venta_producto`
--

DROP TABLE IF EXISTS `detalle_venta_producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_venta_producto` (
  `id_detalle_venta_producto` int(11) NOT NULL AUTO_INCREMENT,
  `cantidad` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `id_venta` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  PRIMARY KEY (`id_detalle_venta_producto`),
  KEY `id_venta` (`id_venta`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `detalle_venta_producto_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `venta` (`id_venta`),
  CONSTRAINT `detalle_venta_producto_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_venta_producto`
--

LOCK TABLES `detalle_venta_producto` WRITE;
/*!40000 ALTER TABLE `detalle_venta_producto` DISABLE KEYS */;
INSERT INTO `detalle_venta_producto` VALUES
(1,1,150000.00,1,1),
(2,2,65000.00,2,5),
(3,1,220000.00,3,3),
(4,1,130000.00,4,8),
(5,2,65000.00,5,6);
/*!40000 ALTER TABLE `detalle_venta_producto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `diagnostico`
--

DROP TABLE IF EXISTS `diagnostico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `diagnostico` (
  `id_diagnostico` int(11) NOT NULL AUTO_INCREMENT,
  `id_enfermedad` int(11) DEFAULT NULL,
  `id_formula` int(11) DEFAULT NULL,
  `ojo` varchar(2) NOT NULL,
  PRIMARY KEY (`id_diagnostico`),
  KEY `id_enfermedad` (`id_enfermedad`),
  KEY `id_formula` (`id_formula`),
  CONSTRAINT `diagnostico_ibfk_1` FOREIGN KEY (`id_enfermedad`) REFERENCES `enfermedades` (`id_enfermedad`),
  CONSTRAINT `diagnostico_ibfk_2` FOREIGN KEY (`id_formula`) REFERENCES `formula_medica` (`id_formula`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `diagnostico`
--

LOCK TABLES `diagnostico` WRITE;
/*!40000 ALTER TABLE `diagnostico` DISABLE KEYS */;
INSERT INTO `diagnostico` VALUES
(1,1,1,'OD'),
(2,3,2,'OI'),
(3,2,3,'OD'),
(4,1,4,'OD'),
(5,4,5,'OI');
/*!40000 ALTER TABLE `diagnostico` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `empresa`
--

DROP TABLE IF EXISTS `empresa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `empresa` (
  `nit` bigint(20) unsigned NOT NULL COMMENT 'NIT sin guion: 9 dÃ­gitos + dÃ­gito de verificaciÃ³n',
  `nombre` varchar(150) NOT NULL,
  `slug` varchar(80) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `camara_comercio` varchar(500) NOT NULL COMMENT 'Ruta del PDF de cÃ¡mara de comercio',
  `estado` enum('activa','suspendida','pendiente') NOT NULL DEFAULT 'activa' COMMENT 'pendiente = registrada desde /planes, esperando aprobación del pago',
  `prueba_usada` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`nit`),
  UNIQUE KEY `uk_empresa_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `empresa`
--

LOCK TABLES `empresa` WRITE;
/*!40000 ALTER TABLE `empresa` DISABLE KEYS */;
INSERT INTO `empresa` VALUES
(9000000001,'Optica Visilux','visilux','admin@visioptica.com','3000000000',NULL,'Bogota','pendiente','activa',1,'2026-10-06 07:17:42');
/*!40000 ALTER TABLE `empresa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enfermedades`
--

DROP TABLE IF EXISTS `enfermedades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `enfermedades` (
  `id_enfermedad` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_enfer` text NOT NULL,
  `descripcion` text NOT NULL,
  `codigo` varchar(15) NOT NULL,
  `nit_empresa` bigint(20) unsigned DEFAULT 9000000001,
  PRIMARY KEY (`id_enfermedad`),
  KEY `fk_enfermedades_empresa` (`nit_empresa`),
  CONSTRAINT `fk_enfermedades_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enfermedades`
--

LOCK TABLES `enfermedades` WRITE;
/*!40000 ALTER TABLE `enfermedades` DISABLE KEYS */;
INSERT INTO `enfermedades` VALUES
(1,'Miopía','Dificultad para ver de lejos','H52.1',9000000001),
(2,'Hipermetropía','Dificultad para ver de cerca','H52.0',9000000001),
(3,'Astigmatismo','Visión borrosa por córnea irregular','H52.2',9000000001),
(4,'Presbicia','Pérdida de visión cercana por edad','H52.4',9000000001),
(5,'Estrabismo','Desalineación de los ojos','H50.0',9000000001);
/*!40000 ALTER TABLE `enfermedades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `envio`
--

DROP TABLE IF EXISTS `envio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `envio` (
  `id_envio` int(11) NOT NULL AUTO_INCREMENT,
  `id_venta` int(11) NOT NULL,
  `direccion_envio` text NOT NULL,
  `ciudad` text NOT NULL,
  `fecha_envio` date DEFAULT NULL,
  `id_estado` int(11) NOT NULL,
  `nit_transportadora` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_envio`),
  KEY `id_venta` (`id_venta`),
  KEY `id_estado` (`id_estado`),
  KEY `envio_ibfk_transportadora` (`nit_transportadora`),
  CONSTRAINT `envio_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `venta` (`id_venta`),
  CONSTRAINT `envio_ibfk_2` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_estado`),
  CONSTRAINT `envio_ibfk_transportadora` FOREIGN KEY (`nit_transportadora`) REFERENCES `transportadora` (`nit`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `envio`
--

LOCK TABLES `envio` WRITE;
/*!40000 ALTER TABLE `envio` DISABLE KEYS */;
INSERT INTO `envio` VALUES
(1,1,'Cra 50 # 30-15 Apto 401','Bogotá','2026-05-07',8,1001),
(2,2,'Cl 45 # 12-30','Ibagué','2026-05-10',8,1002),
(3,3,'Av El Dorado # 68-11','Bogotá','2026-05-14',7,1003),
(4,4,'Cra 80 # 50-20','Medellín',NULL,6,NULL),
(5,5,'Cl 10 # 5-40','Cali',NULL,6,NULL);
/*!40000 ALTER TABLE `envio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `especialidad`
--

DROP TABLE IF EXISTS `especialidad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `especialidad` (
  `id_especialidad` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_especialidad` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `nit_empresa` bigint(20) unsigned DEFAULT 9000000001,
  PRIMARY KEY (`id_especialidad`),
  KEY `fk_especialidad_empresa` (`nit_empresa`),
  CONSTRAINT `fk_especialidad_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `especialidad`
--

LOCK TABLES `especialidad` WRITE;
/*!40000 ALTER TABLE `especialidad` DISABLE KEYS */;
INSERT INTO `especialidad` VALUES
(1,'Optometría Clínica','Diagnóstico y tratamiento de condiciones visuales',9000000001),
(2,'Contactología','Adaptación y seguimiento de lentes de contacto',9000000001),
(3,'Terapia Visual','Rehabilitación visual mediante ejercicios terapéuticos',9000000001),
(4,'Baja Visión','Atención a pacientes con discapacidad visual',9000000001),
(5,'Optometría Pediátrica','Atención visual especializada en niños',9000000001);
/*!40000 ALTER TABLE `especialidad` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estado`
--

DROP TABLE IF EXISTS `estado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `estado` (
  `id_estado` int(11) NOT NULL AUTO_INCREMENT,
  `nom_estado` text NOT NULL,
  PRIMARY KEY (`id_estado`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estado`
--

LOCK TABLES `estado` WRITE;
/*!40000 ALTER TABLE `estado` DISABLE KEYS */;
INSERT INTO `estado` VALUES
(1,'Activo'),
(2,'Inactivo'),
(3,'Pendiente'),
(4,'Cancelado'),
(5,'Completado'),
(6,'En proceso'),
(7,'Enviado'),
(8,'Entregado'),
(9,'Agotado'),
(10,'Suspendido'),
(11,'Confirmada');
/*!40000 ALTER TABLE `estado` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evolucion_terapia`
--

DROP TABLE IF EXISTS `evolucion_terapia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `evolucion_terapia` (
  `id_evolucion` int(11) NOT NULL AUTO_INCREMENT,
  `id_agendamiento_procedimiento` int(11) NOT NULL,
  `fecha` datetime NOT NULL,
  `descripcion_evolucion` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id_evolucion`),
  KEY `id_agendamiento_procedimiento` (`id_agendamiento_procedimiento`),
  CONSTRAINT `evolucion_ibfk_1` FOREIGN KEY (`id_agendamiento_procedimiento`) REFERENCES `agendamiento_procedimiento` (`id_agendamiento_procedimiento`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evolucion_terapia`
--

LOCK TABLES `evolucion_terapia` WRITE;
/*!40000 ALTER TABLE `evolucion_terapia` DISABLE KEYS */;
INSERT INTO `evolucion_terapia` VALUES
(1,1,'2026-06-10 10:00:00','Mejora notable en convergencia','Paciente colaborador'),
(2,2,'2026-06-12 11:00:00','Progreso leve, continuar ejercicios','Requiere más sesiones'),
(3,3,'2026-06-14 09:00:00','Examen completado sin novedad','Fórmula actualizada'),
(4,4,'2026-06-17 10:00:00','Adaptación exitosa','Tolerancia adecuada'),
(5,5,'2026-06-19 11:00:00','Sesión completada','Evolución favorable');
/*!40000 ALTER TABLE `evolucion_terapia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `formula_medica`
--

DROP TABLE IF EXISTS `formula_medica`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `formula_medica` (
  `id_formula` int(11) NOT NULL AUTO_INCREMENT,
  `documento_paciente` int(11) NOT NULL,
  `fecha_formula` date DEFAULT NULL,
  `od_esfera` decimal(5,2) DEFAULT NULL,
  `od_cilindro` decimal(5,2) DEFAULT NULL,
  `od_eje` smallint(5) unsigned DEFAULT NULL,
  `od_adc` decimal(5,2) DEFAULT NULL,
  `od_dist_pupilar` decimal(4,1) DEFAULT NULL,
  `od_av_lejos` varchar(20) DEFAULT NULL,
  `od_av_cerca` varchar(20) DEFAULT NULL,
  `oi_esfera` decimal(5,2) DEFAULT NULL,
  `oi_cilindro` decimal(5,2) DEFAULT NULL,
  `oi_eje` smallint(5) unsigned DEFAULT NULL,
  `oi_adc` decimal(5,2) DEFAULT NULL,
  `oi_dist_pupilar` decimal(4,1) DEFAULT NULL,
  `oi_av_lejos` varchar(20) DEFAULT NULL,
  `oi_av_cerca` varchar(20) DEFAULT NULL,
  `correccion` varchar(50) DEFAULT NULL,
  `forma_uso` varchar(50) DEFAULT NULL,
  `numero_dispositivos` int(11) DEFAULT NULL,
  `proximo_control` date DEFAULT NULL,
  `recomendaciones` text DEFAULT NULL,
  PRIMARY KEY (`id_formula`),
  KEY `fk_formula_usuario` (`documento_paciente`),
  CONSTRAINT `fk_formula_usuario` FOREIGN KEY (`documento_paciente`) REFERENCES `usuario` (`documento`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `formula_medica`
--

LOCK TABLES `formula_medica` WRITE;
/*!40000 ALTER TABLE `formula_medica` DISABLE KEYS */;
INSERT INTO `formula_medica` VALUES
(1,11111111,'2026-03-01',-1.50,-0.50,180,NULL,NULL,NULL,NULL,-1.25,-0.75,175,NULL,NULL,NULL,NULL,'Lentes aéreos',NULL,NULL,'2027-03-01',NULL),
(2,22222222,'2026-03-15',-2.00,-1.00,90,NULL,NULL,NULL,NULL,-2.25,-0.50,85,NULL,NULL,NULL,NULL,'Lentes aéreos',NULL,NULL,'2027-03-15',NULL),
(3,33333333,'2026-04-01',1.00,0.00,0,NULL,NULL,NULL,NULL,0.75,0.00,0,NULL,NULL,NULL,NULL,'Lentes bifocales',NULL,NULL,'2027-04-01',NULL),
(4,44444444,'2026-04-15',-3.00,-1.50,170,NULL,NULL,NULL,NULL,-2.75,-1.25,165,NULL,NULL,NULL,NULL,'Lentes de contacto',NULL,NULL,'2027-04-15',NULL),
(5,55555555,'2026-05-01',-0.50,-0.25,90,NULL,NULL,NULL,NULL,-0.75,-0.50,85,NULL,NULL,NULL,NULL,'Lentes aéreos',NULL,NULL,'2027-05-01',NULL);
/*!40000 ALTER TABLE `formula_medica` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `historia_clinica`
--

DROP TABLE IF EXISTS `historia_clinica`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `historia_clinica` (
  `id_historia_clinica` int(11) NOT NULL AUTO_INCREMENT,
  `fecha_registro` datetime NOT NULL,
  `tratamiento` text DEFAULT NULL,
  `antecedentes_clinicos` text DEFAULT NULL,
  `id_asignacioncita` int(11) NOT NULL,
  `id_formula` int(11) DEFAULT NULL,
  `recomendaciones` text DEFAULT NULL,
  `id_diagnostico` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_historia_clinica`),
  KEY `id_asignacioncita` (`id_asignacioncita`),
  KEY `id_formula` (`id_formula`),
  KEY `id_diagnostico` (`id_diagnostico`),
  CONSTRAINT `historia_clinica_ibfk_1` FOREIGN KEY (`id_asignacioncita`) REFERENCES `asignacion_cita` (`id_cita`),
  CONSTRAINT `historia_clinica_ibfk_2` FOREIGN KEY (`id_formula`) REFERENCES `formula_medica` (`id_formula`),
  CONSTRAINT `historia_clinica_ibfk_3` FOREIGN KEY (`id_diagnostico`) REFERENCES `diagnostico` (`id_diagnostico`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `historia_clinica`
--

LOCK TABLES `historia_clinica` WRITE;
/*!40000 ALTER TABLE `historia_clinica` DISABLE KEYS */;
INSERT INTO `historia_clinica` VALUES
(1,'2026-06-05 08:30:00','Corrección con lentes aéreos','Sin antecedentes',1,1,'Control en 1 año',1),
(2,'2026-06-05 09:30:00','Terapia visual 10 sesiones','Estrabismo leve previo',2,2,'Ejercicios diarios',2),
(3,'2026-06-06 10:30:00','Lentes bifocales','Hipertensión controlada',3,3,'Control en 6 meses',3),
(4,'2026-06-06 11:30:00','Lentes tóricos de contacto','Alergia a preservantes',4,4,'Revisión mensual',4),
(5,'2026-06-07 09:00:00','Lentes aéreos monofocales','Sin antecedentes relevantes',5,5,'Control en 1 año',5);
/*!40000 ALTER TABLE `historia_clinica` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `horario`
--

DROP TABLE IF EXISTS `horario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `horario` (
  `id_horario` int(11) NOT NULL AUTO_INCREMENT,
  `dia_semana` enum('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado') NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `tipo_bloque` enum('cita','terapia') NOT NULL COMMENT 'cita=30min | terapia=60min',
  `estado` enum('libre','bloqueado') NOT NULL DEFAULT 'libre',
  `motivo_bloqueo` varchar(255) DEFAULT NULL,
  `id_optometra` int(11) DEFAULT NULL,
  `nit_empresa` bigint(20) unsigned DEFAULT 9000000001,
  PRIMARY KEY (`id_horario`),
  KEY `id_optometra` (`id_optometra`),
  KEY `fk_horario_empresa` (`nit_empresa`),
  CONSTRAINT `fk_horario_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `horario`
--

LOCK TABLES `horario` WRITE;
/*!40000 ALTER TABLE `horario` DISABLE KEYS */;
INSERT INTO `horario` VALUES
(1,'Lunes','08:00:00','08:30:00','cita','libre',NULL,50001001,9000000001),
(2,'Lunes','09:00:00','10:00:00','terapia','libre',NULL,10000001,9000000001),
(3,'Martes','08:00:00','08:30:00','cita','libre',NULL,10000002,9000000001),
(4,'Miércoles','10:00:00','11:00:00','terapia','libre',NULL,10000003,9000000001),
(5,'Jueves','08:00:00','08:30:00','cita','libre',NULL,10000004,9000000001);
/*!40000 ALTER TABLE `horario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `licencia`
--

DROP TABLE IF EXISTS `licencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `licencia` (
  `id_licencia` int(11) NOT NULL AUTO_INCREMENT,
  `nit_empresa` bigint(20) unsigned NOT NULL,
  `id_plan` int(11) NOT NULL,
  `estado` enum('pendiente','activa','vencida','rechazada','cancelada') NOT NULL DEFAULT 'pendiente',
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `valor` decimal(12,2) NOT NULL DEFAULT 0.00,
  `referencia_pago` varchar(100) DEFAULT NULL,
  `observaciones` varchar(500) DEFAULT NULL,
  `fecha_solicitud` datetime DEFAULT NULL,
  `fecha_aprobacion` datetime DEFAULT NULL,
  `aviso_pago_enviado` datetime DEFAULT NULL,
  PRIMARY KEY (`id_licencia`),
  KEY `idx_licencia_empresa` (`nit_empresa`,`estado`),
  KEY `idx_licencia_plan` (`id_plan`),
  CONSTRAINT `fk_licencia_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_licencia_plan` FOREIGN KEY (`id_plan`) REFERENCES `plan` (`id_plan`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `licencia`
--

LOCK TABLES `licencia` WRITE;
/*!40000 ALTER TABLE `licencia` DISABLE KEYS */;
INSERT INTO `licencia` VALUES
(1,9000000001,4,'activa','2026-10-06','2027-10-06',0.00,NULL,NULL,'2026-10-06 07:17:42','2026-10-06 07:17:42',NULL);
/*!40000 ALTER TABLE `licencia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lote`
--

DROP TABLE IF EXISTS `lote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lote` (
  `id_lote` int(11) NOT NULL AUTO_INCREMENT,
  `precio_compra` decimal(10,2) NOT NULL,
  `precio_venta` decimal(10,2) NOT NULL,
  `stock_actual` int(11) NOT NULL,
  `stock_minimo` int(11) NOT NULL,
  `fecha_ingreso` date NOT NULL,
  `id_producto` int(11) NOT NULL,
  `id_estado` int(11) NOT NULL DEFAULT 1,
  `nit_empresa` bigint(20) unsigned DEFAULT 9000000001,
  PRIMARY KEY (`id_lote`),
  KEY `fk_lote_producto` (`id_producto`),
  KEY `fk_lote_estado` (`id_estado`),
  KEY `fk_lote_empresa` (`nit_empresa`),
  CONSTRAINT `fk_lote_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON UPDATE CASCADE,
  CONSTRAINT `fk_lote_estado` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_estado`),
  CONSTRAINT `fk_lote_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lote`
--

LOCK TABLES `lote` WRITE;
/*!40000 ALTER TABLE `lote` DISABLE KEYS */;
INSERT INTO `lote` VALUES
(1,80000.00,150000.00,30,5,'2026-01-10',1,1,9000000001),
(2,120000.00,220000.00,25,5,'2026-01-15',2,1,9000000001),
(3,95000.00,180000.00,40,8,'2026-02-01',3,1,9000000001),
(4,200000.00,380000.00,15,3,'2026-02-10',4,1,9000000001),
(5,35000.00,65000.00,60,10,'2026-02-20',5,1,9000000001);
/*!40000 ALTER TABLE `lote` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marca`
--

DROP TABLE IF EXISTS `marca`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `marca` (
  `id_marca` int(11) NOT NULL AUTO_INCREMENT,
  `nom_marca` text NOT NULL,
  `id_estado` int(11) NOT NULL DEFAULT 1,
  `nit_empresa` bigint(20) unsigned DEFAULT 9000000001,
  PRIMARY KEY (`id_marca`),
  KEY `fk_marca_estado` (`id_estado`),
  KEY `idx_marca_nit` (`nit_empresa`),
  CONSTRAINT `fk_marca_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON UPDATE CASCADE,
  CONSTRAINT `fk_marca_estado` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_estado`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marca`
--

LOCK TABLES `marca` WRITE;
/*!40000 ALTER TABLE `marca` DISABLE KEYS */;
INSERT INTO `marca` VALUES
(1,'Ray-Ban',1,9000000001),
(2,'Oakley',1,9000000001),
(3,'Carrera',1,9000000001),
(4,'Transitions',1,9000000001),
(5,'Bausch & Lomb',1,9000000001),
(6,'Alcon',1,9000000001),
(7,'CooperVision',1,9000000001),
(8,'Silhouette',1,9000000001),
(9,'Lindberg',1,9000000001),
(10,'Maui Jim',1,9000000001);
/*!40000 ALTER TABLE `marca` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `medicamento`
--

DROP TABLE IF EXISTS `medicamento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `medicamento` (
  `id_medicamento` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `lote_med` int(11) NOT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `id_estado` int(11) NOT NULL DEFAULT 1,
  `nit_empresa` bigint(20) unsigned DEFAULT 9000000001,
  PRIMARY KEY (`id_medicamento`),
  KEY `lote_med` (`lote_med`),
  KEY `fk_medicamento_estado` (`id_estado`),
  KEY `fk_medicamento_empresa` (`nit_empresa`),
  CONSTRAINT `fk_medicamento_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON UPDATE CASCADE,
  CONSTRAINT `fk_medicamento_estado` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_estado`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `medicamento`
--

LOCK TABLES `medicamento` WRITE;
/*!40000 ALTER TABLE `medicamento` DISABLE KEYS */;
INSERT INTO `medicamento` VALUES
(1,'Tobrex gotas','Antibiótico ocular',1,25000.00,50,1,9000000001),
(2,'Lágrimas artificiales','Lubricante ocular',2,15000.00,100,1,9000000001),
(3,'Voltarén oftálmico','Antiinflamatorio ocular',3,32000.00,30,1,9000000001),
(4,'Atropina 1%','Control de miopía progresiva',4,45000.00,20,1,9000000001),
(5,'Maxitrol','Antibiótico + corticoide ocular',5,38000.00,25,1,9000000001);
/*!40000 ALTER TABLE `medicamento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `metodo_compra`
--

DROP TABLE IF EXISTS `metodo_compra`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `metodo_compra` (
  `id_metodo` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_metodo` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `nit_empresa` bigint(20) unsigned DEFAULT 9000000001,
  PRIMARY KEY (`id_metodo`),
  KEY `fk_metodo_compra_empresa` (`nit_empresa`),
  CONSTRAINT `fk_metodo_compra_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `metodo_compra`
--

LOCK TABLES `metodo_compra` WRITE;
/*!40000 ALTER TABLE `metodo_compra` DISABLE KEYS */;
INSERT INTO `metodo_compra` VALUES
(1,'Tarjeta crédito','Pago con tarjeta crédito nacional o internacional',1,9000000001),
(2,'Tarjeta débito','Pago con tarjeta débito',1,9000000001),
(3,'Efectivo','Pago en efectivo en tienda física',1,9000000001),
(4,'PSE','Pago en línea a través de PSE',1,9000000001),
(5,'Nequi','Transferencia por billetera digital Nequi',1,9000000001),
(6,'Daviplata','Transferencia por billetera digital Daviplata',1,9000000001),
(7,'Transferencia bancaria','Transferencia entre cuentas bancarias',1,9000000001);
/*!40000 ALTER TABLE `metodo_compra` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1),
(4,'2026_09_16_120000_add_precio_imagen_to_producto_table',2);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modelo`
--

DROP TABLE IF EXISTS `modelo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `modelo` (
  `id_modelo` int(11) NOT NULL AUTO_INCREMENT,
  `nom_modelo` text NOT NULL,
  `id_estado` int(11) NOT NULL DEFAULT 1,
  `nit_empresa` bigint(20) unsigned DEFAULT 9000000001,
  PRIMARY KEY (`id_modelo`),
  KEY `fk_modelo_estado` (`id_estado`),
  KEY `fk_modelo_empresa` (`nit_empresa`),
  CONSTRAINT `fk_modelo_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON UPDATE CASCADE,
  CONSTRAINT `fk_modelo_estado` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_estado`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modelo`
--

LOCK TABLES `modelo` WRITE;
/*!40000 ALTER TABLE `modelo` DISABLE KEYS */;
INSERT INTO `modelo` VALUES
(1,'Wayfarer',1,9000000001),
(2,'Aviator',1,9000000001),
(3,'Clubmaster',1,9000000001),
(4,'Round Metal',1,9000000001),
(5,'Frogskins',1,9000000001),
(6,'Holbrook',1,9000000001),
(7,'Flak 2.0',1,9000000001),
(8,'Champion',1,9000000001),
(9,'Air Frame',1,9000000001),
(10,'Rimless Classic',1,9000000001);
/*!40000 ALTER TABLE `modelo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `motivos_cita`
--

DROP TABLE IF EXISTS `motivos_cita`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `motivos_cita` (
  `id_motivo` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_motivo` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `nit_empresa` bigint(20) unsigned DEFAULT 9000000001,
  PRIMARY KEY (`id_motivo`),
  KEY `fk_motivos_cita_empresa` (`nit_empresa`),
  CONSTRAINT `fk_motivos_cita_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `motivos_cita`
--

LOCK TABLES `motivos_cita` WRITE;
/*!40000 ALTER TABLE `motivos_cita` DISABLE KEYS */;
INSERT INTO `motivos_cita` VALUES
(1,'Revisión de rutina','Control periódico anual de salud visual',9000000001),
(2,'Dolor ocular','Molestia o dolor en uno o ambos ojos',9000000001),
(3,'Adaptación de lentes de contacto','Primera adaptación o cambio de lentes de contacto',9000000001),
(4,'Control postoperatorio','Seguimiento tras procedimiento quirúrgico ocular',9000000001),
(5,'Examen visual pediátrico','Primera evaluación visual en niños',9000000001),
(6,'Cambio de fórmula','Actualización de graduación óptica',9000000001),
(7,'Problemas de convergencia','Dificultad para enfocar objetos cercanos',9000000001),
(8,'Control lentes de contacto','Revisión de adaptación y tolerancia',9000000001);
/*!40000 ALTER TABLE `motivos_cita` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `optometra`
--

DROP TABLE IF EXISTS `optometra`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `optometra` (
  `doc_optometra` int(11) NOT NULL,
  `nombre` text NOT NULL,
  `apellido` text NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `ultimo_acceso` datetime DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `tarjeta_profesional` int(11) NOT NULL,
  `especialidad` text NOT NULL,
  `id_tipo_docu` int(11) NOT NULL,
  `id_estado` int(11) NOT NULL,
  `id_especialidad` int(11) DEFAULT NULL,
  `nit_empresa` bigint(20) unsigned DEFAULT 9000000001,
  PRIMARY KEY (`doc_optometra`),
  UNIQUE KEY `uk_optometra_telefono` (`nit_empresa`,`telefono`),
  UNIQUE KEY `uk_optometra_correo` (`nit_empresa`,`correo`),
  KEY `id_tipo_docu` (`id_tipo_docu`),
  KEY `id_estado` (`id_estado`),
  KEY `fk_optometra_especialidad` (`id_especialidad`),
  KEY `idx_optometra_nit` (`nit_empresa`),
  CONSTRAINT `fk_optometra_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON UPDATE CASCADE,
  CONSTRAINT `fk_optometra_especialidad` FOREIGN KEY (`id_especialidad`) REFERENCES `especialidad` (`id_especialidad`),
  CONSTRAINT `fk_optometra_tipo_docu` FOREIGN KEY (`id_tipo_docu`) REFERENCES `tipo_documento` (`id_tipo_docu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `optometra`
--

LOCK TABLES `optometra` WRITE;
/*!40000 ALTER TABLE `optometra` DISABLE KEYS */;
INSERT INTO `optometra` VALUES
(50001001,'Catalina','Esquivel','311001001','0','catalina1234','2023-01-15 08:00:00','2026-06-02 07:00:00',NULL,12345,'Optometría Clínica',1,1,NULL,9000000001);
/*!40000 ALTER TABLE `optometra` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagina_empresa`
--

DROP TABLE IF EXISTS `pagina_empresa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagina_empresa` (
  `nit_empresa` bigint(20) unsigned NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `hero_titulo` varchar(80) DEFAULT NULL,
  `hero_subtitulo` varchar(120) DEFAULT NULL,
  `hero_texto` varchar(300) DEFAULT NULL,
  `hero_imagen` varchar(255) DEFAULT NULL,
  `stat1_valor` varchar(20) DEFAULT NULL,
  `stat1_texto` varchar(60) DEFAULT NULL,
  `stat2_valor` varchar(20) DEFAULT NULL,
  `stat2_texto` varchar(60) DEFAULT NULL,
  `stat3_valor` varchar(20) DEFAULT NULL,
  `stat3_texto` varchar(60) DEFAULT NULL,
  `servicios_titulo` varchar(120) DEFAULT NULL,
  `servicios_subtitulo` varchar(200) DEFAULT NULL,
  `serv1_titulo` varchar(120) DEFAULT NULL,
  `serv1_texto` text DEFAULT NULL,
  `serv1_items` text DEFAULT NULL COMMENT 'Un beneficio por línea',
  `serv1_imagen` varchar(255) DEFAULT NULL,
  `serv2_titulo` varchar(120) DEFAULT NULL,
  `serv2_texto` text DEFAULT NULL,
  `serv2_items` text DEFAULT NULL COMMENT 'Un beneficio por línea',
  `serv2_imagen` varchar(255) DEFAULT NULL,
  `prof_nombre` varchar(120) DEFAULT NULL,
  `prof_cargo` varchar(120) DEFAULT NULL,
  `prof_texto` text DEFAULT NULL,
  `prof_credenciales` text DEFAULT NULL COMMENT 'Una por línea: Título | Institución',
  `prof_imagen` varchar(255) DEFAULT NULL,
  `porque_titulo` varchar(120) DEFAULT NULL,
  `porque_subtitulo` varchar(200) DEFAULT NULL,
  `ventaja1_titulo` varchar(120) DEFAULT NULL,
  `ventaja1_texto` varchar(255) DEFAULT NULL,
  `ventaja2_titulo` varchar(120) DEFAULT NULL,
  `ventaja2_texto` varchar(255) DEFAULT NULL,
  `footer_texto` varchar(300) DEFAULT NULL,
  `horario` text DEFAULT NULL COMMENT 'Un horario por línea',
  `telefono` varchar(40) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `actualizado` datetime DEFAULT NULL,
  PRIMARY KEY (`nit_empresa`),
  CONSTRAINT `fk_pagina_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagina_empresa`
--

LOCK TABLES `pagina_empresa` WRITE;
/*!40000 ALTER TABLE `pagina_empresa` DISABLE KEYS */;
INSERT INTO `pagina_empresa` VALUES
(9000000001,NULL,'Bienvenidos','a tu salud visual','Cuidamos de tus ojos con tecnología avanzada, atención personalizada y años de experiencia.',NULL,'15+','Años de experiencia','5000+','Pacientes felices','100%','Atención personalizada','Nuestros Servicios','Atención especializada para todas las edades','Optometría Infantil','Evaluaciones adaptadas para niños con un enfoque lúdico y profesional que genera confianza.','Examen visual completo\nDetección temprana de problemas\nAsesoría para padres',NULL,'Terapia Visual','Programas personalizados para mejorar la coordinación ocular, enfoque y rendimiento visual.','Ejercicios visuales guiados\nSeguimiento periódico\nEquipos de última generación',NULL,'Dra. Catalina Fernández','Optómetra Especializada','Con más de 15 años de experiencia, combina precisión clínica con un trato cercano.','Optometría Certificada | Universidad Nacional de Colombia\nTerapia Visual Avanzada | Instituto de Salud Visual\nPediatría y Salud Ocular | Colegio Colombiano de Optómetras',NULL,'¿Por qué elegirnos?','Somos más que una óptica, somos tu aliado en salud visual','Experiencia Comprobada','Más de 15 años cuidando la visión.','Tecnología Avanzada','Equipos modernos para diagnósticos precisos.','Somos más que una óptica: acompañamos tu salud visual con tecnología, calidez humana y experiencia comprobada.','Lun – Vie: 8:00 – 18:00\nSáb: 9:00 – 14:00\nDom: Cerrado','+57 300 123 4567','info@visioptica.com','Bogotá, Colombia',NULL,NULL,'2026-10-06 09:36:48');
/*!40000 ALTER TABLE `pagina_empresa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedido`
--

DROP TABLE IF EXISTS `pedido`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedido` (
  `id_pedido` int(11) NOT NULL AUTO_INCREMENT,
  `fecha_pedido` date NOT NULL,
  `fecha_entrega_estimada` date DEFAULT NULL,
  `total_pedido` decimal(10,2) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_proveedor` varchar(20) NOT NULL,
  `id_estado` int(11) NOT NULL,
  `nit_empresa` bigint(20) unsigned DEFAULT 9000000001,
  PRIMARY KEY (`id_pedido`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_proveedor` (`id_proveedor`),
  KEY `id_estado` (`id_estado`),
  KEY `fk_pedido_empresa` (`nit_empresa`),
  CONSTRAINT `fk_pedido_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pedido_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedor` (`nit`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedido`
--

LOCK TABLES `pedido` WRITE;
/*!40000 ALTER TABLE `pedido` DISABLE KEYS */;
INSERT INTO `pedido` VALUES
(1,'2026-05-01','2026-05-10',750000.00,'Pedido mensual rutinario',1234567890,'900123456-1',5,9000000001),
(2,'2026-05-10','2026-05-20',320000.00,'Reposición de stock',1234567890,'400678901-6',5,9000000001),
(3,'2026-05-20','2026-06-01',560000.00,'Nuevos modelos temporada',1234567890,'800234567-2',6,9000000001),
(4,'2026-06-01','2026-06-15',1200000.00,'Pedido especial progresivos',1234567890,'200890123-8',3,9000000001),
(5,'2026-06-02','2026-06-12',450000.00,'Reabastecimiento',1234567890,'300789012-7',3,9000000001);
/*!40000 ALTER TABLE `pedido` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plan`
--

DROP TABLE IF EXISTS `plan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `plan` (
  `id_plan` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `meses` int(11) NOT NULL,
  `precio` decimal(12,2) NOT NULL DEFAULT 0.00,
  `es_prueba` tinyint(1) NOT NULL DEFAULT 0,
  `destacado` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_plan`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plan`
--

LOCK TABLES `plan` WRITE;
/*!40000 ALTER TABLE `plan` DISABLE KEYS */;
INSERT INTO `plan` VALUES
(1,'FREE 1 mes',1,0.00,1,0,1),
(2,'6 meses',6,299999.00,0,1,1),
(3,'9 meses',9,449999.00,0,0,1),
(4,'12 meses',12,599999.00,0,0,1);
/*!40000 ALTER TABLE `plan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `procedimiento`
--

DROP TABLE IF EXISTS `procedimiento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `procedimiento` (
  `id_procedimiento` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `id_categoria_servicio` int(11) DEFAULT NULL,
  `nit_empresa` bigint(20) unsigned DEFAULT 9000000001,
  PRIMARY KEY (`id_procedimiento`),
  KEY `fk_procedimiento_categoria_servicio` (`id_categoria_servicio`),
  KEY `fk_procedimiento_empresa` (`nit_empresa`),
  CONSTRAINT `fk_procedimiento_categoria_servicio` FOREIGN KEY (`id_categoria_servicio`) REFERENCES `categoria_servicio` (`id_categoria_servicio`),
  CONSTRAINT `fk_procedimiento_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `procedimiento`
--

LOCK TABLES `procedimiento` WRITE;
/*!40000 ALTER TABLE `procedimiento` DISABLE KEYS */;
INSERT INTO `procedimiento` VALUES
(1,'Terapia visual','Sesiones de rehabilitación visual',NULL,9000000001),
(2,'Examen visual','Evaluación completa de agudeza visual',NULL,9000000001),
(3,'Adaptación lentes de contacto','Adaptación y control de lentes de contacto',NULL,9000000001);
/*!40000 ALTER TABLE `procedimiento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `producto`
--

DROP TABLE IF EXISTS `producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `producto` (
  `id_producto` int(11) NOT NULL,
  `nombre_producto` text NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `id_marca` int(11) DEFAULT NULL,
  `id_modelo` int(11) DEFAULT NULL,
  `id_talla` int(11) DEFAULT NULL,
  `color` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `id_categoria` int(11) DEFAULT NULL,
  `id_estado` int(11) NOT NULL,
  `id_tipo_pro` int(11) NOT NULL,
  `nit_empresa` bigint(20) unsigned DEFAULT 9000000001,
  PRIMARY KEY (`id_producto`),
  KEY `id_marca` (`id_marca`),
  KEY `id_modelo` (`id_modelo`),
  KEY `id_talla` (`id_talla`),
  KEY `id_categoria` (`id_categoria`),
  KEY `id_estado` (`id_estado`),
  KEY `id_tipo_pro` (`id_tipo_pro`),
  KEY `idx_producto_nit` (`nit_empresa`),
  CONSTRAINT `fk_producto_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria_producto` (`id_categoria`),
  CONSTRAINT `fk_producto_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON UPDATE CASCADE,
  CONSTRAINT `fk_producto_marca` FOREIGN KEY (`id_marca`) REFERENCES `marca` (`id_marca`),
  CONSTRAINT `fk_producto_modelo` FOREIGN KEY (`id_modelo`) REFERENCES `modelo` (`id_modelo`),
  CONSTRAINT `fk_producto_talla` FOREIGN KEY (`id_talla`) REFERENCES `talla` (`id_talla`),
  CONSTRAINT `fk_producto_tipo` FOREIGN KEY (`id_tipo_pro`) REFERENCES `tipo_producto` (`id_tipo_pro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto`
--

LOCK TABLES `producto` WRITE;
/*!40000 ALTER TABLE `producto` DISABLE KEYS */;
INSERT INTO `producto` VALUES
(1,'Montura Ray-Ban Wayfarer','Montura acetato clásica unisex',NULL,1,1,7,'Negro mate',NULL,NULL,1,1,1,9000000001),
(2,'Montura Oakley Frogskins','Montura deportiva ligera',NULL,2,5,8,'Azul translúcido',NULL,NULL,5,1,1,9000000001),
(3,'Montura Carrera Champion','Montura metálica para caballero',NULL,3,8,9,'Dorado',NULL,NULL,1,1,1,9000000001),
(4,'Montura Silhouette Rimless','Montura sin aro ultrafina',NULL,8,10,6,'Plata',NULL,NULL,1,1,1,9000000001),
(5,'Lentes de Contacto Bausch Mensuales','Lentes blandas mensuales para miopía',NULL,5,NULL,NULL,'Transparente',NULL,'2027-12-31',3,1,2,9000000001),
(6,'Lentes de Contacto Alcon Diarios','Lentes diarios de alta comodidad',NULL,6,NULL,NULL,'Transparente',NULL,'2027-06-30',3,1,2,9000000001),
(7,'Solución Multipropósito Bausch','Limpieza y desinfección 360ml',NULL,5,NULL,NULL,'N/A',NULL,'2028-03-15',3,1,3,9000000001),
(8,'Estuche Rígido para Montura','Estuche de protección con logo',NULL,NULL,NULL,NULL,'Negro/Gris',NULL,NULL,1,1,4,9000000001),
(9,'Paño de Microfibra','Paño limpiador antirayaduras 20x20cm',NULL,NULL,NULL,NULL,'Azul',NULL,NULL,1,1,4,9000000001),
(10,'Lentes Transitions Signature','Cristales fotosensibles que oscurecen al sol',NULL,4,NULL,3,'Gris',NULL,NULL,1,1,5,9000000001);
/*!40000 ALTER TABLE `producto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proveedor`
--

DROP TABLE IF EXISTS `proveedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `proveedor` (
  `nit` varchar(20) NOT NULL,
  `nombre_prov` varchar(100) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `direccion` varchar(100) NOT NULL,
  `id_ciudad` int(11) NOT NULL,
  `nit_empresa` bigint(20) unsigned DEFAULT 9000000001,
  PRIMARY KEY (`nit`),
  UNIQUE KEY `uk_proveedor_telefono` (`nit_empresa`,`telefono`),
  UNIQUE KEY `uk_proveedor_correo` (`nit_empresa`,`correo`),
  KEY `id_ciudad` (`id_ciudad`),
  KEY `idx_proveedor_nit` (`nit_empresa`),
  CONSTRAINT `fk_proveedor_ciudad` FOREIGN KEY (`id_ciudad`) REFERENCES `ciudad` (`id_ciudad`),
  CONSTRAINT `fk_proveedor_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proveedor`
--

LOCK TABLES `proveedor` WRITE;
/*!40000 ALTER TABLE `proveedor` DISABLE KEYS */;
INSERT INTO `proveedor` VALUES
('100901234-9','CooperVision Andina','3089012345','andina@coopervision.com','Cl 72 # 10-07',1,9000000001),
('200890123-8','Transitions Óptica','3078901234','info@transitions.co','Cra 7 # 71-21',2,9000000001),
('300789012-7','Bausch Health Colombia','3067890123','co@bausch.com','Ak 9 # 115-06',1,9000000001),
('400678901-6','Alcon Colombia','3056789012','colombia@alcon.com','Cl 100 # 18-55',2,9000000001),
('500567890-5','VisiónPro SAS','3045678901','soporte@visionpro.com.co','Cra 50 # 25-60',1,9000000001),
('600456789-4','Infraóptica Colombia','3034567890','pedidos@infraoptica.co','Cl 30 # 5-10',2,9000000001),
('700345678-3','Lentes del Pacífico','3023456789','contacto@lentespacifico.com','Av 3N # 15-40',1,9000000001),
('800234567-2','Distribuidora Visual Ltda','3012345678','info@distvisual.com','Cl 80 # 20-30',2,9000000001),
('900012345-0','Ray-Ban Colombia Oficial','3090123456','co@ray-ban.com','Cra 11 # 82-71',2,9000000001),
('900123456-1','Óptica Nacional S.A.S','3001234567','ventas@opticanacional.com','Cra 45 # 10-20',1,9000000001);
/*!40000 ALTER TABLE `proveedor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rol`
--

DROP TABLE IF EXISTS `rol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rol` (
  `id_rol` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_rol` text NOT NULL,
  `descripcion` text NOT NULL,
  PRIMARY KEY (`id_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rol`
--

LOCK TABLES `rol` WRITE;
/*!40000 ALTER TABLE `rol` DISABLE KEYS */;
INSERT INTO `rol` VALUES
(1,'Administrador','Control total del sistema'),
(2,'Cliente','Usuario que agenda citas y compra productos'),
(3,'Vendedor','Gestiona ventas y carrito de compras'),
(4,'Recepcionista','Agenda y gestiona citas'),
(5,'Auditor','Solo lectura de reportes'),
(6,'Superadmin','administrador del software');
/*!40000 ALTER TABLE `rol` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seguimiento_envio`
--

DROP TABLE IF EXISTS `seguimiento_envio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seguimiento_envio` (
  `id_seguimiento_envio` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` datetime NOT NULL,
  `descripcion` text NOT NULL,
  `id_envio` int(11) NOT NULL,
  PRIMARY KEY (`id_seguimiento_envio`),
  KEY `id_envio` (`id_envio`),
  CONSTRAINT `fk_seguimiento_envio` FOREIGN KEY (`id_envio`) REFERENCES `envio` (`id_envio`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seguimiento_envio`
--

LOCK TABLES `seguimiento_envio` WRITE;
/*!40000 ALTER TABLE `seguimiento_envio` DISABLE KEYS */;
INSERT INTO `seguimiento_envio` VALUES
(1,'2026-05-07 09:00:00','Pedido despachado desde bodega',1),
(2,'2026-05-08 14:00:00','En tránsito hacia ciudad destino',1),
(3,'2026-05-10 10:00:00','Pedido entregado al destinatario',2),
(4,'2026-05-14 08:00:00','Pedido despachado',3),
(5,'2026-05-15 16:00:00','En camino',3);
/*!40000 ALTER TABLE `seguimiento_envio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES
('1lgvWnLcmmD7f82UI7UoWTRCsVQ6UCUPQppRQfBG',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoidDZZTWh4a2dBcko1UXhIaVRLSEZLajJDZVViNGJyUUU1MWJmVWdyWSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czoxMDoib3B0aWNhX25pdCI7czoxMToiOTAwMDAwMDAwLTEiO3M6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjUyOiJodHRwOi8vbG9jYWxob3N0L3Zpc2lsdXhfMy9wdWJsaWMvcGxhbmVzLzIvY29udHJhdGFyIjtzOjU6InJvdXRlIjtzOjE2OiJjb250cmF0YXIuY3JlYXRlIjt9czoyMjoicmVhY3RpdmFjaW9uX2RvY3VtZW50byI7aToxMTExMTExMTt9',1791909441),
('zmxAfc6e0yfhjJZRDNbNimeCAn7WPEvi29nd6ZPR',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZFNoc0dBRmJ2TGxpMlBnZVNOc1l1Nlp6MGdZVE9XSlk4S3NFQXVCNiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTA6Imh0dHA6Ly9sb2NhbGhvc3QvdmlzaWx1eF8zL3B1YmxpYy9sb2dpbi9zdXBlcmFkbWluIjtzOjU6InJvdXRlIjtzOjE2OiJzdXBlcmFkbWluLmxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1791906461);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `talla`
--

DROP TABLE IF EXISTS `talla`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `talla` (
  `id_talla` int(11) NOT NULL AUTO_INCREMENT,
  `talla` varchar(20) NOT NULL,
  `id_estado` int(11) NOT NULL DEFAULT 1,
  `nit_empresa` bigint(20) unsigned DEFAULT 9000000001,
  PRIMARY KEY (`id_talla`),
  KEY `fk_talla_estado` (`id_estado`),
  KEY `fk_talla_empresa` (`nit_empresa`),
  CONSTRAINT `fk_talla_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON UPDATE CASCADE,
  CONSTRAINT `fk_talla_estado` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_estado`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `talla`
--

LOCK TABLES `talla` WRITE;
/*!40000 ALTER TABLE `talla` DISABLE KEYS */;
INSERT INTO `talla` VALUES
(1,'XS',1,9000000001),
(2,'S',1,9000000001),
(3,'M',1,9000000001),
(4,'L',1,9000000001),
(5,'XL',1,9000000001),
(6,'49-18-140',1,9000000001),
(7,'52-20-145',1,9000000001),
(8,'54-18-140',1,9000000001),
(9,'56-17-145',1,9000000001),
(10,'58-14-150',1,9000000001);
/*!40000 ALTER TABLE `talla` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipo_cita`
--

DROP TABLE IF EXISTS `tipo_cita`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipo_cita` (
  `id_tipo_cita` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_tipo` text NOT NULL,
  `desc_tipo` text NOT NULL,
  `nit_empresa` bigint(20) unsigned DEFAULT 9000000001,
  PRIMARY KEY (`id_tipo_cita`),
  KEY `fk_tipo_cita_empresa` (`nit_empresa`),
  CONSTRAINT `fk_tipo_cita_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipo_cita`
--

LOCK TABLES `tipo_cita` WRITE;
/*!40000 ALTER TABLE `tipo_cita` DISABLE KEYS */;
INSERT INTO `tipo_cita` VALUES
(1,'Consulta General','Revisión optométrica de rutina',9000000001),
(2,'Control','Seguimiento a tratamiento previo',9000000001),
(3,'Urgencia','Atención no programada por molestia ocular',9000000001),
(4,'Adaptación de Lentes','Ajuste y adaptación de lentes de contacto',9000000001),
(5,'Examen Visual Completo','Evaluación completa de agudeza visual y refracción',9000000001);
/*!40000 ALTER TABLE `tipo_cita` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipo_documento`
--

DROP TABLE IF EXISTS `tipo_documento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipo_documento` (
  `id_tipo_docu` int(11) NOT NULL AUTO_INCREMENT,
  `nom_ti_docu` text NOT NULL,
  PRIMARY KEY (`id_tipo_docu`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipo_documento`
--

LOCK TABLES `tipo_documento` WRITE;
/*!40000 ALTER TABLE `tipo_documento` DISABLE KEYS */;
INSERT INTO `tipo_documento` VALUES
(1,'Cédula de Ciudadanía'),
(2,'Tarjeta de Identidad'),
(3,'Cédula de Extranjería'),
(4,'Pasaporte'),
(5,'NIT');
/*!40000 ALTER TABLE `tipo_documento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipo_producto`
--

DROP TABLE IF EXISTS `tipo_producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipo_producto` (
  `id_tipo_pro` int(11) NOT NULL AUTO_INCREMENT,
  `nomb_tipo` text NOT NULL,
  `descripcion` text NOT NULL,
  `id_estado` int(11) NOT NULL DEFAULT 1,
  `nit_empresa` bigint(20) unsigned DEFAULT 9000000001,
  PRIMARY KEY (`id_tipo_pro`),
  KEY `fk_tipo_producto_estado` (`id_estado`),
  KEY `fk_tipo_producto_empresa` (`nit_empresa`),
  CONSTRAINT `fk_tipo_producto_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON UPDATE CASCADE,
  CONSTRAINT `fk_tipo_producto_estado` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_estado`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipo_producto`
--

LOCK TABLES `tipo_producto` WRITE;
/*!40000 ALTER TABLE `tipo_producto` DISABLE KEYS */;
INSERT INTO `tipo_producto` VALUES
(1,'Monturas','Armazones para lentes oftálmicos y solares',1,9000000001),
(2,'Lentes de Contacto','Lentes blandas, rígidas y de diseño especial',1,9000000001),
(3,'Soluciones','Líquidos de mantenimiento para lentes de contacto',1,9000000001),
(4,'Accesorios','Estuches, paños, cadenas y demás accesorios ópticos',1,9000000001),
(5,'Lentes Oftálmicos','Cristales graduados para monturas',1,9000000001);
/*!40000 ALTER TABLE `tipo_producto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipo_terapia`
--

DROP TABLE IF EXISTS `tipo_terapia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipo_terapia` (
  `id_tipo_terapia` int(11) NOT NULL AUTO_INCREMENT,
  `nomtipo_terp` text NOT NULL,
  `descripcion` text NOT NULL,
  `nit_empresa` bigint(20) unsigned DEFAULT 9000000001,
  PRIMARY KEY (`id_tipo_terapia`),
  KEY `fk_tipo_terapia_empresa` (`nit_empresa`),
  CONSTRAINT `fk_tipo_terapia_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipo_terapia`
--

LOCK TABLES `tipo_terapia` WRITE;
/*!40000 ALTER TABLE `tipo_terapia` DISABLE KEYS */;
INSERT INTO `tipo_terapia` VALUES
(1,'Terapia Visual','Ejercicios para mejorar coordinación ojo-mano',9000000001),
(2,'Ortoqueratología','Remodelación corneal nocturna con lentes especiales',9000000001),
(3,'Visión Baja','Rehabilitación para pacientes con baja visión',9000000001),
(4,'Binocularidad','Corrección de problemas de visión binocular',9000000001),
(5,'Oclusión','Terapia para corrección de ambliopía',9000000001);
/*!40000 ALTER TABLE `tipo_terapia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transportadora`
--

DROP TABLE IF EXISTS `transportadora`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transportadora` (
  `nit` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `cobertura` text DEFAULT NULL,
  `id_estado` int(11) NOT NULL,
  `nit_empresa` bigint(20) unsigned DEFAULT 9000000001,
  PRIMARY KEY (`nit`),
  UNIQUE KEY `uk_transportadora_telefono` (`nit_empresa`,`telefono`),
  UNIQUE KEY `uk_transportadora_correo` (`nit_empresa`,`correo`),
  KEY `id_estado` (`id_estado`),
  CONSTRAINT `fk_transportadora_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transportadora`
--

LOCK TABLES `transportadora` WRITE;
/*!40000 ALTER TABLE `transportadora` DISABLE KEYS */;
INSERT INTO `transportadora` VALUES
(1001,'Servientrega','6011234567','contacto@servientrega.com','Nacional',1,9000000001),
(1002,'Coordinadora','6022345678','info@coordinadora.com','Nacional',1,9000000001),
(1003,'Interrapidísimo','6033456789','envios@inter.com.co','Nacional',1,9000000001),
(1004,'Deprisa','6044567890','soporte@deprisa.com','Nacional',1,9000000001),
(1005,'TCC','6055678901','clientes@tcc.com.co','Nacional',1,9000000001),
(1006,'Envia','6066789012','info@envia.com.co','Nacional',2,9000000001),
(1007,'DHL Colombia','6077890123','co.cs@dhl.com','Internacional',1,9000000001),
(1008,'FedEx Colombia','6088901234','colombia@fedex.com','Internacional',1,9000000001),
(1009,'Mensajería YA','6099012345','envios@mensajeriaya.co','Medellín',1,9000000001),
(1010,'Rappi Express','6010123456','logistics@rappi.com','Ciudades principales',1,9000000001);
/*!40000 ALTER TABLE `transportadora` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario` (
  `documento` int(11) NOT NULL,
  `nombres` text NOT NULL,
  `apellido` text NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `ultimo_acceso` datetime DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `id_rol` int(11) NOT NULL,
  `id_tipo_docu` int(11) NOT NULL,
  `id_estado` int(11) NOT NULL,
  `id_ciudad` int(11) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `nit_empresa` bigint(20) unsigned DEFAULT 9000000001,
  PRIMARY KEY (`documento`),
  UNIQUE KEY `telefono` (`telefono`),
  UNIQUE KEY `correo` (`email`),
  KEY `id_rol` (`id_rol`),
  KEY `id_tipo_docu` (`id_tipo_docu`),
  KEY `id_estado` (`id_estado`),
  KEY `fk_usuario_ciudad` (`id_ciudad`),
  KEY `fk_usuario_empresa` (`nit_empresa`),
  CONSTRAINT `fk_usuario_ciudad` FOREIGN KEY (`id_ciudad`) REFERENCES `ciudad` (`id_ciudad`),
  CONSTRAINT `fk_usuario_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON UPDATE CASCADE,
  CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`),
  CONSTRAINT `fk_usuario_tipo_docu` FOREIGN KEY (`id_tipo_docu`) REFERENCES `tipo_documento` (`id_tipo_docu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES
(11111111,'Luis Felipe','Patarroyo Velandia','3101234567','pipepatarroyo2006@gmail.com','$2y$12$0eknwNL/crGsR9a6KN9UK.KXvegUacSrcNV2mpvaBhsaKSQjSeBDK','2026-01-10 08:00:00',NULL,NULL,2,1,2,NULL,NULL,9000000001),
(22222222,'Juanita','Ramirez Homez','3112345678','hramirezjuanita@gmail.com','$2y$12$JGGr88hjzQ3TXNsg2UUeJ.G48dA3yfQA/giU.at8U1eW9HiNeWKLK','2026-01-15 09:00:00',NULL,NULL,2,1,1,NULL,NULL,9000000001),
(33333333,'Laura Sofía','García Pérez','3123456789','angelamarcelamartinezruiz@gmail.com','$2y$12$JGGr88hjzQ3TXNsg2UUeJ.G48dA3yfQA/giU.at8U1eW9HiNeWKLK','2026-02-01 10:00:00',NULL,NULL,3,1,1,NULL,NULL,9000000001),
(44444444,'Andrés Felipe','Rodríguez Silva','3134567890','andres.rodriguez@gmail.com','$2y$12$JGGr88hjzQ3TXNsg2UUeJ.G48dA3yfQA/giU.at8U1eW9HiNeWKLK','2026-02-10 11:00:00',NULL,NULL,4,1,1,NULL,NULL,9000000001),
(55555555,'Valentina','Gómez Castro','3145678901','valentina.gomez@gmail.com','$2y$12$JGGr88hjzQ3TXNsg2UUeJ.G48dA3yfQA/giU.at8U1eW9HiNeWKLK','2026-03-01 08:00:00',NULL,NULL,2,1,1,NULL,NULL,9000000001),
(999999999,'Cesar','Esquivel','3000000000','esquivel7809@gmail.com','$2y$12$GJoB23hFrqISQpvCffH.b.RoMEZ0bizyORLU706H5ig4xGDyyfptm','2026-10-05 11:30:09',NULL,NULL,1,1,1,NULL,NULL,9000000001),
(1000000002,'Mega','Ruiz','3009998866','megaruiz0920@gmail.com','$2y$12$C6GigqX8VUnaj7rWYqOJCO1/CS6OoY9ZD/RUVanaffZIndGSkaWQi','2026-10-05 11:25:44',NULL,NULL,6,1,1,NULL,NULL,NULL),
(1000000003,'Cesar','Esquivel','3000000003','adsosenasena@gmail.com','$2y$12$JGGr88hjzQ3TXNsg2UUeJ.G48dA3yfQA/giU.at8U1eW9HiNeWKLK','2026-10-07 10:01:36',NULL,NULL,6,1,1,NULL,NULL,NULL),
(1234567890,'administrador','','','admin@visioptica.com','$2y$12$n3f71eUFhzMTcWrNxUEkjOmnISt1tBL2T9qmbOeHhnYwdgS/nyd2O','2026-01-01 00:00:00',NULL,NULL,1,1,1,NULL,NULL,9000000001);
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios_no_registrados`
--

DROP TABLE IF EXISTS `usuarios_no_registrados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios_no_registrados` (
  `id_usuario_nr` int(11) NOT NULL AUTO_INCREMENT,
  `documento` varchar(20) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `motivo_cita` text DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `nit_empresa` bigint(20) unsigned DEFAULT 9000000001,
  PRIMARY KEY (`id_usuario_nr`),
  KEY `fk_usuarios_no_registrados_empresa` (`nit_empresa`),
  CONSTRAINT `fk_usuarios_no_registrados_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios_no_registrados`
--

LOCK TABLES `usuarios_no_registrados` WRITE;
/*!40000 ALTER TABLE `usuarios_no_registrados` DISABLE KEYS */;
INSERT INTO `usuarios_no_registrados` VALUES
(1,'66666661','nreg1@gmail.com','Pedro Suárez','3156789012','Revisión general','2026-06-27 21:44:16',9000000001),
(2,'66666662','nreg2@gmail.com','María Castillo','3167890123','Dolor ocular','2026-06-27 21:44:16',9000000001),
(3,'66666663','nreg3@gmail.com','Luis Mendoza','3178901234','Cambio de fórmula','2026-06-27 21:44:16',9000000001),
(4,'66666664','nreg4@gmail.com','Sara Jiménez','3189012345','Primera consulta','2026-06-27 21:44:16',9000000001),
(5,'66666665','nreg5@gmail.com','Carlos Ríos','3190123456','Control postoperatorio','2026-06-27 21:44:16',9000000001),
(6,'11223344','maria@ramirez.com','maria ramirez','3156178909','Dolor ocular','2026-09-28 12:35:56',9000000001);
/*!40000 ALTER TABLE `usuarios_no_registrados` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `v_estado_licencias`
--

DROP TABLE IF EXISTS `v_estado_licencias`;
/*!50001 DROP VIEW IF EXISTS `v_estado_licencias`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `v_estado_licencias` AS SELECT
 1 AS `nit`,
  1 AS `empresa`,
  1 AS `email`,
  1 AS `estado_empresa`,
  1 AS `prueba_usada`,
  1 AS `id_licencia`,
  1 AS `plan`,
  1 AS `es_prueba`,
  1 AS `fecha_inicio`,
  1 AS `fecha_fin`,
  1 AS `dias_restantes`,
  1 AS `estado_licencia`,
  1 AS `usuarios_registrados`,
  1 AS `solicitudes_pendientes` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `venta`
--

DROP TABLE IF EXISTS `venta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `venta` (
  `id_venta` int(11) NOT NULL AUTO_INCREMENT,
  `imagen_formula` varchar(255) DEFAULT NULL,
  `fecha_venta` date NOT NULL,
  `impuestos` decimal(10,2) DEFAULT 0.00,
  `descuento` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_estado` int(11) NOT NULL,
  `id_metodo_compra` int(11) DEFAULT NULL,
  `nit_empresa` bigint(20) unsigned DEFAULT 9000000001,
  PRIMARY KEY (`id_venta`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_estado` (`id_estado`),
  KEY `id_metodo_compra` (`id_metodo_compra`),
  KEY `fk_venta_empresa` (`nit_empresa`),
  CONSTRAINT `fk_venta_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON UPDATE CASCADE,
  CONSTRAINT `venta_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`documento`),
  CONSTRAINT `venta_ibfk_2` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_estado`),
  CONSTRAINT `venta_ibfk_3` FOREIGN KEY (`id_metodo_compra`) REFERENCES `metodo_compra` (`id_metodo`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venta`
--

LOCK TABLES `venta` WRITE;
/*!40000 ALTER TABLE `venta` DISABLE KEYS */;
INSERT INTO `venta` VALUES
(1,NULL,'2026-05-05',28500.00,0.00,178500.00,NULL,11111111,5,2,9000000001),
(2,NULL,'2026-05-08',11400.00,5000.00,71400.00,NULL,22222222,5,3,9000000001),
(3,NULL,'2026-05-12',35200.00,0.00,220200.00,NULL,33333333,5,1,9000000001),
(4,NULL,'2026-05-18',20800.00,0.00,130800.00,NULL,44444444,5,4,9000000001),
(5,NULL,'2026-05-25',16400.00,0.00,102400.00,NULL,55555555,5,5,9000000001);
/*!40000 ALTER TABLE `venta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `venta_medicamento`
--

DROP TABLE IF EXISTS `venta_medicamento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `venta_medicamento` (
  `id_venta_med` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `fecha` datetime NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `nit_empresa` bigint(20) unsigned DEFAULT 9000000001,
  PRIMARY KEY (`id_venta_med`),
  KEY `id_usuario` (`id_usuario`),
  KEY `fk_venta_medicamento_empresa` (`nit_empresa`),
  CONSTRAINT `fk_venta_medicamento_empresa` FOREIGN KEY (`nit_empresa`) REFERENCES `empresa` (`nit`) ON UPDATE CASCADE,
  CONSTRAINT `venta_med_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`documento`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venta_medicamento`
--

LOCK TABLES `venta_medicamento` WRITE;
/*!40000 ALTER TABLE `venta_medicamento` DISABLE KEYS */;
INSERT INTO `venta_medicamento` VALUES
(1,11111111,'2026-05-06 10:00:00',25000.00,9000000001),
(2,22222222,'2026-05-09 11:00:00',30000.00,9000000001),
(3,33333333,'2026-05-13 09:00:00',32000.00,9000000001),
(4,44444444,'2026-05-19 14:00:00',45000.00,9000000001),
(5,55555555,'2026-05-26 10:00:00',38000.00,9000000001);
/*!40000 ALTER TABLE `venta_medicamento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'prueba_bd'
--

--
-- Final view structure for view `v_estado_licencias`
--

/*!50001 DROP VIEW IF EXISTS `v_estado_licencias`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_estado_licencias` AS select `e`.`nit` AS `nit`,`e`.`nombre` AS `empresa`,`e`.`email` AS `email`,`e`.`estado` AS `estado_empresa`,`e`.`prueba_usada` AS `prueba_usada`,`l`.`id_licencia` AS `id_licencia`,`p`.`nombre` AS `plan`,`p`.`es_prueba` AS `es_prueba`,`l`.`fecha_inicio` AS `fecha_inicio`,`l`.`fecha_fin` AS `fecha_fin`,to_days(`l`.`fecha_fin`) - to_days(curdate()) AS `dias_restantes`,case when `e`.`estado` = 'pendiente' then 'pendiente_pago' when `e`.`estado` = 'suspendida' then 'suspendida' when `l`.`id_licencia` is null then 'sin_licencia' when `l`.`fecha_fin` < curdate() then 'vencida' when to_days(`l`.`fecha_fin`) - to_days(curdate()) <= 30 then 'por_vencer' else 'vigente' end AS `estado_licencia`,(select count(0) from `usuario` `u` where `u`.`nit_empresa` = `e`.`nit`) AS `usuarios_registrados`,(select count(0) from `licencia` `lp` where `lp`.`nit_empresa` = `e`.`nit` and `lp`.`estado` = 'pendiente') AS `solicitudes_pendientes` from ((`empresa` `e` left join `licencia` `l` on(`l`.`id_licencia` = (select `l2`.`id_licencia` from `licencia` `l2` where `l2`.`nit_empresa` = `e`.`nit` and `l2`.`estado` in ('activa','vencida') order by `l2`.`fecha_fin` desc limit 1))) left join `plan` `p` on(`p`.`id_plan` = `l`.`id_plan`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed
