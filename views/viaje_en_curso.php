<!-- views/viaje_en_curso.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>BiciJardín - Viaje en Curso</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="dashboard-card">
        <h2>🚴 Viaje Activo</h2>
        <div id="alerta-box" style="display:none; color: red; font-weight: bold;"></div>
        <p>Batería disponible: <strong id="bateria-nivel">--%</strong></p>
        <p>Estado del vehículo: <strong id="estado-bici">Cargando...</strong></p>
        
        <form id="form-cerrar-viaje">
            <label for="estacion_destino">Estación de Entrega:</label>
            <select name="estacion_destino" id="estacion_destino" required>
                <!-- Opciones dinámicas de estaciones -->
                <option value="1">Estación Parque Principal</option>
                <option value="2">Estación Hospital</option>
            </select>
            <button type="submit">Finalizar Viaje</button>
        </form>
    </div>
    <script src="../js/trip_monitor.js"></script>
</body>
</html>