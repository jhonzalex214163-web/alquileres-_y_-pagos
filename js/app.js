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

// Base URL para las APIs (definida via <meta name="base-url"> en cada vista)
const API_BASE = (document.querySelector('meta[name="base-url"]') || {}).content || 'controllers/';

// Escapado de texto para evitar inyección HTML (XSS) en salidas dinámicas
function esc(value) {
    if (value === null || value === undefined) return '';
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function csrfHeaders() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? { 'X-CSRF-Token': meta.content } : {};
}

// Alertas estéticas con SweetAlert2 (tema oscuro acorde al panel)
function swalAlert(opts) {
    return Swal.fire(Object.assign({
        background: '#0d2318',
        color: '#e2f1e8',
        confirmButtonColor: '#22c55e'
    }, opts));
}

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

// Datos reales de fidelización (se llenan desde la BD al cargar el ranking)
let frecuentesData = [];

// Cargar datos dinámicos de usuario (prioriza los datos reales del ranking de BD)
function cargarDatosUsuario(userId) {
    const userReal = frecuentesData.find(u => String(u.id_usuario) === String(userId));
    const user = userReal || mockData.usuarios[userId];
    if (!user) return;

    const nombre = userReal ? userReal.cliente : user.nombre;
    const email = userReal ? userReal.email : user.email;
    const dias = userReal ? (parseInt(userReal.dias_alquiler_mes, 10) || 0) : user.dias;

    const profileName = document.getElementById('profile-name');
    const profileEmail = document.getElementById('profile-email');
    const profileDays = document.getElementById('profile-days');

    if (profileName) profileName.innerText = nombre;
    if (profileEmail) profileEmail.innerText = email;
    if (profileDays) profileDays.innerText = dias;

    const progressPercent = Math.min(100, Math.round((dias / 20) * 100));
    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');
    const progressDesc = document.getElementById('progress-desc');
    const profileBadge = document.getElementById('profile-badge');

    if (progressBar) progressBar.style.width = `${progressPercent}%`;
    if (progressText) progressText.innerText = `${progressPercent}% Completado`;

    if (dias >= 20) {
        if (profileBadge) profileBadge.innerHTML = `<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">10% OFF Habilitado</span>`;
        if (progressDesc) progressDesc.innerText = "¡Felicidades! Has superado los 20 días de alquiler en el mes. Tu descuento del 10% se aplicará automáticamente en tu próxima factura.";
    } else {
        if (profileBadge) profileBadge.innerHTML = `<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-slate-700 text-slate-300 border border-subtle">Sin Descuento</span>`;
        if (progressDesc) progressDesc.innerText = `Te faltan ${20 - dias} días de alquiler este mes para desbloquear tu 10% de descuento.`;
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
    swalAlert({
        icon: 'info',
        title: 'Simulación',
        text: 'Executing CALL sp_conciliar_y_auditar();\n\nLos datos de la vista han sido re-sincronizados.'
    });
}

// ================= Monitoreo: rutas por usuario =================
let rutaLayer = null;
let usuariosMonitoreoCargados = false;

// Botón: muestra/oculta la lista de usuarios registrados
function togglePanelUsuariosMonitoreo() {
    const panel = document.getElementById('panel-usuarios-monitoreo');
    if (!panel) return;
    const visible = panel.style.display === 'block';
    panel.style.display = visible ? 'none' : 'block';
    if (!visible && !usuariosMonitoreoCargados) {
        cargarUsuariosMonitoreo();
    }
}

// Lista de todos los usuarios registrados en la BD
function cargarUsuariosMonitoreo() {
    const panel = document.getElementById('panel-usuarios-monitoreo');
    if (!panel) return;
    panel.innerHTML = '<span class="text-muted">Cargando usuarios…</span>';

    fetch(API_BASE + 'UsuarioController.php?action=listar')
        .then(res => res.json())
        .then(data => {
            if (!Array.isArray(data)) return;
            usuariosMonitoreoCargados = true;
            panel.innerHTML = data.map(u =>
                `<button type="button" class="mon-user-btn" data-id="${u.id_usuario}" onclick="verRutaUsuario(${u.id_usuario})">${esc(u.nombre + ' ' + u.apellido)}</button>`
            ).join('');
        })
        .catch(err => {
            panel.innerHTML = '<span class="text-danger">Error al cargar los usuarios.</span>';
            console.error('Error al cargar usuarios de monitoreo:', err);
        });
}

// Traza en el mapa la ruta de un usuario con duración y monto pagado
function verRutaUsuario(idUsuario) {
    const panel = document.getElementById('panel-ruta-usuario');
    if (panel) {
        panel.style.display = 'block';
        panel.innerHTML = '<span class="text-muted">Cargando recorridos…</span>';
    }

    fetch(API_BASE + 'AlquilerController.php?action=rutas_usuario&id_usuario=' + encodeURIComponent(idUsuario))
        .then(res => res.json())
        .then(data => {
            if (!data || !data.success || !Array.isArray(data.rutas) || data.rutas.length === 0) {
                if (panel) panel.innerHTML = '<span class="text-muted">Este usuario aún no tiene recorridos registrados.</span>';
                return;
            }

            const rutas = data.rutas.filter(r => r.destino_lat !== null && r.destino_lng !== null);
            const totalMin = rutas.reduce((acc, r) => acc + (parseInt(r.minutos_totales, 10) || 0), 0);
            const totalPagado = rutas.reduce((acc, r) => acc + parseFloat(r.total_pagado || 0), 0);
            const enCurso = data.rutas.filter(r => r.estado === 'en_curso').length;
            const cliente = data.rutas[0].cliente;

            if (panel) {
                panel.innerHTML = `
                    <div class="card" style="margin: 0;">
                        <h3 style="font-size: 15px; margin-bottom: 10px;">🚴 Ruta de ${esc(cliente)}</h3>
                        <p class="text-muted" style="font-size: 13px;">${rutas.length} recorridos finalizados${enCurso ? ' · ' + enCurso + ' en curso' : ''}</p>
                        <p style="margin-top: 8px;">⏱ Tiempo total: <strong>${formatDuracion(totalMin)}</strong></p>
                        <p>💰 Total cancelado: <strong>$${totalPagado.toFixed(2)}</strong></p>
                        <p class="text-muted" style="font-size: 12px;">Haz clic sobre cada línea del mapa para ver el detalle del recorrido.</p>
                    </div>`;
            }

            dibujarRutasMapa(rutas);

            // [T-05-08] Actualiza el contenedor de batería con la bici de este cliente en tiempo real
            if (typeof monitorearUsuario === 'function') {
                monitorearUsuario(idUsuario);
            }
        })
        .catch(err => {
            if (panel) panel.innerHTML = '<span class="text-danger">Error al cargar las rutas.</span>';
            console.error('Error al cargar rutas:', err);
        });
}

// Dibuja los recorridos (polilíneas) del usuario sobre el mapa Leaflet
function dibujarRutasMapa(rutas) {
    if (typeof L === 'undefined' || typeof map === 'undefined' || map === null) {
        if (typeof initMap === 'function') initMap();
    }
    if (typeof L === 'undefined' || typeof map === 'undefined' || map === null || !rutas.length) return;

    try {
        if (!rutaLayer) rutaLayer = L.layerGroup().addTo(map);
        rutaLayer.clearLayers();

        const bounds = [];
        const colores = ['#22c55e', '#3b82f6', '#f59e0b', '#ec4899', '#8b5cf6', '#14b8a6', '#f43f5e'];

        rutas.forEach((r, i) => {
            const inicio = [parseFloat(r.origen_lat), parseFloat(r.origen_lng)];
            const fin = [parseFloat(r.destino_lat), parseFloat(r.destino_lng)];
            bounds.push(inicio, fin);
            const popup =
                `<strong>#${r.id_alquiler}</strong> • ${esc(r.origen_nombre)} → ${esc(r.destino_nombre)}<br>` +
                `📅 ${r.fecha_inicio}<br>` +
                `⏱ Duración: ${formatDuracion(r.minutos_totales)}<br>` +
                `💰 Pagado: $${parseFloat(r.total_pagado).toFixed(2)}` +
                (r.costo_total != null ? ` (costo $${parseFloat(r.costo_total).toFixed(2)})` : '');

            L.polyline([inicio, fin], { color: colores[i % colores.length], weight: 3, opacity: 0.85 })
                .bindPopup(popup)
                .addTo(rutaLayer);
        });

        map.invalidateSize();
        map.fitBounds(bounds, { padding: [40, 40] });
    } catch (e) {
        console.error('No se pudo dibujar la ruta en el mapa:', e);
    }
}

// Formatea minutos en "Xh Ymin" o "Z min"
function formatDuracion(minutos) {
    const m = parseInt(minutos, 10) || 0;
    const h = Math.floor(m / 60);
    const rem = m % 60;
    return h > 0 ? `${h}h ${rem} min` : `${rem} min`;
}

// Auditoría SQL: conciliación financiera real de los usuarios que ya pagaron su servicio
function cargarConciliacionJS() {
    const tbody = document.getElementById('tabla-conciliacion');
    if (!tbody) return;

    fetch(API_BASE + 'PagoController.php?action=conciliacion')
        .then(res => res.json())
        .then(data => {
            if (!data || !Array.isArray(data.rows)) return;

            tbody.innerHTML = data.rows.map(item => {
                const estado = item.estado_conciliacion || '';
                let badgeClass = 'bg-slate-500/10 text-slate-300 border-slate-500/30';
                if (estado.includes('Conciliado')) badgeClass = 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30';
                else if (estado.includes('Parcial')) badgeClass = 'bg-amber-500/10 text-amber-400 border-amber-500/30';
                else if (estado.includes('Sobrepago')) badgeClass = 'bg-rose-500/10 text-rose-400 border-rose-500/30';

                return `
                    <tr>
                        <td class="p-4 font-semibold">#${esc(item.id_alquiler)}</td>
                        <td class="p-4">${esc(item.cliente)}</td>
                        <td class="p-4">$${parseFloat(item.monto_alquiler).toFixed(2)}</td>
                        <td class="p-4">$${parseFloat(item.monto_pagado).toFixed(2)}</td>
                        <td class="p-4"><span class="px-2.5 py-1 text-xs font-bold rounded-full border ${badgeClass}">${esc(estado)}</span></td>
                    </tr>
                `;
            }).join('');

            // Contenedores de Auditoría Financiera (tarjetas KPI) — datos completos actuales
            const r = data.resumen;
            if (r) {
                const setText = (id, txt) => {
                    const el = document.getElementById(id);
                    if (el) el.innerText = txt;
                };
                const fmt = n => '$' + Number(n).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                setText('conciliado-total', fmt(r.total_conciliado));
                setText('conciliado-sub', `${r.conciliados} registro(s) conciliado(s)`);
                setText('descuadres-monto', fmt(r.monto_descuadres));
                setText('descuadres-sub', `${r.descuadres} registro(s) pendiente(s)`);
                setText('huerfanas-num', r.huerfanas + (r.huerfanas === 1 ? ' Alerta' : ' Alertas'));
            }
        })
        .catch(err => console.error('Error al cargar conciliación financiera:', err));
}

// Refresco automático de Auditoría Financiera y Conciliación SQL:
// cada 8 segundos, mientras la vista de auditoría esté visible, actualiza los contenedores
// con los datos completos actuales de la tabla de conciliación financiera de pagos.
setInterval(() => {
    const view = document.getElementById('view-auditoria');
    if (view && view.style.display !== 'none' && typeof cargarConciliacionJS === 'function') {
        cargarConciliacionJS();
    }
}, 8000);

// Inicialización general
document.addEventListener('DOMContentLoaded', () => {
    cargarTemaPreferido();

    // El portal del usuario final usa su propia inicialización (js/usuario.js).
    // Este bloque solo aplica para el panel administrativo.
    const esPortalUsuario = !!document.getElementById('view-viajes');
    if (esPortalUsuario) return;

    cargarDatosUsuario(1);
    renderConciliacion();
    initMap();
    cargarFrecuentesJS();
});

// Llena el desplegable de usuarios del portal eco con los registrados en la BD
function llenarSelectUsuarios() {
    const sel = document.getElementById('user-select');
    if (!sel) return;

    sel.innerHTML = frecuentesData.map(u => {
        const dias = parseInt(u.dias_alquiler_mes, 10) || 0;
        const desc = parseFloat(u.descuento_fidelidad) || 0;
        const etiqueta = desc > 0 ? `${dias} días · ${desc}%` : `${dias} días`;
        return `<option value="${u.id_usuario}">${esc(u.cliente)} (${etiqueta})</option>`;
    }).join('');

    if (sel.value) {
        cargarDatosUsuario(sel.value);
    }
}

// Ranking de fidelización: usuarios con más alquileres del mes (descendente, resalta >20)
function cargarFrecuentesJS() {
    const tbody = document.getElementById('tabla-frecuentes');
    if (!tbody) return;

    fetch(API_BASE + 'UsuarioController.php?action=frecuentes')
        .then(res => res.json())
        .then(data => {
            if (!Array.isArray(data)) return;
            frecuentesData = data;
            llenarSelectUsuarios();
            tbody.innerHTML = data.map((u, i) => {
                const dias = parseInt(u.dias_alquiler_mes, 10) || 0;
                const top = dias > 20;
                const desc = parseFloat(u.descuento_fidelidad) || 0;

                const cellStyle = '';
                const badgeEstado = top
                    ? '<span class="badge-success">Elegible 10% OFF</span>'
                    : '<span style="background: rgba(148,163,184,0.12); color: #94a3b8; border: 1px solid rgba(148,163,184,0.3); padding: 3px 8px; border-radius: 999px; font-size: 11px; font-weight: 700;">' + esc(u.estado_fidelidad || ('Faltan ' + (21 - dias) + ' días')) + '</span>';

                return `<tr${top ? ' style="background: rgba(34,197,94,0.12);"' : cellStyle}>
                    <td>${i + 1}</td>
                    <td><strong>${esc(u.cliente)}</strong></td>
                    <td>${esc(u.email)}</td>
                    <td><strong>${dias}</strong></td>
                    <td>${esc(desc > 0 ? desc + '%' : '0%')}</td>
                    <td>${badgeEstado}</td>
                </tr>`;
            }).join('');
        })
        .catch(err => console.error('Error al cargar ranking de fidelización:', err));
}

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

// Render de las transacciones en la tabla (devuelve cuántas filas se pintaron)
function renderTablaTransacciones(data) {
    const tbody = document.getElementById('tabla-transacciones-base');
    if (!tbody) return 0;

    tbody.innerHTML = '';
    if (!Array.isArray(data)) return 0;

    data.forEach(tx => {
        const estado = String(tx.estado || '').toUpperCase();
        let badgeClass = 'badge-success';
        let accion = '';
        if (estado === 'PENDIENTE') {
            badgeClass = 'badge-warning';
            accion = `
                <button type="button" class="btn-primary" style="padding: 4px 8px; font-size: 11px;" onclick="aprobarPagoJS(${esc(tx.id_transaccion)})">✓ Aprobar</button>
                <button type="button" class="btn-danger" style="padding: 4px 8px; font-size: 11px;" onclick="rechazarPagoJS(${esc(tx.id_transaccion)})">✕ Rechazar</button>`;
        } else if (estado === 'RECHAZADO') {
            badgeClass = 'badge-danger';
        }

        tbody.innerHTML += `
            <tr>
                <td>TX-${esc(tx.id_transaccion)}</td>
                <td>ALQ-${esc(tx.id_alquiler)}</td>
                <td><code>${esc(tx.referencia_externa)}</code></td>
                <td>${esc(tx.metodo || '—')}</td>
                <td>$${esc(parseFloat(tx.monto ?? tx.valor ?? 0).toLocaleString('en-US'))}</td>
                <td><span class="${badgeClass}">${esc(tx.estado)}</span></td>
                <td>${esc(tx.fecha_pago)}</td>
                <td>${accion || '—'}</td>
            </tr>
        `;
    });

    return data.length;
}

// Cargar transacciones registradas en la tabla al abrir la pestaña
function cargarTransaccionesJS() {
    fetch(API_BASE + 'PagoController.php?action=listar_transacciones')
        .then(res => res.json())
        .then(data => renderTablaTransacciones(data))
        .catch(err => console.error('Error al cargar transacciones:', err));
}

// Actualizar la tabla de transacciones con confirmación y feedback modal (SweetAlert2)
async function actualizarTablaTransaccionesJS() {
    const confirmacion = await swalAlert({
        title: 'Actualizar tabla de transacciones',
        html: 'Se consultará la base de datos y se sincronizarán las transacciones registradas con la tabla.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, actualizar',
        cancelButtonText: 'Cancelar'
    });
    if (!confirmacion.isConfirmed) return;

    swalAlert({
        title: 'Actualizando…',
        html: 'Consultando las transacciones en la base de datos.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const res = await fetch(API_BASE + 'PagoController.php?action=listar_transacciones');
        const data = await res.json();
        const filas = renderTablaTransacciones(data);
        Swal.close();
        swalAlert({
            icon: 'success',
            title: 'Tabla actualizada',
            html: `Se sincronizaron <b>${filas}</b> transacción(es) desde la base de datos.`
        });
    } catch (err) {
        console.error('Error actualizando tabla:', err);
        Swal.close();
        swalAlert({
            icon: 'error',
            title: 'No se pudo actualizar',
            text: 'Ocurrió un error al consultar la base de datos. Inténtalo de nuevo.'
        });
    }
}

// Interfaz de registro de pago y carga de comprobante [T-06-03, T-06-04, T-06-08]
function registrarPagoJS() {
    const form = document.getElementById('form-procesar-pago');
    const btn = form.querySelector('button[type="submit"]');
    const formData = new FormData();
    
    formData.append('id_alquiler', document.getElementById('pago_alquiler_id').value);
    formData.append('monto', document.getElementById('pago_monto').value);
    formData.append('metodo', document.getElementById('pago_metodo')?.value || 'TARJETA');
    formData.append('referencia_externa', 'REF_' + Date.now()); // Genera la ref de la pasarela [T-07-05]
    
    const fileInput = document.getElementById('pago_evidencia');
    if (fileInput.files[0]) {
        formData.append('evidencia', fileInput.files[0]); // [T-06-04]
    }

    if (btn) btn.disabled = true; // Evita doble envío durante el procesamiento

    fetch(API_BASE + 'PagoController.php?action=registrar_pago', {
        method: 'POST',
        headers: csrfHeaders(),
        body: formData
    })
    .then(res => res.json().catch(() => Promise.reject(new Error('El servidor no respondió correctamente. Inténtalo de nuevo.'))))
    .then(res => {
        swalAlert({
            icon: res.status === 'success' ? 'success' : 'error',
            title: res.status === 'success' ? 'Pago registrado' : 'No se pudo procesar',
            text: res.message
        });
        if (res.status === 'success') {
            form.reset();
            cargarTransaccionesJS(); // Actualizar la tabla de transacciones de inmediato
            cargarConciliacionJS(); // Auditoría financiera y conciliación SQL al día con el nuevo pago
        }
    })
    .catch(err => {
        console.error('Error procesando pago:', err);
        if (err && err.message) {
            swalAlert({ icon: 'error', title: 'Error', text: err.message });
        }
    })
    .finally(() => {
        if (btn) btn.disabled = false;
    });
}

// Aprobar un pago pendiente (transferencia/QR) — solo admin
async function aprobarPagoJS(idTransaccion) {
    const confirmacion = await swalAlert({
        title: 'Aprobar pago',
        text: '¿Confirmas que el comprobante es válido? Se conciliará contra el alquiler.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, aprobar',
        cancelButtonText: 'Cancelar'
    });
    if (!confirmacion.isConfirmed) return;

    fetch(API_BASE + 'PagoController.php?action=aprobar_pago', {
        method: 'POST',
        headers: Object.assign({ 'Content-Type': 'application/json' }, csrfHeaders()),
        body: JSON.stringify({ id_transaccion: idTransaccion })
    })
    .then(res => res.json())
    .then(res => {
        swalAlert({ icon: res.status === 'success' ? 'success' : 'error',
            title: res.status === 'success' ? 'Pago aprobado' : 'No se pudo aprobar',
            text: res.message });
        cargarTransaccionesJS();
        cargarConciliacionJS();
    })
    .catch(err => console.error('Error aprobando pago:', err));
}

// Rechazar un pago pendiente (transferencia/QR) — solo admin
async function rechazarPagoJS(idTransaccion) {
    const confirmacion = await swalAlert({
        title: 'Rechazar pago',
        text: 'El pago quedará RECHAZADO y no se conciliará. ¿Continuar?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, rechazar',
        cancelButtonText: 'Cancelar'
    });
    if (!confirmacion.isConfirmed) return;

    fetch(API_BASE + 'PagoController.php?action=rechazar_pago', {
        method: 'POST',
        headers: Object.assign({ 'Content-Type': 'application/json' }, csrfHeaders()),
        body: JSON.stringify({ id_transaccion: idTransaccion })
    })
    .then(res => res.json())
    .then(res => {
        swalAlert({ icon: res.status === 'success' ? 'success' : 'error',
            title: res.status === 'success' ? 'Pago rechazado' : 'No se pudo rechazar',
            text: res.message });
        cargarTransaccionesJS();
        cargarConciliacionJS();
    })
    .catch(err => console.error('Error rechazando pago:', err));
}

// Revocación lógica del token [T-06-07]
async function eliminarTokenJS(tokenId) {
    const confirmacion = await swalAlert({
        title: 'Revocar token',
        text: '¿Desea revocar este token de pasarela?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, revocar',
        cancelButtonText: 'Cancelar'
    });
    if (!confirmacion.isConfirmed) return;

    fetch(API_BASE + 'PagoController.php?action=revocar_token', {
        method: 'POST',
        headers: Object.assign({ 'Content-Type': 'application/json' }, csrfHeaders()),
        body: JSON.stringify({ id_token: tokenId })
    })
    .then(res => res.json())
    .then(res => {
        swalAlert({
            icon: res.status === 'success' ? 'success' : 'error',
            title: res.status === 'success' ? 'Token revocado' : 'No se pudo revocar',
            text: res.message
        });
    })
    .catch(err => console.error('Error al revocar token:', err));
}

// Inicio de viaje [T-05-03] desde el panel de gestión de viajes
function iniciarViajeJS() {
    const idUsuario = document.getElementById('id_usuario').value;
    const idBicicleta = document.getElementById('id_bicicleta').value;
    const estacionOrigen = document.getElementById('estacion_origen').value;

    if (!idUsuario || !idBicicleta || !estacionOrigen) {
        swalAlert({ icon: 'warning', title: 'Faltan datos', text: 'Selecciona un usuario, una bicicleta y la estación de origen.' });
        return;
    }

    const payload = {
        id_usuario: idUsuario,
        id_bicicleta: idBicicleta,
        estacion_origen: estacionOrigen
    };

    fetch(API_BASE + 'AlquilerController.php?action=iniciar', {
        method: 'POST',
        headers: Object.assign({ 'Content-Type': 'application/json' }, csrfHeaders()),
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            swalAlert({ icon: 'success', title: 'Viaje iniciado', text: res.message });
            document.getElementById('form-iniciar-viaje').reset();
            cargarDatosGestionViajes();
        } else {
            swalAlert({ icon: 'error', title: 'No se pudo iniciar', text: res.message });
        }
    })
    .catch(err => console.error('Error iniciando viaje:', err));
}

// Cierre de viaje y cálculo de costo [T-05-05, T-05-06, T-05-07]
function cerrarViajeJS() {
    const idAlquiler = document.getElementById('id_alquiler').value;
    const estacionDestino = document.getElementById('estacion_destino').value;

    if (!idAlquiler || !estacionDestino) {
        swalAlert({ icon: 'warning', title: 'Faltan datos', text: 'Selecciona el viaje activo y la estación de destino.' });
        return;
    }

    const payload = {
        id_alquiler: idAlquiler,
        estacion_destino: estacionDestino
    };

    fetch(API_BASE + 'AlquilerController.php?action=cerrar', {
        method: 'POST',
        headers: Object.assign({ 'Content-Type': 'application/json' }, csrfHeaders()),
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            const datos = res.datos || {};
            swalAlert({
                icon: 'success',
                title: 'Viaje finalizado',
                text: res.message + `\nMinutos: ${datos.minutos}\nCosto total: $${datos.costo_total}`
            });
            document.getElementById('form-cerrar-viaje').reset();
            cargarDatosGestionViajes();
        } else {
            swalAlert({ icon: 'error', title: 'No se pudo finalizar', text: res.message });
        }
    })
    .catch(err => console.error('Error cerrando viaje:', err));
}

// Catálogo real de la BD para la gestión de viajes (usuarios, bicicletas, estaciones, viajes activos)
function cargarDatosGestionViajes() {
    if (!document.getElementById('form-iniciar-viaje')) return;

    fetch(API_BASE + 'AlquilerController.php?action=datos_iniciales')
        .then(res => res.json())
        .then(data => {
            if (!data || data.status === 'error') {
                console.error('Error al cargar el catálogo de viajes:', data && data.message);
                return;
            }

            llenarSelect('id_usuario', data.usuarios, 'id_usuario', 'email');
            llenarSelect('estacion_origen', data.estaciones, 'id_estacion', 'nombre');
            llenarSelect('estacion_destino', data.estaciones, 'id_estacion', 'nombre');
            llenarSelectViajes('id_alquiler', data.viajes);

            renderBicicletas(data.bicicletas);
        })
        .catch(err => console.error('Error al cargar gestión de viajes:', err));
}

function llenarSelect(selectId, items, valueKey, labelKey) {
    const sel = document.getElementById(selectId);
    if (!sel) return;

    sel.innerHTML = '<option value="">Seleccionar…</option>' + items.map(i => {
        let label = i[labelKey];
        if (selectId === 'id_usuario') {
            label = `${i.nombre} ${i.apellido}`;
        }
        return `<option value="${i[valueKey]}">${esc(label)}</option>`;
    }).join('');
}

function llenarSelectViajes(selectId, viajes) {
    const sel = document.getElementById(selectId);
    if (!sel) return;
    sel.innerHTML = '<option value="">Sin viajes en curso…</option>' + viajes.map(v => {
        return `<option value="${v.id_alquiler}">#${v.id_alquiler} · ${esc(v.cliente)} · Bici ${esc(v.num_serie)} · ${v.fecha_inicio}</option>`;
    }).join('');
}

// Render de tarjetas de bicicleta según disponibilidad
function renderBicicletas(bicicletas) {
    const cont = document.getElementById('bici-cards');
    const input = document.getElementById('id_bicicleta');
    if (!cont || !input) return;

    cont.innerHTML = bicicletas.map(b => {
        const disponible = b.estado === 'disponible';
        const ic = disponible ? '🚲' : (b.estado === 'alquilada' ? '🔒' : '🔧');
        return `<button type="button" data-id="${b.id_bicicleta}" class="bici-card ${disponible ? 'disponible' : 'no-disponible'}" ${disponible ? '' : 'disabled'}>
            <span class="bici-ico">${ic}</span>
            <strong>${esc(b.modelo)}</strong>
            <small>#${b.id_bicicleta} · ${b.nivel_bateria}%</small>
            <small>${esc(b.estacion_nombre)}</small>
        </button>`;
    }).join('');

    cont.querySelectorAll('.bici-card.disponible').forEach(card => {
        card.addEventListener('click', () => seleccionarBicicleta(card.dataset.id, card));
    });

    input.value = '';
    cont.querySelectorAll('.bici-card').forEach(c => c.classList.remove('selected'));
}

// Seleccionar bicicleta en las tarjetas (guarda el id en el input hidden)
function seleccionarBicicleta(id, card) {
    const cont = document.getElementById('bici-cards');
    const input = document.getElementById('id_bicicleta');
    if (!cont || !input) return;

    cont.querySelectorAll('.bici-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    input.value = id;
}

// Registrar un usuario desde la gestión de viajes (pide solo los datos del prototipo)
function registrarUsuarioJS() {
    swalAlert({
        title: 'Registrar usuario',
        html: `
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 8px;">
                <input id="swal-nombre" class="swal2-input" placeholder="Nombre" autocomplete="off">
                <input id="swal-apellido" class="swal2-input" placeholder="Apellidos" autocomplete="off">
            </div>
            <input id="swal-email" class="swal2-input" placeholder="Email" type="email" autocomplete="off">
            <input id="swal-telefono" class="swal2-input" placeholder="Teléfono (opcional)" autocomplete="off">
        `,
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        focusConfirm: false,
        width: 440,
        preConfirm: () => {
            const nombre = document.getElementById('swal-nombre').value.trim();
            const apellido = document.getElementById('swal-apellido').value.trim();
            const email = document.getElementById('swal-email').value.trim();
            const telefono = document.getElementById('swal-telefono').value.trim();

            if (!nombre || !apellido) {
                Swal.showValidationMessage('El nombre y los apellidos son obligatorios.');
                return false;
            }
            const emailValido = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            if (!emailValido) {
                Swal.showValidationMessage('Introduce un email válido.');
                return false;
            }

            return fetch(API_BASE + 'UsuarioController.php?action=registrar', {
                method: 'POST',
                headers: Object.assign({ 'Content-Type': 'application/json' }, csrfHeaders()),
                body: JSON.stringify({ nombre, apellido, email, telefono })
            })
            .then(res => res.json().catch(() => Promise.reject(new Error('El servidor no respondió correctamente.'))))
            .then(res => {
                if (!res || res.status !== 'success') throw new Error(res && res.message ? res.message : 'No se pudo registrar.');
                return res;
            })
            .catch(error => {
                Swal.showValidationMessage(error.message);
                return false;
            });
        }
    }).then(result => {
        if (result.isConfirmed && result.value) {
            const res = result.value;
            swalAlert({
                icon: 'success',
                title: 'Usuario registrado',
                html: `${res.message}<br>Contraseña temporal: <b>${esc(res.password_temp)}</b>`
            });
            cargarDatosGestionViajes();
        }
    });
}

