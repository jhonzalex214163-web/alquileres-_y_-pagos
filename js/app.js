// Base de Datos Simulada (Reflejando la BD MySQL)
const mockData = {
    usuarios: {
        1: { id: 1, nombre: 'Andrés López', email: 'andres.lopez@email.com', dias: 22, descuento: 10.00 },
        2: { id: 2, nombre: 'Maria Gomez', email: 'maria.gomez@email.com', dias: 12, descuento: 0.00 }
    },
    estaciones: [
        { id: 1, codigo: 'EST-JAR-01', nombre: 'Parque Principal El Libertador', lat: 5.5986, lng: -75.8194, cap: 15 },
        { id: 2, codigo: 'EST-JAR-02', nombre: 'Basílica Menor Inmaculada Concepción', lat: 5.5991, lng: -75.8190, cap: 10 },
        { id: 3, codigo: 'EST-JAR-03', nombre: 'Teleférico / Camellón del Tequendamita', lat: 5.5962, lng: -75.8231, cap: 12 },
        { id: 4, codigo: 'EST-JAR-04', nombre: 'Sector Charco Corazón', lat: 5.5921, lng: -75.8285, cap: 8 },
        { id: 5, codigo: 'EST-JAR-05', nombre: 'Casa de la Cultura y Hospital', lat: 5.6012, lng: -75.8162, cap: 10 }
    ],
    conciliacion: [
        { id_alquiler: 13, cliente: 'Andrés López', costo: 25.00, pagado: 25.00, dif: 0.00, estado: 'Conciliado' },
        { id_alquiler: 14, cliente: 'Maria Gomez', costo: 18.00, pagado: 10.00, dif: -8.00, estado: 'Pago Parcial' },
        { id_alquiler: 15, cliente: 'Desconocido', costo: 0.00, pagado: 15.00, dif: 15.00, estado: 'Transacción Huérfana' }
    ],
    logs: [
        { fecha: '2026-09-05 14:20:10', accion: 'PROCESO_CONCILIACION', detalle: 'Conciliación masiva ejecutada sin errores.' },
        { fecha: '2026-09-05 14:22:05', accion: 'ID_HUERFANO_DETECTADO', detalle: 'Transacción #902 no registra un id_alquiler válido.' }
    ]
};

let map = null;

function toggleDayNight() {
    const body = document.body;
    const toggle = document.getElementById('theme-toggle');
    const isDay = body.classList.contains('day-mode');

    if (isDay) {
        body.classList.remove('day-mode');
        if (toggle) toggle.classList.remove('active');
        localStorage.setItem('theme', 'night');
    } else {
        body.classList.add('day-mode');
        if (toggle) toggle.classList.add('active');
        localStorage.setItem('theme', 'day');
    }
}

function cargarTemaPreferido() {
    const theme = localStorage.getItem('theme');
    const toggle = document.getElementById('theme-toggle');
    if (theme === 'day') {
        document.body.classList.add('day-mode');
        if (toggle) toggle.classList.add('active');
    }
}

function switchTab(viewId) {
    // 1. Ocultar todas las secciones que empiecen por "view-"
    document.querySelectorAll('[id^="view-"]').forEach(view => {
        view.style.display = 'none';
    });

    // 2. Mostrar únicamente la vista seleccionada
    const activeView = document.getElementById(viewId);
    if (activeView) {
        activeView.style.display = 'block';
    }

    // 3. Actualizar estado visual de las pestañas/botones
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    if (viewId === 'view-fidelizacion') {
        document.getElementById('tab-fidelizacion')?.classList.add('active');
    } else if (viewId === 'view-auditoria') {
        document.getElementById('tab-auditoria')?.classList.add('active');
    }

    // 4. Forzar el redibujado del mapa si se usa Leaflet
    if (typeof map !== 'undefined' && map !== null) {
        setTimeout(() => {
            map.invalidateSize();
        }, 200);
    }
}

// Cargar datos dinámicos de usuario
function cargarDatosUsuario(userId) {
    const user = mockData.usuarios[userId];
    if (!user) return;

    const profileName = document.getElementById('profile-name');
    const profileEmail = document.getElementById('profile-email');
    const profileDays = document.getElementById('profile-days');

    if (profileName) profileName.innerText = user.nombre;
    if (profileEmail) profileEmail.innerText = user.email;
    if (profileDays) profileDays.innerText = user.dias;

    const progressPercent = Math.min(100, Math.round((user.dias / 20) * 100));
    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');
    const progressDesc = document.getElementById('progress-desc');
    const profileBadge = document.getElementById('profile-badge');

    if (progressBar) progressBar.style.width = `${progressPercent}%`;
    if (progressText) progressText.innerText = `${progressPercent}% Completado`;

    if (user.dias >= 20) {
        if (profileBadge) profileBadge.innerHTML = `<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">10% OFF Habilitado</span>`;
        if (progressDesc) progressDesc.innerText = "¡Felicidades! Has superado los 20 días de alquiler en el mes. Tu descuento del 10% se aplicará automáticamente en tu próxima factura.";
    } else {
        if (profileBadge) profileBadge.innerHTML = `<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-slate-700 text-slate-300 border border-subtle">Sin Descuento</span>`;
        if (progressDesc) progressDesc.innerText = `Te faltan ${20 - user.dias} días de alquiler este mes para desbloquear tu 10% de descuento.`;
    }
}

// Inicializar Mapa de Jardín
function initMap() {
    if (map) return;
    const mapElement = document.getElementById('map');
    if (!mapElement || typeof L === 'undefined') return;

    map = L.map('map').setView([5.5986, -75.8194], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    mockData.estaciones.forEach(est => {
        L.marker([est.lat, est.lng])
            .addTo(map)
            .bindPopup(`<b>${est.codigo}</b><br>${est.nombre}<br>Capacidad: ${est.cap} bicis`);
    });
}

// Cargar Tabla de Conciliación
function renderConciliacion() {
    const tbody = document.getElementById('tabla-conciliacion');
    if (tbody) {
        tbody.innerHTML = mockData.conciliacion.map(item => {
            let badgeClass = 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30';
            if (item.estado === 'Pago Parcial') badgeClass = 'bg-amber-500/10 text-amber-400 border-amber-500/30';
            if (item.estado === 'Transacción Huérfana') badgeClass = 'bg-rose-500/10 text-rose-400 border-rose-500/30';

            return `
                <tr>
                    <td class="p-4 font-semibold">#${item.id_alquiler}</td>
                    <td class="p-4">${item.cliente}</td>
                    <td class="p-4">$${item.costo.toFixed(2)}</td>
                    <td class="p-4">$${item.pagado.toFixed(2)}</td>
                    <td class="p-4 font-bold ${item.dif < 0 ? 'text-amber-400' : 'text-slate-200'}">$${item.dif.toFixed(2)}</td>
                    <td class="p-4"><span class="px-2.5 py-1 text-xs font-bold rounded-full border ${badgeClass}">${item.estado}</span></td>
                </tr>
            `;
        }).join('');
    }

    // Validación segura para evitar que la app crashee si log-container no existe
    const logContainer = document.getElementById('log-container');
    if (logContainer) {
        logContainer.innerHTML = mockData.logs.map(log => `
            <div class="p-2.5 bg-slate-900 rounded-lg border border-subtle flex justify-between text-slate-400">
                <span>[${log.fecha}] <strong class="text-emerald-400">${log.accion}</strong>: ${log.detalle}</span>
            </div>
        `).join('');
    }
}

function ejecutarStoredProcedure() {
    alert("Simulación: Executing CALL sp_conciliar_y_auditar();\n\nLos datos de la vista han sido re-sincronizados.");
}

// Inicialización general
document.addEventListener('DOMContentLoaded', () => {
    cargarTemaPreferido();
    cargarDatosUsuario(1);
    renderConciliacion();
    initMap();
});

function mostrarSeccion(nombreSeccion) {
    // 1. Ocultar todas las secciones
    const secciones = document.querySelectorAll('.view-section');
    secciones.forEach(sec => sec.style.display = 'none');

    // 2. Remover estado activo de todos los botones
    const botones = document.querySelectorAll('.nav-btn');
    botones.forEach(btn => btn.classList.remove('active'));

    // 3. Mostrar la sección seleccionada y activar su botón
    const seccionObjetivo = document.getElementById(`sec-${nombreSeccion}`);
    if (seccionObjetivo) {
        seccionObjetivo.style.display = 'block';
    }

    // Activar estilo en el botón presionado
    event.target.classList.add('active');

    // Iniciar el polling si se entra a la vista de monitoreo
    if (nombreSeccion === 'monitoreo') {
        iniciarMonitoreo(1); // ID de prueba para telemetría
    }
}

// Cargar transacciones registradas en la tabla al abrir la pestaña
function cargarTransaccionesJS() {
    fetch('controllers/PagoController.php?action=listar_transacciones')
        .then(res => res.json())
        .then(data => {
            const tbody = document.getElementById('tabla-transacciones-base');
            if (!tbody) return;
            tbody.innerHTML = '';

            data.forEach(tx => {
                const badgeClass = tx.estado === 'EXITOSO' ? 'badge-success' : 'badge-danger';
                tbody.innerHTML += `
                    <tr>
                        <td>TX-${tx.id_transaccion}</td>
                        <td>ALQ-${tx.id_alquiler}</td>
                        <td><code>${tx.referencia_externa}</code></td>
                        <td>$${parseFloat(tx.monto).toLocaleString()}</td>
                        <td><span class="${badgeClass}">${tx.estado}</span></td>
                        <td>${tx.fecha_transaccion}</td>
                    </tr>
                `;
            });
        })
        .catch(err => console.error('Error al cargar transacciones:', err));
}

// Interfaz de registro de pago y carga de comprobante [T-06-03, T-06-04, T-06-08]
function registrarPagoJS() {
    const form = document.getElementById('form-procesar-pago');
    const formData = new FormData();
    
    formData.append('id_alquiler', document.getElementById('pago_alquiler_id').value);
    formData.append('monto', document.getElementById('pago_monto').value);
    formData.append('referencia_externa', 'REF_' + Date.now()); // Genera la ref de la pasarela [T-07-05]
    
    const fileInput = document.getElementById('pago_evidencia');
    if (fileInput.files[0]) {
        formData.append('evidencia', fileInput.files[0]); // [T-06-04]
    }

    fetch('controllers/PagoController.php?action=registrar_pago', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        alert(res.message);
        if (res.status === 'success') {
            form.reset();
            cargarTransaccionesJS(); // Actualizar la tabla de transacciones de inmediato
        }
    })
    .catch(err => console.error('Error procesando pago:', err));
}

// Revocación lógica del token [T-06-07]
function eliminarTokenJS(tokenId) {
    if (!confirm('¿Desea revocar este token de pasarela?')) return;

    fetch('controllers/PagoController.php?action=revocar_token', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_token: tokenId })
    })
    .then(res => res.json())
    .then(res => {
        alert(res.message);
    })
    .catch(err => console.error('Error al revocar token:', err));
}