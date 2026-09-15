<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/AuthController.php';

// Control de acceso: Si no hay sesión iniciada o el rol NO es admin, redirigir al login
if (!AuthController::isAuthenticated() || ($_SESSION['usuario_rol'] ?? null) !== 'admin') {
    header('Location: ' . BASE_URL . '?action=login');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiciJardín - Panel Administrador</title>
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="base-url" content="controllers/">
    <link rel="stylesheet" href="css/styles.css">
    <!-- Leaflet CSS y JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>

    <header>
        <div class="brand">
            <img src="img/logo.png" alt="Logo BiciJardín" style="height: 36px; width: auto; object-fit: contain;">
            <div>
                <h1 style="font-size: 18px; font-weight: 800;">BiciJardín (ADMIN)</h1>
                <p class="text-muted" style="font-size: 11px;">Jardín, Antioquia · Eco-Movilidad Urbana 🚴‍♂️</p>
            </div>
        </div>
        
        <!-- Pestañas / Botones de Navegación del Panel -->
        <div class="nav-tabs" style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button onclick="switchTab('view-fidelizacion')" id="tab-fidelizacion" class="tab-btn active">Portal Fidelización</button>
            <button onclick="switchTab('view-auditoria')" id="tab-auditoria" class="tab-btn">Auditoría SQL</button>
            <button onclick="switchTab('view-monitoreo')" id="tab-monitoreo" class="tab-btn">Monitoreo en Tiempo Real</button>
            <button onclick="switchTab('view-metodos-pago')" id="tab-metodos-pago" class="tab-btn">Métodos de Pago</button>
        </div>

        <div style="display: flex; gap: 16px; align-items: center;">
            <span class="text-muted" style="font-size: 12px; font-weight: 600;">
                👑 <?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Administrador'); ?>
            </span>
            <a href="<?php echo BASE_URL; ?>index.php?action=logout" style="color: #f43f5e; text-decoration: none; font-size: 13px; font-weight: bold;">Cerrar Sesión</a>
            <button id="theme-toggle" class="theme-toggle" onclick="toggleDayNight()">
                <span class="moon">🌙</span>
                <span class="sun">☀️</span>
            </button>
        </div>
    </header>

    <main>
        <!-- VISTA 1: FIDELIZACIÓN ECOLÓGICA -->
        <section id="view-fidelizacion">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <span class="text-accent" style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Programa Eco-Puntos</span>
                    <h2 style="font-size: 22px; font-weight: 800;">Fidelización de Ciclistas</h2>
                </div>
                <div>
                    <label class="text-muted" style="font-size: 12px;">Usuario: </label>
                    <select id="user-select" onchange="cargarDatosUsuario(this.value)">
                        <option value="">Cargando usuarios…</option>
                    </select>
                </div>
            </div>

            <!-- Tarjeta Usuario -->
            <div class="card grid-3">
                <div>
                    <span class="text-muted" style="font-size: 11px; text-transform: uppercase;">Usuario Activo</span>
                    <h3 id="profile-name" style="font-size: 20px; margin-top: 4px;">Andrés López</h3>
                    <p id="profile-email" class="text-muted" style="font-size: 12px;">andres.lopez@email.com</p>
                </div>
                <div>
                    <span class="text-muted" style="font-size: 11px; text-transform: uppercase;">Días Eco-Sostenibles</span>
                    <div style="margin-top: 4px;">
                        <span id="profile-days" class="text-accent" style="font-size: 32px; font-weight: 900;">22</span>
                        <span class="text-muted" style="font-size: 12px;"> / 20 días meta</span>
                    </div>
                </div>
                <div>
                    <span class="text-muted" style="font-size: 11px; text-transform: uppercase;">Beneficio de Movilidad</span>
                    <div id="profile-badge" style="margin-top: 8px;">
                        <span class="badge-success">10% OFF Habilitado 🎉</span>
                    </div>
                </div>
            </div>

            <!-- Progreso Eco -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; font-size: 14px; font-weight: 700;">
                    <span>Meta mensual para reducción de huella de carbono:</span>
                    <span id="progress-text" class="text-accent">100% Completado</span>
                </div>
                <div class="progress-bg">
                    <div id="progress-bar" class="progress-fill" style="width: 100%;"></div>
                </div>
                <p id="progress-desc" class="text-muted" style="font-size: 12px;">¡Excelente compromiso verde! Has completado más de 20 días de viaje limpio este mes.</p>
            </div>

            <!-- Ranking de Clientes Frecuentes del Mes -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h3 style="font-size: 16px; margin: 0;">🏆 Clientes con Más Alquileres del Mes</h3>
                    <span class="text-muted" style="font-size: 11px;">vw_clientes_frecuentes</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Email</th>
                            <th>Días / Alquileres</th>
                            <th>Descuento</th>
                            <th>Estado Fidelidad</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-frecuentes">
                        <!-- Cargado por JS desde la BD -->
                    </tbody>
                </table>
            </div>
        </section>

        <!-- VISTA 2: AUDITORÍA -->
        <section id="view-auditoria" style="display: none;">
            <div style="margin-bottom: 20px;">
                <span class="text-accent" style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Control Interno</span>
                <h2 style="font-size: 22px; font-weight: 800;">Auditoría Financiera y Conciliación SQL</h2>
            </div>

            <div class="grid-3" style="margin-bottom: 20px;">
                <div class="card" style="margin: 0;">
                    <span class="text-muted" style="font-size: 12px;">Total Recaudado Conciliado</span>
                    <div id="conciliado-total" class="text-accent" style="font-size: 26px; font-weight: 900; margin-top: 4px;">$—</div>
                    <span id="conciliado-sub" class="text-muted" style="font-size: 10px;">vw_conciliacion_financiera</span>
                </div>
                <div class="card" style="margin: 0;">
                    <span class="text-muted" style="font-size: 12px;">Descuadres / Parciales</span>
                    <div id="descuadres-monto" class="text-warning" style="font-size: 26px; font-weight: 900; margin-top: 4px;">$—</div>
                    <span id="descuadres-sub" class="text-muted" style="font-size: 10px;">—</span>
                </div>
                <div class="card" style="margin: 0;">
                    <span class="text-muted" style="font-size: 12px;">Transacciones Huérfanas</span>
                    <div id="huerfanas-num" class="text-danger" style="font-size: 26px; font-weight: 900; margin-top: 4px;">—</div>
                    <span id="huerfanas-sub" class="text-muted" style="font-size: 10px;">vw_transacciones_huerfanas</span>
                </div>
            </div>

            <div class="card">
                <h3 style="font-size: 16px; margin-bottom: 16px;">Conciliación Financiera de Pagos</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID Alquiler</th>
                            <th>Cliente</th>
                            <th>Monto Alquiler</th>
                            <th>Monto Pagado</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-conciliacion">
                        <!-- Cargado por JS -->
                    </tbody>
                </table>
            </div>
        </section>

        <!-- VISTA 3: MONITOREO EN TIEMPO REAL -->
        <section id="view-monitoreo" style="display: none;">
            <div style="margin-bottom: 20px;">
                <span class="text-accent" style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Telemetría Activa</span>
                <h2 style="font-size: 22px; font-weight: 800;">Monitoreo en Tiempo Real e Interfaz de Seguimiento</h2>
                <p id="monitoreo-quien" class="text-muted" style="font-size: 12px; margin-bottom: 14px;">—</p>
            </div>

            <div class="grid-3" style="margin-bottom: 20px;">
                <div class="card" style="margin: 0;">
                    <span class="text-muted" style="font-size: 12px;">Batería Actual</span>
                    <div id="bateria-nivel" class="text-accent" style="font-size: 28px; font-weight: 900; margin-top: 4px;">—</div>
                    <span id="bateria-detalle" class="text-muted" style="font-size: 11px;">Adquirida vs Actual</span>
                </div>
                <div class="card" style="margin: 0;">
                    <span class="text-muted" style="font-size: 12px;">Estado de Bicicleta</span>
                    <div id="estado-bici" style="font-size: 22px; font-weight: 800; margin-top: 4px; color: #22c55e;">—</div>
                    <span id="estado-detalle" class="text-muted" style="font-size: 11px;">Adquirido vs Actual</span>
                </div>
                <div class="card" style="margin: 0;">
                    <span class="text-muted" style="font-size: 12px;">Alerta de Mantenimiento</span>
                    <div id="alerta-box" class="text-accent" style="font-size: 18px; font-weight: 700; margin-top: 4px;">—</div>
                    <span id="alerta-detalle" class="text-muted" style="font-size: 11px;">Al adquirir vs Actual</span>
                </div>
            </div>

            <!-- Mapa: Estaciones de Carga y Retiro -->
            <div class="card">
                <h3 style="font-size: 16px; margin-bottom: 12px;">Estaciones de Carga y Retiro en Jardín</h3>
                <div id="map" style="height: 320px; width: 100%; border-radius: 14px; border: 1px solid var(--card-border);"></div>
            </div>

            <div class="card">
                <h3 style="font-size: 16px; margin-bottom: 12px;">Rutas por Usuario</h3>
                <button type="button" class="btn-secondary" onclick="togglePanelUsuariosMonitoreo()">👥 Ver Usuarios Registrados</button>
                <p class="text-muted" style="font-size: 12px; margin-top: 8px;">Selecciona un usuario para trazar en el mapa los recorridos que ha realizado, con su duración y monto pagado.</p>
                <div id="panel-usuarios-monitoreo" style="display: none; margin-top: 12px;"></div>
                <div id="panel-ruta-usuario" style="display: none; margin-top: 12px;"></div>
            </div>

            <div class="card">
                <h3 style="font-size: 16px; margin-bottom: 12px;">Estado Geofencing y Parámetros</h3>
                <p class="text-muted" style="font-size: 13px;">Supervisión continua de geocerca, batería y estatus operativo en vivo.</p>
            </div>
        </section>

        <!-- VISTA 5: MÉTODOS DE PAGO Y PROCESAMIENTO -->
        <section id="view-metodos-pago" style="display: none;">
            <div style="margin-bottom: 20px;">
                <span class="text-accent" style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Pasarela y Métodos</span>
                <h2 style="font-size: 22px; font-weight: 800;">Estructura, Registro y Procesamiento de Pagos</h2>
            </div>

            <!-- Formulario de Registro de Pagos / Carga de Evidencia -->
            <div class="card" style="margin-bottom: 20px;">
                <h3 style="font-size: 16px; margin-bottom: 12px;">Registrar Transacción y Cargar Comprobante</h3>
                <form id="form-procesar-pago" onsubmit="event.preventDefault(); registrarPagoJS();" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; align-items: end;">
                    <div>
                        <label style="font-size: 12px; font-weight: 600;" class="text-muted">ID Alquiler</label>
                        <input type="number" id="pago_alquiler_id" required placeholder="Ej: 1" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--card-border);">
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 600;" class="text-muted">Monto ($)</label>
                        <input type="number" step="0.01" id="pago_monto" required placeholder="0.00" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--card-border);">
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 600;" class="text-muted">Método de Pago</label>
                        <select id="pago_metodo" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--card-border); background: var(--bg-60); color: var(--text-30);">
                            <option value="TARJETA">💳 Tarjeta (tokenizada)</option>
                            <option value="TRANSFERENCIA">🏦 Transferencia (comprobante)</option>
                            <option value="QR">📱 QR / Nequi</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 600;" class="text-muted">Comprobante / Evidencia</label>
                        <input type="file" id="pago_evidencia" accept="image/*,application/pdf" style="width: 100%; padding: 6px; border-radius: 6px; border: 1px solid var(--card-border);">
                    </div>
                    <div>
                        <button type="submit" class="btn-primary" style="width: 100%;">
                            💳 Procesar Pago
                        </button>
                    </div>
                </form>
                <p class="text-muted" style="font-size: 12px; margin-top: 8px;">💡 Pago con tarjeta tokenizada se aprueba al instante. Transferencia y QR quedan <b>Pendientes</b> hasta que los compruebes y apruebes aquí.</p>
            </div>

            <!-- Tabla de Transacciones Procesadas Base de Datos -->
            <div class="card" style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h3 style="font-size: 16px; margin: 0;">Transacciones Registradas en BD</h3>
                    <button onclick="actualizarTablaTransaccionesJS()" class="btn-secondary">🔄 Actualizar Tabla</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID Tx</th>
                            <th>ID Alquiler</th>
                            <th>Referencia Externa</th>
                            <th>Método</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-transacciones-base">
                        <!-- Se llena dinámicamente con JS -->
                    </tbody>
                </table>
            </div>

            <!-- Métodos de Pago Guardados por Usuario -->
            <div class="card">
                <h3 style="font-size: 16px; margin-bottom: 16px;">Métodos de Pago Registrados</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID Método</th>
                            <th>ID Usuario</th>
                            <th>Tipo</th>
                            <th>Proveedor</th>
                            <th>Últimos Dígitos</th>
                            <th>Predeterminado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-metodos-pago">
                        <tr>
                            <td>1</td>
                            <td>1</td>
                            <td>Tarjeta Crédito</td>
                            <td>Visa</td>
                            <td>**** 4582</td>
                            <td><span class="badge-success">Sí</span></td>
                            <td><button onclick="eliminarTokenJS(1)" style="background: #ef4444; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 11px;">Revocar Token</button></td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>2</td>
                            <td>Billetera Digital</td>
                            <td>Nequi</td>
                            <td>**** 8821</td>
                            <td>No</td>
                            <td><button onclick="eliminarTokenJS(2)" style="background: #ef4444; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 11px;">Revocar Token</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="assets/sweetalert2/sweetalert2.all.min.js"></script>
    <script>
        // Respaldo: si el bundle no expone el global Swal, lo inyecta evaluándolo en el contexto global.
        if (typeof Swal === 'undefined') {
            fetch('assets/sweetalert2/sweetalert2.all.min.js')
                .then(r => r.text())
                .then(t => { new Function(t)(); if (typeof Swal === 'undefined') console.error('SweetAlert2 no cargó correctamente.'); })
                .catch(() => console.error('No se pudo cargar SweetAlert2.'));
        }
    </script>
    <script src="js/app.js?v=<?= filemtime('js/app.js') ?>"></script>
    <script src="js/trip_monitor.js?v=<?= filemtime('js/trip_monitor.js') ?>"></script>
    <script>
        // Función para cambiar pestañas y cargar transacciones automáticamente al entrar a Métodos de Pago
        function switchTab(viewId) {
            document.querySelectorAll('main > section').forEach(sec => sec.style.display = 'none');
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));

            const targetSec = document.getElementById(viewId);
            if(targetSec) {
                targetSec.style.display = 'block';
            }

            const targetBtn = document.getElementById('tab-' + viewId.replace('view-', ''));
            if(targetBtn) {
                targetBtn.classList.add('active');
            }

            // Si entra a métodos de pago, cargar la lista actualizada de la BD
            if (viewId === 'view-metodos-pago' && typeof cargarTransaccionesJS === 'function') {
                cargarTransaccionesJS();
            }

            // Si entra a auditoría, cargar la conciliación financiera real desde la BD
            if (viewId === 'view-auditoria' && typeof cargarConciliacionJS === 'function') {
                cargarConciliacionJS();
            }

            // Si entra a monitoreo, iniciar telemetría real de la bicicleta 1
            if (viewId === 'view-monitoreo' && typeof iniciarMonitoreo === 'function') {
                iniciarMonitoreo(1);
            }

            // El mapa de estaciones vive ahora en Monitoreo; al mostrarlo hay que recalcular su tamaño
            if (viewId === 'view-monitoreo') {
                setTimeout(() => {
                    if (typeof map !== 'undefined' && map !== null) {
                        map.invalidateSize();
                    } else if (typeof initMap === 'function') {
                        initMap();
                    }
                }, 150);
            }
        }
    </script>
</body>
</html>

