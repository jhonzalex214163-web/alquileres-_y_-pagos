-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         8.4.3 - MySQL Community Server - GPL
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Volcando estructura de base de datos para bicicletas_compartidas
DROP DATABASE IF EXISTS `bicicletas_compartidas`;
CREATE DATABASE IF NOT EXISTS `bicicletas_compartidas` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `bicicletas_compartidas`;

-- Volcando estructura para tabla bicicletas_compartidas.alquileres
DROP TABLE IF EXISTS `alquileres`;
CREATE TABLE IF NOT EXISTS `alquileres` (
  `id_alquiler` int unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` int unsigned NOT NULL,
  `id_bicicleta` int unsigned NOT NULL,
  `estacion_origen` int unsigned NOT NULL,
  `estacion_destino` int unsigned DEFAULT NULL,
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime DEFAULT NULL,
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

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla bicicletas_compartidas.bicicletas
DROP TABLE IF EXISTS `bicicletas`;
CREATE TABLE IF NOT EXISTS `bicicletas` (
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

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla bicicletas_compartidas.cliente
DROP TABLE IF EXISTS `cliente`;
CREATE TABLE IF NOT EXISTS `cliente` (
  `id_cliente` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `direccion` varchar(150) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `ciudad` varchar(80) DEFAULT NULL,
  PRIMARY KEY (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla bicicletas_compartidas.estaciones
DROP TABLE IF EXISTS `estaciones`;
CREATE TABLE IF NOT EXISTS `estaciones` (
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

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla bicicletas_compartidas.factura
DROP TABLE IF EXISTS `factura`;
CREATE TABLE IF NOT EXISTS `factura` (
  `cod_fac` int NOT NULL,
  `fecha` date NOT NULL,
  `id_cliente` int NOT NULL,
  PRIMARY KEY (`cod_fac`),
  KEY `fk_factura_cliente` (`id_cliente`),
  CONSTRAINT `fk_factura_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla bicicletas_compartidas.inventario_costos_arreglos
DROP TABLE IF EXISTS `inventario_costos_arreglos`;
CREATE TABLE IF NOT EXISTS `inventario_costos_arreglos` (
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

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla bicicletas_compartidas.logs_auditoria
DROP TABLE IF EXISTS `logs_auditoria`;
CREATE TABLE IF NOT EXISTS `logs_auditoria` (
  `id_log` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` int unsigned DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `tabla_afectada` varchar(100) DEFAULT NULL,
  `descripcion` text,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `FK_logs_usuarios` (`id_usuario`),
  CONSTRAINT `FK_logs_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla bicicletas_compartidas.mantenimientos
DROP TABLE IF EXISTS `mantenimientos`;
CREATE TABLE IF NOT EXISTS `mantenimientos` (
  `id_mantenimiento` int unsigned NOT NULL AUTO_INCREMENT,
  `id_bicicleta` int unsigned NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `fecha_mantenimiento` date NOT NULL,
  PRIMARY KEY (`id_mantenimiento`),
  KEY `FK_mantenimientos_bicicletas` (`id_bicicleta`),
  CONSTRAINT `FK_mantenimientos_bicicletas` FOREIGN KEY (`id_bicicleta`) REFERENCES `bicicletas` (`id_bicicleta`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla bicicletas_compartidas.metodos_pago
DROP TABLE IF EXISTS `metodos_pago`;
CREATE TABLE IF NOT EXISTS `metodos_pago` (
  `id_metodo` int unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` int unsigned NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `descripcion` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id_metodo`),
  KEY `FK_metodos_pago_usuarios` (`id_usuario`),
  CONSTRAINT `FK_metodos_pago_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para procedimiento bicicletas_compartidas.pc_lista_productos
DROP PROCEDURE IF EXISTS `pc_lista_productos`;
DELIMITER //
CREATE PROCEDURE `pc_lista_productos`()
BEGIN
    SELECT
        codigo,
        categoria,
        descripcion,
        valor,
        cantidad
    FROM producto;
END//
DELIMITER ;

-- Volcando estructura para tabla bicicletas_compartidas.producto
DROP TABLE IF EXISTS `producto`;
CREATE TABLE IF NOT EXISTS `producto` (
  `codigo` int NOT NULL,
  `categoria` varchar(80) NOT NULL,
  `descripcion` varchar(150) NOT NULL,
  `valor` decimal(12,2) NOT NULL,
  `cantidad` int NOT NULL,
  PRIMARY KEY (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla bicicletas_compartidas.producto_factura
DROP TABLE IF EXISTS `producto_factura`;
CREATE TABLE IF NOT EXISTS `producto_factura` (
  `cod_pro` int NOT NULL,
  `cod_fac` int NOT NULL,
  `cantidad` int NOT NULL,
  PRIMARY KEY (`cod_pro`,`cod_fac`),
  KEY `fk_pf_factura` (`cod_fac`),
  CONSTRAINT `fk_pf_factura` FOREIGN KEY (`cod_fac`) REFERENCES `factura` (`cod_fac`),
  CONSTRAINT `fk_pf_producto` FOREIGN KEY (`cod_pro`) REFERENCES `producto` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla bicicletas_compartidas.registro_energia
DROP TABLE IF EXISTS `registro_energia`;
CREATE TABLE IF NOT EXISTS `registro_energia` (
  `id_registro` int unsigned NOT NULL AUTO_INCREMENT,
  `id_estacion` int unsigned NOT NULL,
  `consumo_kwh` decimal(10,2) NOT NULL,
  `fecha_registro` datetime NOT NULL,
  PRIMARY KEY (`id_registro`),
  KEY `FK_energia_estaciones` (`id_estacion`),
  CONSTRAINT `FK_energia_estaciones` FOREIGN KEY (`id_estacion`) REFERENCES `estaciones` (`id_estacion`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `registro_energia_chk_1` CHECK ((`consumo_kwh` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla bicicletas_compartidas.roles
DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id_rol` int unsigned NOT NULL AUTO_INCREMENT,
  `codigo_rol` varchar(50) NOT NULL,
  `nombre_rol` varchar(100) NOT NULL,
  `permisos` json DEFAULT NULL,
  `nivel_acceso` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `UQ_roles_codigo` (`codigo_rol`),
  UNIQUE KEY `UQ_roles_nombre` (`nombre_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla bicicletas_compartidas.tarifas
DROP TABLE IF EXISTS `tarifas`;
CREATE TABLE IF NOT EXISTS `tarifas` (
  `id_tarifa` int unsigned NOT NULL AUTO_INCREMENT,
  `tipo_tarifa` varchar(50) NOT NULL,
  `precio_por_minuto` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_tarifa`),
  CONSTRAINT `tarifas_chk_1` CHECK ((`precio_por_minuto` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla bicicletas_compartidas.transacciones
DROP TABLE IF EXISTS `transacciones`;
CREATE TABLE IF NOT EXISTS `transacciones` (
  `id_transaccion` int unsigned NOT NULL AUTO_INCREMENT,
  `id_alquiler` int unsigned NOT NULL,
  `id_metodo` int unsigned NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `fecha_pago` datetime NOT NULL,
  PRIMARY KEY (`id_transaccion`),
  KEY `FK_transacciones_alquileres` (`id_alquiler`),
  KEY `FK_transacciones_metodos` (`id_metodo`),
  CONSTRAINT `FK_transacciones_alquileres` FOREIGN KEY (`id_alquiler`) REFERENCES `alquileres` (`id_alquiler`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `FK_transacciones_metodos` FOREIGN KEY (`id_metodo`) REFERENCES `metodos_pago` (`id_metodo`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `transacciones_chk_1` CHECK ((`valor` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla bicicletas_compartidas.usuarios
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id_usuario` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `id_rol` int unsigned NOT NULL,
  `fecha_registro` date DEFAULT (curdate()),
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `email` (`email`),
  KEY `FK_usuarios_roles` (`id_rol`),
  CONSTRAINT `FK_usuarios_roles` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
