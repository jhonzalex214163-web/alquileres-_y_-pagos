<?php
session_start();

// Validar que exista sesión y que el rol sea exclusivamente de usuario regular
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'user') {
    header('Location: views/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiciJardín - Portal de Usuario</title>
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

        <div style="display: flex; gap: 16px; align-items: center;">
            <span class="text-muted" style="font-size: 13px;">🚴‍♂️ <?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?></span>
            <a href="views/login.php" style="color: #f43f5e; text-decoration: none; font-size: 13px; font-weight: bold;">Cerrar Sesión</a>
            <button id="theme-toggle" class="theme-toggle" onclick="toggleDayNight()">
                <span class="moon">🌙</span>
                <span class="sun">☀️</span>
            </button>
        </div>
    </header>

    <main>
        <!-- VISTA DE USUARIO: SOLO ACCESO A SU INFORMACIÓN PERSONAL -->
        <section id="view-fidelizacion">
            <div style="margin-bottom: 20px;">
                <span class="text-accent" style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Programa Eco-Puntos</span>
                <h2 style="font-size: 22px; font-weight: 800;">Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Ciclista'); ?></h2>
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
                        <span class="text-accent" style="font-size: 32px; font-weight: 900;">14</span>
                        <span class="text-muted" style="font-size: 12px;"> / 20 días meta</span>
                    </div>
                </div>
                <div>
                    <span class="text-muted" style="font-size: 11px; text-transform: uppercase;">Beneficio de Movilidad</span>
                    <div style="margin-top: 8px;">
                        <span class="badge-success">En Progreso (70%) 🚴</span>
                    </div>
                </div>
            </div>

            <!-- Progreso Eco -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; font-size: 14px; font-weight: 700;">
                    <span>Meta mensual para reducción de huella de carbono:</span>
                    <span class="text-accent">70% Completado</span>
                </div>
                <div class="progress-bg">
                    <div class="progress-fill" style="width: 70%;"></div>
                </div>
                <p class="text-muted" style="font-size: 12px;">Te faltan 6 días pedaleando para conseguir tu 10% de descuento en el próximo mes.</p>
            </div>

            <!-- Mapa -->
            <div class="card">
                <h3 style="font-size: 16px; margin-bottom: 12px;">Estaciones de Carga y Retiro en Jardín</h3>
                <div id="map" style="height: 320px; width: 100%; border-radius: 14px; border: 1px solid var(--card-border);"></div>
            </div>
        </section>
    </main>

    <script src="js/app.js"></script>
</body>
</html>