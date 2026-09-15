-- ============================================================
-- Migración 003: Fidelización mensual (Eco-Puntos)
-- ============================================================
-- Añade (si faltan) columnas de fidelización y recrea las vistas
-- de conciliación / fidelización. Idempotente.
-- ============================================================

USE `bicicletas_compartidas`;

-- Columnas de fidelización en usuarios (additivas, si no existen)
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = 'bicicletas_compartidas'
               AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'dias_alquiler_mes');
SET @sql := IF(@col = 0,
    'ALTER TABLE `usuarios` ADD COLUMN `dias_alquiler_mes` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `fecha_registro`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = 'bicicletas_compartidas'
               AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'descuento_fidelidad');
SET @sql := IF(@col = 0,
    'ALTER TABLE `usuarios` ADD COLUMN `descuento_fidelidad` DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER `dias_alquiler_mes`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Vista de clientes frecuentes (fidelización mensual)
CREATE OR REPLACE VIEW `vw_clientes_frecuentes` AS
SELECT
    `u`.`id_usuario`,
    CONCAT(`u`.`nombre`, ' ', `u`.`apellido`) AS `cliente`,
    `u`.`email`,
    `u`.`dias_alquiler_mes`,
    `u`.`descuento_fidelidad`,
    CASE
        WHEN `u`.`dias_alquiler_mes` > 20 THEN 'Elegible para Descuento (10%)'
        ELSE CONCAT('Faltan ', (21 - `u`.`dias_alquiler_mes`), ' días')
    END AS `estado_fidelidad`
FROM `usuarios` `u`
ORDER BY `u`.`dias_alquiler_mes` DESC;

-- Vista de conciliación financiera
CREATE OR REPLACE VIEW `vw_conciliacion_financiera` AS
SELECT
    `a`.`id_alquiler`,
    `a`.`id_usuario`,
    CONCAT(`u`.`nombre`, ' ', `u`.`apellido`) AS `cliente`,
    `a`.`costo_total` AS `monto_alquiler`,
    IFNULL(SUM(`t`.`valor`), 0.00) AS `monto_pagado`,
    (`a`.`costo_total` - IFNULL(SUM(`t`.`valor`), 0.00)) AS `diferencia`,
    CASE
        WHEN IFNULL(SUM(`t`.`valor`), 0.00) = `a`.`costo_total` THEN 'Conciliado'
        WHEN IFNULL(SUM(`t`.`valor`), 0.00) < `a`.`costo_total` THEN 'Pago Parcial / Pendiente'
        ELSE 'Sobrepago / Error'
    END AS `estado_conciliacion`
FROM (`alquileres` `a`
    JOIN `usuarios` `u` ON (`a`.`id_usuario` = `u`.`id_usuario`)
    LEFT JOIN `transacciones` `t` ON (`a`.`id_alquiler` = `t`.`id_alquiler`))
WHERE `a`.`fecha_fin` IS NOT NULL
GROUP BY `a`.`id_alquiler`, `a`.`id_usuario`, `u`.`nombre`, `u`.`apellido`, `a`.`costo_total`;

-- Vista de transacciones huérfanas
CREATE OR REPLACE VIEW `vw_transacciones_huerfanas` AS
SELECT
    `t`.`id_transaccion`,
    `t`.`id_alquiler`,
    `t`.`valor`,
    `t`.`fecha_pago`,
    'Transacción sin registro de alquiler' AS `motivo`
FROM (`transacciones` `t`
    LEFT JOIN `alquileres` `a` ON (`t`.`id_alquiler` = `a`.`id_alquiler`))
WHERE `a`.`id_alquiler` IS NULL;