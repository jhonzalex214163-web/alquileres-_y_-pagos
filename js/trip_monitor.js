// js/trip_monitor.js - Seguimiento del viaje en tiempo real
function iniciarMonitoreo(idBicicleta) {
    setInterval(() => {
        fetch(`/controllers/AlquilerController.php?action=monitorear&id_bicicleta=${idBicicleta}`)
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    document.getElementById('bateria-nivel').innerText = data.telemetria.nivel_bateria + '%';
                    document.getElementById('estado-bici').innerText = data.telemetria.estado;
                    
                    if(data.telemetria.nivel_bateria < 15) {
                        document.getElementById('alerta-box').innerText = "⚠️ Advertencia: Batería baja, busca una estación cercana.";
                        document.getElementById('alerta-box').style.display = 'block';
                    }
                }
            });
    }, 5000); // Polling cada 5 segundos
}