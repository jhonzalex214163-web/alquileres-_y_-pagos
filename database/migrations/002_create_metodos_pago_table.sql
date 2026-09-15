-- ============================================================
-- Migración 002: tabla metodos_pago (idempotente)
-- ============================================================
-- Refleja la estructura real de la BD. Si la tabla ya existe
-- (sembrada por script.sql), no hace nada.
-- ============================================================

USE `bicicletas_compartidas`;

CREATE TABLE IF NOT EXISTS `metodos_pago` (
  `id_metodo` int unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` int unsigned NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `descripcion` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id_metodo`),
  KEY `FK_metodos_pago_usuarios` (`id_usuario`),
  CONSTRAINT `FK_metodos_pago_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;