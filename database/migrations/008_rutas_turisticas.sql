-- ============================================================
-- Migración 008: Rutas Turísticas de Jardín
-- Crea la tabla rutas_turisticas con las rutas DEMO de Jardín
-- (coordenadas aproximadas; sustituir por ubicaciones reales
--  antes de una operación comercial, según README).
-- ============================================================

CREATE TABLE IF NOT EXISTS `rutas_turisticas` (
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

INSERT INTO `rutas_turisticas` (`nombre`, `descripcion`, `dificultad`, `distancia_km`, `duracion_est`, `lat`, `lng`, `activa`) VALUES
('Centro Histórico Colonia', 'Recorrido por el Parque Principal, la Basílica Menor de la Inmaculada Concepción y las calles coloniales de Jardín.', 'facil', '2.50', '45 min', 5.5991, -75.8192, 1),
('Charco Corazón', 'Senderismo guiado hacia el Charco Corazón con pozos naturales de agua cristalina.', 'media', '4.00', '90 min', 5.5921, -75.8285, 1),
('Cascada La Escalera', 'Ruta hasta la Cascada La Escalera, ideal para aficionados a la fotografía.', 'media', '5.00', '2 h', 5.5850, -75.8300, 1),
('Camino a Las Tangas', 'Rodada de montaña con paisajes cafeteros y avistamiento de aves.', 'media', '8.00', '3 h', 5.5980, -75.7950, 1),
('Mirador Cristo Rey', 'Ascenso exigente hasta el Mirador de Cristo Rey con vista panorámica del municipio.', 'alta', '6.00', '2 h 30 min', 5.6100, -75.8100, 1);