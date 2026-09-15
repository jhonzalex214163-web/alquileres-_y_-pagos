// ================= Portal del Usuario Final =================
// lógica del perfil: Mis Viajes (desbloqueo/cierre), Rutas y Estaciones (mapa),
// Mis Recibos (imprimibles/PDF) y Eco-Puntos.

let portalData = { catalogo: null, tarifa: 0.50 };
let costoInterval = null;
let rutasLayer = null;
let estacionesLayer = null;

// Cambio de pestañas en el portal del usuario
function switchTab(viewId) {
    document.querySelectorAll('main > section').forEach(sec => sec.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));

    const targetSec = document.getElementById(viewId);
    if (targetSec) targetSec.style.display = 'block';

    const targetBtn = document.getElementById('tab-' + viewId.replace('view-', ''));
    if (targetBtn) targetBtn.classList.add('active');

    if (viewId === 'view-viajes') cargarCatalogoUsuario();
    if (viewId === 'view-rutas') { cargarRutasMapa(); initMapaEstaciones(); }
    if (viewId === 'view-recibos') cargarRecibosUsuario();
    if (viewId === 'view-ecopuntos') cargarEcoPuntosUsuario();
}

// ================= Mis Viajes =================
function cargarCatalogoUsuario() {
    const bloqueIniciar = document.getElementById('bloque-iniciar-viaje');
    if (!bloqueIniciar) return;

    fetch(API_BASE + 'AlquilerController.php?action=catalogo_usuario')
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                swalAlert({ icon: 'error', title: 'Error', text: data.message || 'No se pudo cargar el catálogo.' });
                return;
            }
            portalData.catalogo = data.catalogo;
            portalData.tarifa = parseFloat(data.catalogo.tarifa) || 0.50;

            // Estaciones (retiro y devolución)
            llenarSelectEstaciones('estacion_origen', data.catalogo.estaciones);
            llenarSelectEstaciones('estacion_destino', data.catalogo.estaciones);

            // Tarjetas de bicicleta
            renderBicicletas(data.catalogo.bicicletas);

            // ¿Hay viaje activo del usuario?
            const activo = (data.catalogo.viajes || [])[0];
            if (activo) {
                mostrarViajeActivo(activo);
            } else {
                ocultarViajeActivo();
            }
        })
        .catch(err => console.error('Error al cargar el catálogo del usuario:', err));
}

function llenarSelectEstaciones(selectId, estaciones) {
    const sel = document.getElementById(selectId);
    if (!sel) return;
    sel.innerHTML = '<option value="">Seleccionar estación…</option>' + estaciones.map(e =>
        `<option value="${e.id_estacion}">${esc(e.codigo)} · ${esc(e.nombre)}</option>`
    ).join('');
}

// Si hay un viaje en curso: mostrar bloque de cierre y ocultar el de inicio
function mostrarViajeActivo(viaje) {
    const bloqueIniciar = document.getElementById('bloque-iniciar-viaje');
    const bloqueActivo = document.getElementById('bloque-viaje-activo');
    if (bloqueIniciar) bloqueIniciar.style.display = 'none';
    if (bloqueActivo) bloqueActivo.style.display = 'block';

    const bici = document.getElementById('viaje-activo-bici');
    const origen = document.getElementById('viaje-activo-origen');
    const inicio = document.getElementById('viaje-activo-inicio');
    if (bici) bici.innerText = `${viaje.num_serie || '—'}`;
    if (origen) origen.innerText = viaje.estacion_nombre || '—';
    if (inicio) inicio.innerText = viaje.fecha_inicio || '—';

    // Estimador de costo por minuto en vivo
    const inicioMs = new Date(viaje.fecha_inicio.replace(' ', 'T')).getTime();
    clearInterval(costoInterval);
    const actualizarCosto = () => {
        const el = document.getElementById('costo-actual');
        if (!el) return;
        const mins = Math.max(0, (Date.now() - inicioMs) / 60000);
        el.innerText = '$' + (mins * portalData.tarifa).toFixed(2);
    };
    actualizarCosto();
    costoInterval = setInterval(actualizarCosto, 30000);
}

function ocultarViajeActivo() {
    const bloqueIniciar = document.getElementById('bloque-iniciar-viaje');
    const bloqueActivo = document.getElementById('bloque-viaje-activo');
    if (bloqueIniciar) bloqueIniciar.style.display = 'block';
    if (bloqueActivo) bloqueActivo.style.display = 'none';
    clearInterval(costoInterval);
}

// Iniciar viaje (el id de usuario sale del backend por sesión, no del request)
function iniciarViajeUsuarioJS() {
    const idBicicleta = document.getElementById('id_bicicleta').value;
    const estacionOrigen = document.getElementById('estacion_origen').value;

    if (!idBicicleta || !estacionOrigen) {
        swalAlert({ icon: 'warning', title: 'Faltan datos', text: 'Elige una bicicleta disponible y la estación de retiro.' });
        return;
    }

    fetch(API_BASE + 'AlquilerController.php?action=iniciar_usuario', {
        method: 'POST',
        headers: Object.assign({ 'Content-Type': 'application/json' }, csrfHeaders()),
        body: JSON.stringify({ id_bicicleta: idBicicleta, estacion_origen: estacionOrigen })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            swalAlert({ icon: 'success', title: '¡Viaje iniciado!', text: res.message });
            document.getElementById('form-iniciar-viaje')?.reset();
            document.getElementById('id_bicicleta').value = '';
            document.querySelectorAll('.bici-card').forEach(c => c.classList.remove('selected'));
            cargarCatalogoUsuario();
        } else {
            swalAlert({ icon: 'error', title: 'No se pudo iniciar', text: res.message });
        }
    })
    .catch(err => console.error('Error iniciando viaje:', err));
}

// Cerrar viaje (valida en backend que el alquiler pertenezca al usuario en sesión)
function cerrarViajeUsuarioJS() {
    const idAlquiler = (portalData.catalogo?.viajes || [])[0]?.id_alquiler;
    const estacionDestino = document.getElementById('estacion_destino').value;

    if (!idAlquiler || !estacionDestino) {
        swalAlert({ icon: 'warning', title: 'Faltan datos', text: 'Selecciona la estación de devolución.' });
        return;
    }

    fetch(API_BASE + 'AlquilerController.php?action=cerrar_usuario', {
        method: 'POST',
        headers: Object.assign({ 'Content-Type': 'application/json' }, csrfHeaders()),
        body: JSON.stringify({ id_alquiler: idAlquiler, estacion_destino: estacionDestino })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            const d = res.datos || {};
            swalAlert({
                icon: 'success',
                title: '¡Viaje finalizado!',
                text: `${res.message}\nMinutos: ${d.minutos}\nCosto total: $${d.costo_total}`
            });
            clearInterval(costoInterval);
            document.getElementById('form-cerrar-viaje')?.reset();
            cargarCatalogoUsuario();
        } else {
            swalAlert({ icon: 'error', title: 'No se pudo finalizar', text: res.message });
        }
    })
    .catch(err => console.error('Error cerrando viaje:', err));
}

// ================= Rutas y Estaciones =================
// Rutas turísticas con Leaflet + OSM, centro en Jardín
function cargarRutasMapa() {
    const cont = document.getElementById('lista-rutas');
    if (!cont) return;

    fetch(API_BASE + 'RutasController.php?action=listar')
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                cont.innerHTML = '<p class="text-muted">No se pudieron cargar las rutas.</p>';
                return;
            }
            const rutas = data.rutas || [];
            if (!rutas.length) {
                cont.innerHTML = '<p class="text-muted">Aún no hay rutas turísticas publicadas.</p>';
                return;
            }

            cont.innerHTML = rutas.map(r => {
                const cls = r.dificultad === 'facil' ? 'badge-success'
                    : r.dificultad === 'media' ? 'badge-warning'
                    : 'badge-danger';
                return `
                    <div class="card" style="margin: 0;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <strong style="font-size:15px;">${esc(r.nombre)}</strong>
                            <span class="${cls}" style="font-size:11px; text-transform:capitalize;">${esc(r.dificultad)}</span>
                        </div>
                        <p class="text-muted" style="font-size:12px; margin-top:6px;">${esc(r.descripcion)}</p>
                        <p style="font-size:13px; margin-top:8px;">
                            📏 ${r.distancia_km != null ? parseFloat(r.distancia_km).toFixed(1) + ' km' : '—'}
                            &nbsp;·&nbsp; ⏱ ${esc(r.duracion_est || '—')}
                        </p>
                    </div>`;
            }).join('');

            dibujarRutasMapa(rutas);
        })
        .catch(err => console.error('Error al cargar rutas turísticas:', err));
}

// Mapa base + estaciones (Leaflet) — se inicializa una sola vez
function initMapaEstaciones() {
    if (map) {
        setTimeout(() => map.invalidateSize(), 200);
        return;
    }
    const mapEl = document.getElementById('map');
    if (!mapEl || typeof L === 'undefined') return;

    map = L.map('map').setView([5.5986, -75.8194], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    estacionesLayer = L.layerGroup().addTo(map);
    rutasLayer = L.layerGroup().addTo(map);

    fetch(API_BASE + 'AlquilerController.php?action=estaciones_mapa')
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;
            (data.estaciones || []).forEach(est => {
                if (est.lat == null || est.lng == null) return;
                const color = est.estado === 'activa' ? '#22c55e' : '#f59e0b';
                const icon = L.divIcon({
                    html: `<div style="width:26px;height:26px;border-radius:50%;background:${color};color:#062318;font-weight:900;display:flex;align-items:center;justify-content:center;border:3px solid #e2f1e8;box-shadow:0 2px 8px rgba(0,0,0,.4);">⚡</div>`,
                    iconSize: [26, 26]
                });
                L.marker([parseFloat(est.lat), parseFloat(est.lng)], { icon })
                    .addTo(estacionesLayer)
                    .bindPopup(`<b>${esc(est.codigo)}</b><br>${esc(est.nombre)}<br>Energía: ${esc(est.energia_disp ?? '—')}% · Capacidad: ${esc(est.capacidad)}`);
            });
        })
        .catch(err => console.error('Error al cargar estaciones del mapa:', err));
}

// Dibuja las rutas turísticas como polilíneas con punto de inicio
function dibujarRutasMapa(rutas) {
    if (typeof L === 'undefined' || !map || !rutasLayer || !rutas.length) return;
    rutasLayer.clearLayers();

    const bounds = [];
    const colores = ['#22c55e', '#3b82f6', '#f59e0b', '#ec4899', '#8b5cf6', '#14b8a6', '#f43f5e'];

    rutas.forEach((r, i) => {
        const pt = [parseFloat(r.lat), parseFloat(r.lng)];
        bounds.push(pt);
        // Núcleo de la ruta en Jardín: centro de la plaza como referencia visual
        const pt2 = [parseFloat(r.lat) + 0.0015, parseFloat(r.lng) - 0.002];
        bounds.push(pt2);

        L.circleMarker(pt, { radius: 8, color: '#fff', weight: 2, fillColor: colores[i % colores.length], fillOpacity: 1 })
            .addTo(rutasLayer)
            .bindPopup(`<strong>${esc(r.nombre)}</strong><br>${esc(r.descripcion)}<br>📍 ${esc(r.dificultad)} · ${r.distancia_km != null ? parseFloat(r.distancia_km).toFixed(1) + ' km' : '—'} · ${esc(r.duracion_est || '—')}`);

        L.polyline([pt, pt2], { color: colores[i % colores.length], weight: 3, opacity: 0.6, dashArray: '6 6' })
            .addTo(rutasLayer);
    });

    if (bounds.length) {
        map.fitBounds(bounds, { maxZoom: 16, padding: [30, 30] });
    }
}

// ================= Mis Recibos =================
function cargarRecibosUsuario() {
    const tbody = document.getElementById('tabla-recibos');
    if (!tbody) return;

    fetch(API_BASE + 'AlquilerController.php?action=mis_recibos')
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; color:#94a3b8;">No se pudieron cargar tus recibos.</td></tr>';
                return;
            }
            const recibos = data.recibos || [];
            if (!recibos.length) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; color:#94a3b8;">Aún no tienes viajes registrados.</td></tr>';
                return;
            }

            tbody.innerHTML = recibos.map(r => {
                const badge = r.estado === 'finalizado'
                    ? '<span class="badge-success">Finalizado</span>'
                    : '<span style="background:rgba(59,130,246,.14); color:#60a5fa; border:1px solid rgba(59,130,246,.4); padding:3px 8px; border-radius:999px; font-size:11px; font-weight:700;">En curso</span>';
                const boton = r.estado === 'finalizado'
                    ? `<a href="views/recibo.php?id_alquiler=${encodeURIComponent(r.id_alquiler)}" class="btn-secondary" style="text-decoration:none; padding:5px 10px; font-size:12px;" target="_blank">🖨️ Recibo</a>`
                    : '—';
                return `
                    <tr>
                        <td>#${esc(r.id_alquiler)}</td>
                        <td>${esc(r.fecha_inicio)}</td>
                        <td>${esc(r.bici_modelo)} (${esc(r.num_serie)})</td>
                        <td>${esc(r.estacion_origen)} → ${esc(r.estacion_destino)}</td>
                        <td>${formatDuracion(r.minutos_totales)}</td>
                        <td>$${(parseFloat(r.costo_total) || 0).toFixed(2)}</td>
                        <td>${badge}</td>
                        <td>${boton}</td>
                    </tr>`;
            }).join('');
        })
        .catch(err => console.error('Error al cargar recibos:', err));
}

// ================= Eco-Puntos =================
function cargarEcoPuntosUsuario() {
    const miEmail = (window.USUARIO_SESION || {}).email || '';

    fetch(API_BASE + 'UsuarioController.php?action=frecuentes')
        .then(res => res.json())
        .then(data => {
            if (!Array.isArray(data) || !data.length) return;
            // Buscar al usuario logueado por email en el ranking
            const miInfo = miEmail
                ? data.find(u => String(u.email).toLowerCase() === miEmail.toLowerCase()) || data[0]
                : data[0];
            if (!miInfo) return;

            const dias = parseInt(miInfo.dias_alquiler_mes || miInfo.dias || 0, 10) || 0;
            const pct = Math.min(100, Math.round((dias / 20) * 100));

            const elDias = document.getElementById('ecopuntos-dias');
            const elPct = document.getElementById('ecopuntos-progreso');
            const elBarra = document.getElementById('ecopuntos-barra');
            const elDesc = document.getElementById('ecopuntos-desc');
            const elBadge = document.getElementById('ecopuntos-badge');

            if (elDias) elDias.innerText = dias;
            if (elPct) elPct.innerText = `${pct}% Completado`;
            if (elBarra) elBarra.style.width = `${pct}%`;

            if (dias >= 20) {
                if (elBadge) elBadge.innerHTML = '<span class="badge-success">10% OFF Habilitado 🎉</span>';
                if (elDesc) elDesc.innerText = '¡Felicidades! Superaste los 20 días de alquiler del mes: tu 10% de descuento está activo.';
            } else {
                if (elBadge) elBadge.innerHTML = '<span class="badge-success">En Progreso 🚴</span>';
                if (elDesc) elDesc.innerText = `Te faltan ${20 - dias} días pedaleando para conseguir tu 10% de descuento en el próximo mes.`;
            }
        })
        .catch(err => console.error('Error al cargar Eco-Puntos:', err));
}

// Inicialización del portal
document.addEventListener('DOMContentLoaded', () => {
    cargarTemaPreferido();
    cargarCatalogoUsuario();
});