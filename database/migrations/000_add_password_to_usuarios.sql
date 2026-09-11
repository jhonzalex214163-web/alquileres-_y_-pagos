-- ============================================================
-- Migración: bicicletas_compartidas - Agregar autenticación
-- ============================================================
-- Basado en database/script.sql
-- Añade columna password a usuarios e inserta roles + usuarios de prueba
-- ============================================================

USE `bicicletas_compartidas`;

-- 1. Agregar columna password a la tabla usuarios
ALTER TABLE `usuarios`
    ADD COLUMN `password` VARCHAR(255) NOT NULL DEFAULT '' AFTER `fecha_registro`;

-- 2. Insertar roles predeterminados
INSERT INTO `roles` (`id_rol`, `codigo_rol`, `nombre_rol`, `permisos`, `nivel_acceso`) VALUES
    (1, 'admin', 'Administrador', '["usuarios","roles","bicicletas","estaciones","conciliacion","auditoria","reportes"]', 10),
    (2, 'user', 'Usuario', '["fidelizacion","estaciones","mis_alquileres"]', 1)
ON DUPLICATE KEY UPDATE
    `nombre_rol` = VALUES(`nombre_rol`),
    `permisos` = VALUES(`permisos`),
    `nivel_acceso` = VALUES(`nivel_acceso`);

-- 3. Insertar usuarios de prueba con contraseñas hasheadas
--    Contraseña para ambos: password123
--    Hash generado con: php -r "echo password_hash('password123', PASSWORD_BCRYPT);"
INSERT INTO `usuarios` (`id_usuario`, `nombre`, `apellido`, `email`, `telefono`, `id_rol`, `password`) VALUES
    (1, 'Andrés', 'López', 'admin@bicijardin.com', '312-456-7890', 1, '$2y$10$N6EZ6RPK3Gxi4399MXrh3Oe9wKNoWHW3mi4QniOW00zJwvxASbT.y'),
    (2, 'Maria',  'Gómez',  'user@bicijardin.com',  '315-789-0123', 2, '$2y$10$N6EZ6RPK3Gxi4399MXrh3Oe9wKNoWHW3mi4QniOW00zJwvxASbT.y')
ON DUPLICATE KEY UPDATE
    `password` = VALUES(`password`);
