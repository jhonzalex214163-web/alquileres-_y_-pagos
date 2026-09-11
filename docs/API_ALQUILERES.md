# Documentación de APIs y Flujo de Alquiler (BiciJardín)

## Flujo del Viaje

1. **Desbloqueo e Inicio**:
   - `POST /controllers/AlquilerController.php?action=iniciar`
   - Payload: `{"id_usuario": 1, "id_bicicleta": 2, "estacion_origen": 1}`
   - *Acción*: Cambia estado de bicicleta a `Alquilada` y genera el registro de alquiler en `en_curso`.

2. **Monitoreo en Tiempo Real**:
   - `GET /controllers/AlquilerController.php?action=monitorear&id_bicicleta=2`
   - *Respuesta*: Devuelve nivel de batería, geolocalización y alertas de mantenimiento.

3. **Cierre y Cobro**:
   - `POST /controllers/AlquilerController.php?action=cerrar`
   - Payload: `{"id_alquiler": 10, "estacion_destino": 2}`
   - *Acción*: Valida disponibilidad de estación destino, calcula la diferencia de tiempo en minutos, aplica tarifa y actualiza bicicleta a `Disponible`.