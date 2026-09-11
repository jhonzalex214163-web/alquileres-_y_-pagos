<?php
require_once __DIR__ . '/../controllers/AuthController.php';

$user = AuthController::getUserFromSession();
$rolNombre = AuthController::getRolNombre() ?? 'Usuario';
$esAdmin = AuthController::getRol() === ROL_ADMIN;
$userName = $user ? $user->nombre . ' ' . $user->apellido : 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiciJardín - Dashboard</title>
    <link rel="stylesheet" href="css/styles.css">
    <!-- Leaflet para el Mapa -->
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

        <div style="display: flex; align-items: center; gap: 12px;">
            <span class="text-muted" style="font-size: 12px;">
                <?php echo htmlspecialchars($userName); ?>
            </span>

            <nav class="nav-tabs">
                <button onclick="switchTab('dashboard')" id="tab-dashboard" class="tab-btn active">🏠 Dashboard</button>
                <button onclick="switchTab('fidelizacion')" id="tab-fidelizacion" class="tab-btn">🌿 Portal Fidelización</button>
                <button onclick="switchTab('auditoria')" id="tab-auditoria" class="tab-btn">📊 Auditoría SQL</button>
            </nav>

            <button id="theme-toggle" class="theme-toggle" onclick="toggleDayNight()">
                <span class="moon">🌙</span>
                <span class="sun">☀️</span>
            </button>

            <button onclick="window.location.href='?action=logout'"
                    class="tab-btn"
                    style="color: var(--accent-danger);">
                🡒 Salir
            </button>
        </div>
    </header>

    <main>
        <!-- VISTA DASHBOARD: Rol -->
        <section id="view-dashboard" class="hidden">
            <div class="card" style="margin-bottom: 24px;">
                <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <span class="text-muted" style="font-size: 12px; text-transform: uppercase;">Rol Asignado</span>
                        <h2 style="font-size: 26px; font-weight: 800; margin-top: 6px; color: var(--accent-glow);">
                            <?php echo $rolNombre; ?>
                        </h2>
                        <p class="text-muted" style="font-size: 13px; margin-top: 4px;">
                            Nivel de acceso: <?php echo $user ? $user->rol->nivel_acceso : 0; ?>
                        </p>
                    </div>
                    <div style="flex-shrink: 0;">
                        <?php if ($esAdmin): ?>
                            <span class="badge-success" style="font-size: 14px; padding: 10px 20px;">
                                🔧 Administrador del Sistema
                            </span>
                        <?php else: ?>
                            <span class="badge-success" style="font-size: 14px; padding: 10px 20px;">
                                🚴 Usuario Eco-Ciclista
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <?php if ($esAdmin): ?>
                    <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 12px;">
                        Panel de Administrador
                    </h3>
                    <p class="text-muted" style="font-size: 14px; line-height: 1.6;">
                        Bienvenido <strong>Administrador</strong>. Tienes acceso completo al sistema:
                    </p>
                    <ul class="text-muted" style="font-size: 13px; margin-top: 10px; line-height: 1.8;">
                        <li>Gestión de usuarios y roles</li>
                        <li>Conciliación financiera y auditoría SQL</li>
                        <li>Administración de estaciones y bicicletas</li>
                        <li>Reportes y logs de auditoría</li>
                    </ul>
                <?php else: ?>
                    <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 12px;">
                        Panel de Usuario
                    </h3>
                    <p class="text-muted" style="font-size: 14px; line-height: 1.6;">
                        Bienvenido <strong>Usuario</strong>. Tienes acceso a:
                    </p>
                    <ul class="text-muted" style="font-size: 13px; margin-top: 10px; line-height: 1.8;">
                        <li>Portal de Fidelización Eco-Puntos</li>
                        <li>Consulta de estaciones de carga en el mapa</li>
                        <li>Historial de alquileres y beneficios</li>
                    </ul>
                <?php endif; ?>
            </div>
        </section>

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
                        <option value="1">Andrés López (Eco-Líder 10%)</option>
                        <option value="2">Maria Gomez (En Progreso)</option>
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

            <!-- Mapa -->
            <div class="card">
                <h3 style="font-size: 16px; margin-bottom: 12px;">Estaciones de Carga y Retiro en Jardín</h3>
                <div id="map" style="height: 320px; border-radius: 14px; border: 1px solid var(--card-border);"></div>
            </div>
        </section>

        <!-- VISTA 2: AUDITORÍA -->
        <section id="view-auditoria" class="hidden">
            <div style="margin-bottom: 20px;">
                <span class="text-accent" style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Control Interno</span>
                <h2 style="font-size: 22px; font-weight: 800;">Auditoría Financiera y Conciliación SQL</h2>
            </div>

            <div class="grid-3" style="margin-bottom: 20px;">
                <div class="card" style="margin: 0;">
                    <span class="text-muted" style="font-size: 12px;">Total Recaudado Conciliado</span>
                    <div class="text-accent" style="font-size: 26px; font-weight: 900; margin-top: 4px;">$1,450.00</div>
                    <span class="text-muted" style="font-size: 10px;">`vw_conciliacion_financiera`</span>
                </div>
                <div class="card" style="margin: 0;">
                    <span class="text-muted" style="font-size: 12px;">Descuadres / Parciales</span>
                    <div class="text-warning" style="font-size: 26px; font-weight: 900; margin-top: 4px;">$85.00</div>
                    <span class="text-muted" style="font-size: 10px;">1 registro pendiente</span>
                </div>
                <div class="card" style="margin: 0;">
                    <span class="text-muted" style="font-size: 12px;">Transacciones Huérfanas</span>
                    <div class="text-danger" style="font-size: 26px; font-weight: 900; margin-top: 4px;">1 Alerta</div>
                    <span class="text-muted" style="font-size: 10px;">`vw_transacciones_huerfanas`</span>
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

            <!-- Logs de Auditoría -->
            <div class="card">
                <h3 style="font-size: 16px; margin-bottom: 16px;">Logs de Auditoría</h3>
                <div id="log-container">
                    <!-- Cargado por JS -->
                </div>
            </div>
        </section>
    </main>

    <script src="js/app.js"></script>
</body>
</html>
