-- ============================================================
-- Migración 004: Autenticación de usuarios (única vía autenticación)
-- ============================================================
-- No se renombra ni sobrescribe roles existentes (ADMIN/OPER/MANT/CLI/SUP).
-- NO toca los usuarios reales (id 1-8): solo inserta dos usuarios de
-- prueba como filas NUEVAS (AUTO_INCREMENT ≠ 1-2).
-- ============================================================

USE `bicicletas_compartidas`;

/*!40101 SET NAMES utf8mb4 */;

-- 1. Agregar columna password (los usuarios existentes quedan sin acceso)
ALTER TABLE `usuarios`
    ADD COLUMN `password` VARCHAR(255) NOT NULL DEFAULT '' AFTER `fecha_registro`;

-- 2. Usuarios de prueba como filas nuevas
--    admin@bicijardin.com -> rol ADMIN ; user@bicijardin.com -> rol CLI
--    Contraseña para ambos: password123
--    Hash generado con: php -r "echo password_hash('password123', PASSWORD_BCRYPT);"
INSERT INTO `usuarios` (`nombre`, `apellido`, `email`, `telefono`, `id_rol`, `password`)
SELECT 'Andrés', 'López', 'admin@bicijardin.com', '312-456-7890', r.id_rol,
       '$2y$10$YOJOTx.7ozP3BK5MNlGoae5UEuK6eq7xka9QNjfEpzNJv3/tDTGby'
FROM roles r WHERE r.codigo_rol = 'ADMIN'
ON DUPLICATE KEY UPDATE `password` = VALUES(`password`);

INSERT INTO `usuarios` (`nombre`, `apellido`, `email`, `telefono`, `id_rol`, `password`)
SELECT 'María', 'Gómez', 'user@bicijardin.com', '315-789-0123', r.id_rol,
       '$2y$10$YOJOTx.7ozP3BK5MNlGoae5UEuK6eq7xka9QNjfEpzNJv3/tDTGby'
FROM roles r WHERE r.codigo_rol = 'CLI'
ON DUPLICATE KEY UPDATE `password` = VALUES(`password`);