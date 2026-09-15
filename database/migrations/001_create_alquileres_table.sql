-- ============================================================
-- Migración 001: tabla alquileres (idempotente)
-- ============================================================
-- Refleja la estructura real de la BD. Si la tabla ya existe
-- (sembrada por script.sql), no hace nada.
-- ============================================================

USE `bicicletas_compartidas`;

CREATE TABLE IF NOT EXISTS `alquileres` (
  `id_alquiler` int unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` int unsigned NOT NULL,
  `id_bicicleta` int unsigned NOT NULL,
  `estacion_origen` int unsigned NOT NULL,
  `estacion_destino` int unsigned DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'en_curso',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;