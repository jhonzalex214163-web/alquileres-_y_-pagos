-- ============================================================
-- Bicijardin - ESQUEMA COMPLETO (generado por mysqldump 8.4 desde la BD real, migraciones 004-007 aplicadas). DDL sin datos.
-- Dump autoritativo generado desde la BD real (MySQL 8.4),
-- con las migraciones 004-007 ya aplicadas. DDL sin datos.
-- ============================================================
-- WARNING: este script ejecuta CREATE DATABASE + USE sobre 'bicicletas_compartidas'.
-- NO lo importes sobre una BD scratch sin revisar el nombre, o destruiras la BD real.
USE `bicicletas_compartidas`;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `alquileres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `alquileres` (
  `id_alquiler` int unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` int unsigned NOT NULL,
  `id_bicicleta` int unsigned NOT NULL,
  `estacion_origen` int unsigned NOT NULL,
  `estacion_destino` int unsigned DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'finalizado',
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime DEFAULT NULL,
  `minutos_totales` int unsigned DEFAULT NULL,
  `costo_total` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id_alquiler`),
  KEY `FK_alquileres_usuarios` (`id_usuario`),
  KEY `FK_alquileres_bicicletas` (`id_bicicleta`),
  KEY `FK_alquileres_origen` (`estacion_origen`),
  KEY `FK_alquileres_destino` (`estacion_destino`),
  CONSTRAINT `FK_alquileres_bicicletas` FOREIGN KEY (`id_bicicleta`) REFERENCES `bicicletas` (`id_bicicleta`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `FK_alquileres_destino` FOREIGN KEY (`estacion_destino`) REFERENCES `estaciones` (`id_estacion`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_alquileres_origen` FOREIGN KEY (`estacion_origen`) REFERENCES `estaciones` (`id_estacion`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `FK_alquileres_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `tg_actualizar_frecuencia_usuario` AFTER UPDATE ON `alquileres` FOR EACH ROW BEGIN

    IF OLD.fecha_fin IS NULL AND NEW.fecha_fin IS NOT NULL THEN

        UPDATE usuarios u

        SET u.dias_alquiler_mes = (

            SELECT COUNT(DISTINCT DATE(fecha_inicio)) 

            FROM alquileres 

            WHERE id_usuario = NEW.id_usuario 

              AND MONTH(fecha_inicio) = MONTH(NEW.fecha_inicio)

              AND YEAR(fecha_inicio) = YEAR(NEW.fecha_inicio)

        )

        WHERE u.id_usuario = NEW.id_usuario;

    END IF;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `bicicletas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bicicletas` (
  `id_bicicleta` int unsigned NOT NULL AUTO_INCREMENT,
  `modelo` varchar(50) NOT NULL,
  `num_serie` varchar(50) NOT NULL,
  `nivel_bateria` int DEFAULT NULL,
  `estado` varchar(30) DEFAULT 'Disponible',
  `kilometraje` decimal(10,2) DEFAULT '0.00',
  `id_estacion` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id_bicicleta`),
  UNIQUE KEY `num_serie` (`num_serie`),
  KEY `FK_bicicletas_estaciones` (`id_estacion`),
  CONSTRAINT `FK_bicicletas_estaciones` FOREIGN KEY (`id_estacion`) REFERENCES `estaciones` (`id_estacion`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `bicicletas_chk_1` CHECK (((`nivel_bateria` >= 0) and (`nivel_bateria` <= 100))),
  CONSTRAINT `bicicletas_chk_2` CHECK ((`kilometraje` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cliente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cliente` (
  `id_cliente` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `direccion` varchar(150) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `ciudad` varchar(80) DEFAULT NULL,
  PRIMARY KEY (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `estado` enum('Activo','Inactivo') DEFAULT 'Activo',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clientes_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int DEFAULT NULL,
  `accion` varchar(20) DEFAULT NULL,
  `descripcion` text,
  `fecha_cambio` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `estaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `estaciones` (
  `id_estacion` int unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `coordenadas` point DEFAULT NULL,
  `capacidad` int NOT NULL,
  `energia_disp` decimal(10,2) DEFAULT NULL,
  `estado` enum('operativa','inoperativa') NOT NULL DEFAULT 'operativa',
  PRIMARY KEY (`id_estacion`),
  UNIQUE KEY `codigo` (`codigo`),
  CONSTRAINT `estaciones_chk_1` CHECK ((`capacidad` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `factura`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `factura` (
  `cod_fac` int NOT NULL,
  `fecha` date NOT NULL,
  `id_cliente` int NOT NULL,
  PRIMARY KEY (`cod_fac`),
  KEY `fk_factura_cliente` (`id_cliente`),
  CONSTRAINT `fk_factura_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventario_costos_arreglos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventario_costos_arreglos` (
  `id_arreglo` int unsigned NOT NULL AUTO_INCREMENT,
  `id_bicicleta` int unsigned NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `costo` decimal(10,2) NOT NULL,
  `fecha_arreglo` date NOT NULL,
  PRIMARY KEY (`id_arreglo`),
  KEY `FK_arreglos_bicicletas` (`id_bicicleta`),
  CONSTRAINT `FK_arreglos_bicicletas` FOREIGN KEY (`id_bicicleta`) REFERENCES `bicicletas` (`id_bicicleta`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `inventario_costos_arreglos_chk_1` CHECK ((`costo` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `logs_auditoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs_auditoria` (
  `id_log` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` int unsigned DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `tabla_afectada` varchar(100) DEFAULT NULL,
  `descripcion` text,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `FK_logs_usuarios` (`id_usuario`),
  CONSTRAINT `FK_logs_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mantenimientos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mantenimientos` (
  `id_mantenimiento` int unsigned NOT NULL AUTO_INCREMENT,
  `id_bicicleta` int unsigned NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `fecha_mantenimiento` date NOT NULL,
  PRIMARY KEY (`id_mantenimiento`),
  KEY `FK_mantenimientos_bicicletas` (`id_bicicleta`),
  CONSTRAINT `FK_mantenimientos_bicicletas` FOREIGN KEY (`id_bicicleta`) REFERENCES `bicicletas` (`id_bicicleta`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `metodos_pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `metodos_pago` (
  `id_metodo` int unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` int unsigned NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `descripcion` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id_metodo`),
  KEY `FK_metodos_pago_usuarios` (`id_usuario`),
  CONSTRAINT `FK_metodos_pago_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `producto` (
  `codigo` int NOT NULL,
  `categoria` varchar(80) NOT NULL,
  `descripcion` varchar(150) NOT NULL,
  `valor` decimal(12,2) NOT NULL,
  `cantidad` int NOT NULL,
  PRIMARY KEY (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `producto_factura`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `producto_factura` (
  `cod_pro` int NOT NULL,
  `cod_fac` int NOT NULL,
  `cantidad` int NOT NULL,
  PRIMARY KEY (`cod_pro`,`cod_fac`),
  KEY `fk_pf_factura` (`cod_fac`),
  CONSTRAINT `fk_pf_factura` FOREIGN KEY (`cod_fac`) REFERENCES `factura` (`cod_fac`),
  CONSTRAINT `fk_pf_producto` FOREIGN KEY (`cod_pro`) REFERENCES `producto` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `registro_energia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `registro_energia` (
  `id_registro` int unsigned NOT NULL AUTO_INCREMENT,
  `id_estacion` int unsigned NOT NULL,
  `consumo_kwh` decimal(10,2) NOT NULL,
  `fecha_registro` datetime NOT NULL,
  PRIMARY KEY (`id_registro`),
  KEY `FK_energia_estaciones` (`id_estacion`),
  CONSTRAINT `FK_energia_estaciones` FOREIGN KEY (`id_estacion`) REFERENCES `estaciones` (`id_estacion`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `registro_energia_chk_1` CHECK ((`consumo_kwh` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rutas_turisticas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rutas_turisticas` (
  `id_ruta` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `dificultad` enum('facil','media','alta') NOT NULL DEFAULT 'media',
  `distancia_km` decimal(5,2) DEFAULT NULL,
  `duracion_est` varchar(30) DEFAULT NULL,
  `lat` decimal(10,7) NOT NULL,
  `lng` decimal(10,7) NOT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_ruta`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id_rol` int unsigned NOT NULL AUTO_INCREMENT,
  `codigo_rol` varchar(50) NOT NULL,
  `nombre_rol` varchar(100) NOT NULL,
  `permisos` json DEFAULT NULL,
  `nivel_acceso` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `UQ_roles_codigo` (`codigo_rol`),
  UNIQUE KEY `UQ_roles_nombre` (`nombre_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tarifas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tarifas` (
  `id_tarifa` int unsigned NOT NULL AUTO_INCREMENT,
  `tipo_tarifa` varchar(50) NOT NULL,
  `precio_por_minuto` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_tarifa`),
  CONSTRAINT `tarifas_chk_1` CHECK ((`precio_por_minuto` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tokens_pasarela`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tokens_pasarela` (
  `id_token` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `proveedor` varchar(50) NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_token`),
  KEY `fk_token_usuario` (`id_usuario`),
  CONSTRAINT `fk_token_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `cliente` (`id_cliente`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `transacciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transacciones` (
  `id_transaccion` int unsigned NOT NULL AUTO_INCREMENT,
  `id_alquiler` int unsigned NOT NULL,
  `id_metodo` int unsigned DEFAULT NULL,
  `referencia_externa` varchar(100) DEFAULT NULL,
  `monto` decimal(10,2) DEFAULT NULL,
  `metodo` varchar(20) NOT NULL DEFAULT 'TARJETA',
  `estado` varchar(20) NOT NULL DEFAULT 'EXITOSO',
  `comprobante_url` varchar(255) DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL,
  `fecha_pago` datetime NOT NULL,
  PRIMARY KEY (`id_transaccion`),
  UNIQUE KEY `UQ_transacciones_referencia` (`referencia_externa`),
  KEY `FK_transacciones_alquileres` (`id_alquiler`),
  KEY `FK_transacciones_metodos` (`id_metodo`),
  CONSTRAINT `FK_transacciones_alquileres` FOREIGN KEY (`id_alquiler`) REFERENCES `alquileres` (`id_alquiler`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `FK_transacciones_metodos` FOREIGN KEY (`id_metodo`) REFERENCES `metodos_pago` (`id_metodo`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `transacciones_chk_1` CHECK ((`valor` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id_usuario` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `id_rol` int unsigned NOT NULL,
  `fecha_registro` date DEFAULT (curdate()),
  `password` varchar(255) NOT NULL DEFAULT '',
  `dias_alquiler_mes` int unsigned DEFAULT '0',
  `descuento_fidelidad` decimal(5,2) DEFAULT '0.00',
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `email` (`email`),
  KEY `FK_usuarios_roles` (`id_rol`),
  CONSTRAINT `FK_usuarios_roles` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vw_clientes_frecuentes`;
/*!50001 DROP VIEW IF EXISTS `vw_clientes_frecuentes`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_clientes_frecuentes` AS SELECT 
 1 AS `id_usuario`,
 1 AS `cliente`,
 1 AS `email`,
 1 AS `dias_alquiler_mes`,
 1 AS `descuento_fidelidad`,
 1 AS `estado_fidelidad`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `vw_conciliacion_financiera`;
/*!50001 DROP VIEW IF EXISTS `vw_conciliacion_financiera`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_conciliacion_financiera` AS SELECT 
 1 AS `id_alquiler`,
 1 AS `id_usuario`,
 1 AS `cliente`,
 1 AS `monto_alquiler`,
 1 AS `monto_pagado`,
 1 AS `diferencia`,
 1 AS `estado_conciliacion`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `vw_transacciones_huerfanas`;
/*!50001 DROP VIEW IF EXISTS `vw_transacciones_huerfanas`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_transacciones_huerfanas` AS SELECT 
 1 AS `id_transaccion`,
 1 AS `id_alquiler`,
 1 AS `valor`,
 1 AS `fecha_pago`,
 1 AS `motivo`*/;
SET character_set_client = @saved_cs_client;
/*!50003 DROP PROCEDURE IF EXISTS `pc_lista_productos` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `pc_lista_productos`()
BEGIN

    SELECT

        codigo,

        categoria,

        descripcion,

        valor,

        cantidad

    FROM producto;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_conciliar_y_auditar` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_conciliar_y_auditar`()
BEGIN

    -- 1. Auditar en logs_auditoria transacciones con IDs de alquiler incompletos u huâ”œÂ®rfanos

    INSERT INTO logs_auditoria (id_usuario, accion, tabla_afectada, descripcion)

    SELECT 

        NULL, 

        'ALERTA_FINANCIERA', 

        'transacciones', 

        CONCAT('Transacciâ”œâ”‚n huâ”œÂ®rfana detectada. ID Transacciâ”œâ”‚n: ', id_transaccion, ' con ID Alquiler inexistente: ', id_alquiler)

    FROM vw_transacciones_huerfanas;



    -- 2. Auditar descuadres en la conciliaciâ”œâ”‚n

    INSERT INTO logs_auditoria (id_usuario, accion, tabla_afectada, descripcion)

    SELECT 

        id_usuario,

        'DESCUADRE_PAGO',

        'alquileres',

        CONCAT('Alquiler ID ', id_alquiler, ' presenta diferencia de costo de ', diferencia)

    FROM vw_conciliacion_financiera

    WHERE estado_conciliacion != 'Conciliado';

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_recuperar_transacciones_huerfanas` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_recuperar_transacciones_huerfanas`()
BEGIN

    -- Registrar en auditorâ”œÂ¡a el inicio de la recuperaciâ”œâ”‚n

    INSERT INTO logs_auditoria (accion, tabla_afectada, descripcion)

    VALUES ('PROCESO_RECUPERACION', 'transacciones', 'Iniciando escaneo de transacciones sin alquiler asociado.');



    -- Generar log individual por cada transacciâ”œâ”‚n corrupta/huâ”œÂ®rfana detectada

    INSERT INTO logs_auditoria (id_usuario, accion, tabla_afectada, descripcion)

    SELECT 

        NULL,

        'ID_HUERFANO_DETECTADO',

        'transacciones',

        CONCAT('Transacciâ”œâ”‚n id_transaccion: ', t.id_transaccion, ' referencia un id_alquiler inexistente: ', t.id_alquiler)

    FROM transacciones t

    LEFT JOIN alquileres a ON t.id_alquiler = a.id_alquiler

    WHERE a.id_alquiler IS NULL;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50001 DROP VIEW IF EXISTS `vw_clientes_frecuentes`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_clientes_frecuentes` AS select `u`.`id_usuario` AS `id_usuario`,concat(`u`.`nombre`,' ',`u`.`apellido`) AS `cliente`,`u`.`email` AS `email`,`u`.`dias_alquiler_mes` AS `dias_alquiler_mes`,`u`.`descuento_fidelidad` AS `descuento_fidelidad`,(case when (`u`.`dias_alquiler_mes` > 20) then 'Elegible para Descuento (10%)' else concat('Faltan ',(21 - `u`.`dias_alquiler_mes`),' dâ”œÂ¡as') end) AS `estado_fidelidad` from `usuarios` `u` order by `u`.`dias_alquiler_mes` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `vw_conciliacion_financiera`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_conciliacion_financiera` AS select `a`.`id_alquiler` AS `id_alquiler`,`a`.`id_usuario` AS `id_usuario`,concat(`u`.`nombre`,' ',`u`.`apellido`) AS `cliente`,`a`.`costo_total` AS `monto_alquiler`,ifnull(sum(case when `t`.`estado` in ('EXITOSO','APROBADO') then `t`.`valor` else 0 end),0.00) AS `monto_pagado`,(`a`.`costo_total` - ifnull(sum(case when `t`.`estado` in ('EXITOSO','APROBADO') then `t`.`valor` else 0 end),0.00)) AS `diferencia`,(case when (ifnull(sum(case when `t`.`estado` in ('EXITOSO','APROBADO') then `t`.`valor` else 0 end),0.00) = `a`.`costo_total`) then 'Conciliado' when (ifnull(sum(case when `t`.`estado` in ('EXITOSO','APROBADO') then `t`.`valor` else 0 end),0.00) < `a`.`costo_total`) then 'Pago Parcial / Pendiente' else 'Sobrepago / Error' end) AS `estado_conciliacion` from ((`alquileres` `a` join `usuarios` `u` on((`a`.`id_usuario` = `u`.`id_usuario`))) left join `transacciones` `t` on((`a`.`id_alquiler` = `t`.`id_alquiler`))) where (`a`.`fecha_fin` is not null) group by `a`.`id_alquiler`,`a`.`id_usuario`,`u`.`nombre`,`u`.`apellido`,`a`.`costo_total` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `vw_transacciones_huerfanas`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_transacciones_huerfanas` AS select `t`.`id_transaccion` AS `id_transaccion`,`t`.`id_alquiler` AS `id_alquiler`,`t`.`valor` AS `valor`,`t`.`fecha_pago` AS `fecha_pago`,'Transacciâ”œâ”‚n sin registro de alquiler' AS `motivo` from (`transacciones` `t` left join `alquileres` `a` on((`t`.`id_alquiler` = `a`.`id_alquiler`))) where (`a`.`id_alquiler` is null) */;
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

