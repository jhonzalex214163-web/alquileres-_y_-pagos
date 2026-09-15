-- ============================================================
-- Migración 005: alquileres -> estado y minutos_totales
-- ============================================================
-- Columnas usadas por models/Alquiler.php (iniciarViaje/cerrarViaje).
-- Backfill: filas cerradas = 'finalizado', abiertas = 'en_curso'.
-- ============================================================

USE `bicicletas_compartidas`;

ALTER TABLE `alquileres`
    ADD COLUMN `estado` VARCHAR(20) NOT NULL DEFAULT 'finalizado' AFTER `estacion_destino`,
    ADD COLUMN `minutos_totales` INT UNSIGNED NULL DEFAULT NULL AFTER `fecha_fin`;

-- Backfill de minutos para viajes finalizados y estado coherente
UPDATE `alquileres`
SET `minutos_totales` = TIMESTAMPDIFF(MINUTE, `fecha_inicio`, `fecha_fin`)
WHERE `fecha_fin` IS NOT NULL;

UPDATE `alquileres`
SET `estado` = 'en_curso'
WHERE `fecha_fin` IS NULL;