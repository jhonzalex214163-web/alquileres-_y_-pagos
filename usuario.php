<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/AuthController.php';

// Validar que exista sesión y que el rol sea exclusivamente de usuario regular (Cliente)
if (!AuthController::isAuthenticated() || ($_SESSION['usuario_rol'] ?? null) !== 'cli') {
    header('Location: ' . BASE_URL . '?action=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiciJardín - Portal de Usuario</title>
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="base-url" content="controllers/">
    <script>
        window.USUARIO_SESION = {
            id: <?php echo (int)($_SESSION['user_id'] ?? 0); ?>,
            email: <?php echo json_encode($_SESSION['usuario_email'] ?? '', JSON_UNESCAPED_UNICODE); ?>
        };
    </script>
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
                <h1 style="font-size: 18px; font-weight: 800;">BiciJardín</h1>
                <p class="text-muted" style="font-size: 11px;">Jardín, Antioquia · Eco-Movilidad Urbana 🚴‍♂️</p>
            </div>
        </div>

        <!-- Pestañas del Portal de Usuario -->
        <div class="nav-tabs" style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button onclick="switchTab('view-viajes')" id="tab-viajes" class="tab-btn active">Mis Viajes</button>
            <button onclick="switchTab('view-rutas')" id="tab-rutas" class="tab-btn">Rutas y Estaciones</button>
            <button onclick="switchTab('view-recibos')" id="tab-recibos" class="tab-btn">Mis Recibos</button>
            <button onclick="switchTab('view-ecopuntos')" id="tab-ecopuntos" class="tab-btn">Eco-Puntos</button>
        </div>

        <div style="display: flex; gap: 16px; align-items: center;">
            <span class="text-muted" style="font-size: 13px;">🚴‍♂️ <?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?></span>
            <a href="<?php echo BASE_URL; ?>index.php?action=logout" style="color: #f43f5e; text-decoration: none; font-size: 13px; font-weight: bold;">Cerrar Sesión</a>
            <button id="theme-toggle" class="theme-toggle" onclick="toggleDayNight()">
                <span class="moon">🌙</span>
                <span class="sun">☀️</span>
            </button>
        </div>
    </header>

    <main>
        <!-- VISTA 1: MIS VIAJES (Desbloqueo y Cierre, flujo del usuario) -->
        <section id="view-viajes">
            <div style="margin-bottom: 20px;">
                <span class="text-accent" style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Flujo completo del viaje</span>
                <h2 style="font-size: 22px; font-weight: 800;">Alquila una bicicleta y pedalea por Jardín</h2>
                <p class="text-muted" style="font-size: 13px; margin-top: 6px;">Elige tu bicicleta, desbloquéala en la estación de retiro y devuélvela cuando termines. El costo se calcula por minuto al cerrar el viaje. 🚴</p>
            </div>

            <!-- Viaje en curso -->
            <div id="bloque-viaje-activo" style="display: none;">
                <div class="card" style="border: 1px solid rgba(59, 130, 246, 0.45); background: rgba(59, 130, 246, 0.06);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <h3 style="font-size: 17px; font-weight: 800; margin: 0;">⚡ Viaje en Curso</h3>
                        <span class="badge-success" style="font-size: 11px;">Activo</span>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 16px;">
                        <div>
                            <span class="text-muted" style="font-size: 11px; text-transform: uppercase; display: block;">Bicicleta</span>
                            <strong id="viaje-activo-bici">—</strong>
                        </div>
                        <div>
                            <span class="text-muted" style="font-size: 11px; text-transform: uppercase; display: block;">Retiro</span>
                            <strong id="viaje-activo-origen">—</strong>
                        </div>
                        <div>
                            <span class="text-muted" style="font-size: 11px; text-transform: uppercase; display: block;">Inicio</span>
                            <strong id="viaje-activo-inicio">—</strong>
                        </div>
                    </div>

                    <form id="form-cerrar-viaje" onsubmit="event.preventDefault(); cerrarViajeUsuarioJS();" style="display: flex; flex-direction: column; gap: 14px;">
                        <div>
                            <label for="estacion_destino" style="font-size: 12px; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 6px;" class="text-muted">Estación de Devolución</label>
                            <select id="estacion_destino" required style="width: 100%; padding: 9px; border-radius: 8px; border: 1px solid var(--card-border); background: var(--bg-60); color: var(--text-30);">
                                <option value="">Seleccionar estación…</option>
                            </select>
                        </div>
                        <div style="background: rgba(59, 130, 246, 0.10); border: 1px solid rgba(59, 130, 246, 0.35); border-radius: 12px; padding: 14px; text-align: center;">
                            <p class="text-muted" style="font-size: 12px;">Costo estimado hasta ahora</p>
                            <p id="costo-actual" style="font-size: 26px; font-weight: 900; color: #60a5fa; margin: 2px 0 0;">$0.00</p>
                        </div>
                        <button type="submit" class="btn-info" style="width: 100%;">⏹ Finalizar Viaje y Calcular Costo</button>
                    </form>
                </div>
            </div>

            <!-- Inicio de viaje (solo si no hay viaje activo) -->
            <div id="bloque-iniciar-viaje">
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--card-border); padding-bottom: 10px; margin-bottom: 14px;">
                        <h3 style="font-size: 16px; font-weight: 800;">▶️ Iniciar Viaje / Desbloqueo</h3>
                        <span class="badge-success" style="font-size: 11px;">Elige tu bicicleta</span>
                    </div>

                    <form id="form-iniciar-viaje" onsubmit="event.preventDefault(); iniciarViajeUsuarioJS();" style="display: flex; flex-direction: column; gap: 14px;">
                        <div>
                            <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 6px;" class="text-muted">Bicicleta Disponible</label>
                            <div id="bici-cards" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px;">
                                <!-- Renderizado dinámico por JS -->
                            </div>
                            <input type="hidden" id="id_bicicleta">
                        </div>

                        <div>
                            <label for="estacion_origen" style="font-size: 12px; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 6px;" class="text-muted">Estación de Retiro</label>
                            <select id="estacion_origen" required style="width: 100%; padding: 9px; border-radius: 8px; border: 1px solid var(--card-border); background: var(--bg-60); color: var(--text-30);">
                                <option value="">Seleccionar estación…</option>
                            </select>
                        </div>

                        <button type="submit" class="btn-primary" style="width: 100%;">🚴 Iniciar Viaje (Desbloquear)</button>
                    </form>
                </div>
            </div>
        </section>

        <!-- VISTA 2: RUTAS Y ESTACIONES -->
        <section id="view-rutas" style="display: none;">
            <div style="margin-bottom: 20px;">
                <span class="text-accent" style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Explora Jardín</span>
                <h2 style="font-size: 22px; font-weight: 800;">Rutas Turísticas y Estaciones</h2>
                <p class="text-muted" style="font-size: 13px; margin-top: 6px;">Conoce las rutas recomendadas para vivir Jardín, Antioquia y ubica las estaciones de carga y retiro.</p>
            </div>

            <!-- Lista de rutas turísticas -->
            <div class="card" style="margin-bottom: 20px;">
                <h3 style="font-size: 16px; margin-bottom: 14px;">🗺️ Rutas Recomendadas</h3>
                <div id="lista-rutas" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 12px;">
                    <!-- Cargado por JS -->
                </div>
            </div>

            <!-- Mapa -->
            <div class="card">
                <h3 style="font-size: 16px; margin-bottom: 12px;">Estaciones de Carga y Retiro en Jardín</h3>
                <div id="map" style="height: 460px; width: 100%; border-radius: 14px; border: 1px solid var(--card-border);"></div>
            </div>
        </section>

        <!-- VISTA 3: MIS RECIBOS -->
        <section id="view-recibos" style="display: none;">
            <div style="margin-bottom: 20px;">
                <span class="text-accent" style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Comprobantes de pago</span>
                <h2 style="font-size: 22px; font-weight: 800;">Mis Recibos de Alquiler</h2>
                <p class="text-muted" style="font-size: 13px; margin-top: 6px;">Consulta el detalle e imprime tus recibos, o guárdalos como PDF. 🖨️</p>
            </div>

            <div class="card">
                <h3 style="font-size: 16px; margin-bottom: 16px;">📄 Historial de Viajes</h3>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Fecha Inicio</th>
                            <th>Bicicleta</th>
                            <th>Ruta (Retiro → Devolución)</th>
                            <th>Duración</th>
                            <th>Costo</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-recibos">
                        <tr><td colspan="8" style="text-align:center; color:#94a3b8;">Cargando tus recibos…</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- VISTA 4: ECO-PUNTOS -->
        <section id="view-ecopuntos" style="display: none;">
            <div style="margin-bottom: 20px;">
                <span class="text-accent" style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Programa Eco-Puntos</span>
                <h2 style="font-size: 22px; font-weight: 800;">Tu aporte por la movilidad sostenible</h2>
            </div>

            <!-- Tarjeta Usuario -->
            <div class="card grid-3">
                <div>
                    <span class="text-muted" style="font-size: 11px; text-transform: uppercase;">Usuario Activo</span>
                    <h3 style="font-size: 20px; margin-top: 4px;"><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?></h3>
                    <p class="text-muted" style="font-size: 12px;"><?php echo htmlspecialchars($_SESSION['usuario_email'] ?? ''); ?></p>
                </div>
                <div>
                    <span class="text-muted" style="font-size: 11px; text-transform: uppercase;">Días Eco-Sostenibles</span>
                    <div style="margin-top: 4px;">
                        <span id="ecopuntos-dias" class="text-accent" style="font-size: 32px; font-weight: 900;">14</span>
                        <span class="text-muted" style="font-size: 12px;"> / 20 días meta</span>
                    </div>
                </div>
                <div>
                    <span class="text-muted" style="font-size: 11px; text-transform: uppercase;">Beneficio de Movilidad</span>
                    <div id="ecopuntos-badge" style="margin-top: 8px;">
                        <span class="badge-success">En Progreso (70%) 🚴</span>
                    </div>
                </div>
            </div>

            <!-- Progreso Eco -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; font-size: 14px; font-weight: 700;">
                    <span>Meta mensual para reducción de huella de carbono:</span>
                    <span id="ecopuntos-progreso" class="text-accent">70% Completado</span>
                </div>
                <div class="progress-bg">
                    <div id="ecopuntos-barra" class="progress-fill" style="width: 70%;"></div>
                </div>
                <p id="ecopuntos-desc" class="text-muted" style="font-size: 12px;">Te faltan 6 días pedaleando para conseguir tu 10% de descuento en el próximo mes.</p>
            </div>
        </section>
    </main>

    <script src="assets/sweetalert2/sweetalert2.all.min.js"></script>
    <script>
        // Respaldo: si el bundle no expone el global Swal, lo inyecta evaluándolo en el contexto global.
        if (typeof Swal === 'undefined') {
            fetch('assets/sweetalert2/sweetalert2.all.min.js')
                .then(r => r.text())
                .then(t => { new Function(t)(); })
                .catch(() => console.error('No se pudo cargar SweetAlert2.'));
        }
    </script>
    <script src="js/app.js?v=<?= filemtime('js/app.js') ?>"></script>
    <script src="js/usuario.js?v=<?= filemtime('js/usuario.js') ?>"></script>
</body>
</html>