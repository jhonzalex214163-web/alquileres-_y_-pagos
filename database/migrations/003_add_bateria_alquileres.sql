-- 003_add_bateria_alquileres.sql
-- Estado de la batería en cada viaje: cómo se recibió la bicicleta (bateria_inicio)
-- y cómo se entregó (bateria_fin al cierre del alquiler).
ALTER TABLE `alquileres`
    ADD COLUMN `bateria_inicio` INT UNSIGNED NULL DEFAULT NULL AFTER `costo_total`,
    ADD COLUMN `bateria_fin` INT UNSIGNED NULL DEFAULT NULL AFTER `bateria_inicio`;