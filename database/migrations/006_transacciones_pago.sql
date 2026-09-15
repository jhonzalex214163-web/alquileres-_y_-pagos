-- ============================================================
-- Migración 006: transacciones -> registro de pagos (API PagoController)
-- ============================================================
-- Añade campos usados por la API: referencia_externa (única),
-- monto, estado y comprobante_url. Relaja id_metodo (la tabla
-- metodos_pago está vacía y el formulario no lo recoge).
-- ============================================================

USE `bicicletas_compartidas`;

ALTER TABLE `transacciones`
    ADD COLUMN `referencia_externa` VARCHAR(100) DEFAULT NULL AFTER `id_metodo`,
    ADD UNIQUE KEY `UQ_transacciones_referencia` (`referencia_externa`),
    ADD COLUMN `monto` DECIMAL(10,2) DEFAULT NULL AFTER `referencia_externa`,
    ADD COLUMN `estado` VARCHAR(20) NOT NULL DEFAULT 'EXITOSO' AFTER `monto`,
    ADD COLUMN `comprobante_url` VARCHAR(255) DEFAULT NULL AFTER `estado`;

-- id_metodo: ya no es obligatorio (no hay métodos sembrados y el
-- formulario de pago no los solicita aún)
ALTER TABLE `transacciones`
    MODIFY COLUMN `id_metodo` INT UNSIGNED NULL;

-- Backfill: monto refleja el valor histórico
UPDATE `transacciones` SET `monto` = `valor` WHERE `monto` IS NULL;