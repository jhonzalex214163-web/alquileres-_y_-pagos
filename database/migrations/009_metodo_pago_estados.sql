-- ============================================================
-- Migración 009: Método de pago y estados de revisión
-- Añade la columna `metodo` (TARJETA / TRANSFERENCIA / QR) a
-- transacciones y recrea la vista de conciliación para que
-- solo computen pagos EXITOSO/APROBADO (los pendientes y
-- rechazados no cuentan como pagados).
-- ============================================================

ALTER TABLE `transacciones`
  ADD COLUMN `metodo` varchar(20) NOT NULL DEFAULT 'TARJETA' AFTER `monto`;

-- Los pagos QUIEDAN PENDIENTES hasta que el admin los compruebe;
-- por eso la conciliación solo suma los estados aprobados.
DROP VIEW IF EXISTS `vw_conciliacion_financiera`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_conciliacion_financiera` AS
select `a`.`id_alquiler` AS `id_alquiler`,
       `a`.`id_usuario` AS `id_usuario`,
       concat(`u`.`nombre`,' ',`u`.`apellido`) AS `cliente`,
       `a`.`costo_total` AS `monto_alquiler`,
       ifnull(sum(case when `t`.`estado` in ('EXITOSO','APROBADO') then `t`.`valor` else 0 end),0.00) AS `monto_pagado`,
       (`a`.`costo_total` - ifnull(sum(case when `t`.`estado` in ('EXITOSO','APROBADO') then `t`.`valor` else 0 end),0.00)) AS `diferencia`,
       (case
         when (ifnull(sum(case when `t`.`estado` in ('EXITOSO','APROBADO') then `t`.`valor` else 0 end),0.00) = `a`.`costo_total`) then 'Conciliado'
         when (ifnull(sum(case when `t`.`estado` in ('EXITOSO','APROBADO') then `t`.`valor` else 0 end),0.00) < `a`.`costo_total`) then 'Pago Parcial / Pendiente'
         else 'Sobrepago / Error'
       end) AS `estado_conciliacion`
from ((`alquileres` `a`
  join `usuarios` `u` on((`a`.`id_usuario` = `u`.`id_usuario`)))
  left join `transacciones` `t` on((`a`.`id_alquiler` = `t`.`id_alquiler`)))
where (`a`.`fecha_fin` is not null)
group by `a`.`id_alquiler`,`a`.`id_usuario`,`u`.`nombre`,`u`.`apellido`,`a`.`costo_total`;