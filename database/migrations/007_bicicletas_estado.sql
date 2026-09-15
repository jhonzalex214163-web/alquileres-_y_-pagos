-- ============================================================
-- Migración 007: normalizar bicicletas.estado a minúsculas
-- ============================================================
-- Los valores reales son 'disponible', 'alquilada', 'mantenimiento'.
-- El modelo (models/Alquiler.php) ahora compara en minúsculas.
-- Esta migración normaliza cualquier dato residual en mayúsculas.
-- ============================================================

USE `bicicletas_compartidas`;

UPDATE `bicicletas` SET `estado` = LOWER(`estado`);
UPDATE `bicicletas` SET `estado` = 'disponible' WHERE `estado` NOT IN ('disponible', 'alquilada', 'mantenimiento');