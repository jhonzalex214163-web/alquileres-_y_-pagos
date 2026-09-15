// js/trip_monitor.js - Seguimiento del viaje en tiempo real
// Muestra en los contenedores de batería, estado del vehículo y alerta el estado
// con el que el usuario adquiere la bicicleta vs el estado actual/en el que la entrega.
// Soporta dos objetivos: el viaje activo global o la bicicleta del cliente seleccionado
// en "Rutas por Usuario" (batería en tiempo real de cada bici al ser usada).
let monitoreoTimer = null;
let monitoreoObjetivo = 'activo';   // 'activo' | 'usuario'
let monitoreoIdUsuario = null;
let alertaBateriaTrip = null;       // id_alquiler ya alertado (evita spam del Sweet Alert)

function iniciarMonitoreo(idBicicleta) {
    monitoreoObjetivo = 'activo';
    monitoreoIdUsuario = null;
    if (monitoreoTimer) clearInterval(monitoreoTimer);
    refrescarMonitoreo();
    monitoreoTimer = setInterval(refrescarMonitoreo, 5000); // refresco en tiempo real
}

// Cambia el objetivo del monitoreo a la bicicleta del cliente seleccionado
function monitorearUsuario(idUsuario) {
    monitoreoObjetivo = 'usuario';
    monitoreoIdUsuario = idUsuario;
    if (monitoreoTimer) clearInterval(monitoreoTimer);
    monitoreoTimer = setInterval(refrescarMonitoreo, 5000);
    refrescarMonitoreo();
}

function refrescarMonitoreo() {
    const base = (document.querySelector('meta[name="base-url"]') || {}).content || 'controllers/';
    const url = monitoreoObjetivo === 'usuario'
        ? base + 'AlquilerController.php?action=bateria_usuario&id_usuario=' + encodeURIComponent(monitoreoIdUsuario)
        : base + 'AlquilerController.php?action=monitorear';

    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.telemetria) {
                const t = data.telemetria;
                const bateriaBaja = t.bateria_actual < 30;

                const etqBateria = document.getElementById('bateria-nivel');
                if (etqBateria) {
                    etqBateria.innerText = `${t.bateria_inicio}% → ${t.bateria_actual}%`;
                    // Alerta en rojo cuando la batería está por debajo del 30%
                    etqBateria.style.color = bateriaBaja ? '#ef4444' : '';
                    etqBateria.style.textShadow = bateriaBaja ? '0 0 12px rgba(239,68,68,0.45)' : '';
                }

                const detBateria = document.getElementById('bateria-detalle');
                if (detBateria) detBateria.innerText =
                    `Adquirida: ${t.bateria_inicio}% · Actual: ${t.bateria_actual}%` +
                    (bateriaBaja ? ' ⚠️ Batería Baja' : '') +
                    `${t.viaje_activo ? ' (en ruta)' : ' (entregada)'}`;

                const etqEstado = document.getElementById('estado-bici');
                const coloresEstado = {
                    'Disponible': '#22c55e',
                    'En Ruta': '#3b82f6',
                    'En Mantenimiento': '#ef4444',
                    'Próxima a Mantenimiento': '#f59e0b'
                };
                if (etqEstado) {
                    etqEstado.innerText = t.estado_actual;
                    etqEstado.style.color = coloresEstado[t.estado_actual] || '#22c55e';
                }

                const detEstado = document.getElementById('estado-detalle');
                if (detEstado) detEstado.innerText =
                    `Adquirido: ${t.estado_inicio} → Actual: ${t.estado_actual}${t.estacion_destino ? ' · destino: ' + t.estacion_destino : ''}`;

                const etqAlerta = document.getElementById('alerta-box');
                if (etqAlerta) etqAlerta.innerText = data.alerta;

                const detAlerta = document.getElementById('alerta-detalle');
                if (detAlerta) {
                    const malas = [];
                    if (t.proxima_mantenimiento) malas.push('⚠️ Próxima a mantenimiento');
                    if (t.bateria_actual < 15) malas.push('⚠️ Batería baja');
                    detAlerta.innerText = `Al adquirir: Normal → Actual: ${malas.length ? malas.join(' · ') : 'Normal'}`;
                }

                const quien = document.getElementById('monitoreo-quien');
                if (quien) quien.innerText =
                    (monitoreoObjetivo === 'usuario' ? '👥 ' : '🌐 ') +
                    `Viaje #${t.id_alquiler} · ${t.cliente} · Bici ${t.num_serie} · adquisición en ${t.estacion_retiro}`;

                avisarBateriaBaja(t);
            }
        })
        .catch(err => console.error('Error en monitoreo:', err));
}

// Sweet Alert cuando la batería de la bicicleta en uso cae por debajo del 30%
function avisarBateriaBaja(t) {
    if (!t.viaje_activo || t.bateria_actual >= 30) return;
    if (alertaBateriaTrip === t.id_alquiler) return; // una sola alerta por viaje
    alertaBateriaTrip = t.id_alquiler;

    const mensaje =
        `La bicicleta de ${t.cliente} está al ${t.bateria_actual}% de batería. ` +
        `Rebaja del umbral del 30%. ${t.estacion_destino ? 'Dirígete a ' + t.estacion_destino + ' o ' : ''}busca una estación cercana.`;

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'warning',
            title: '🔋 Batería baja',
            text: mensaje,
            confirmButtonText: 'Entendido'
        });
    } else {
        alert('🔋 Batería baja: ' + mensaje);
    }
}