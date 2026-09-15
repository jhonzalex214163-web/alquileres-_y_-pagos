<?php
// views/recibo.php — Recibo imprimible de un alquiler (propietario o admin)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../models/Alquiler.php';

if (!AuthController::isAuthenticated()) {
    header('Location: ' . BASE_URL . '?action=login');
    exit;
}

$id_alquiler = (int)($_GET['id_alquiler'] ?? 0);
$esAdmin = in_array(strtolower((string)($_SESSION['usuario_rol'] ?? '')), ['admin', 'oper', 'sup'], true);
$idUsuario = 0;
if (!$esAdmin) {
    $idUsuario = (int)($_SESSION['user_id'] ?? 0);
}

$modelo = new Alquiler();
$recibo = $id_alquiler > 0 ? $modelo->obtenerRecibo($id_alquiler, $idUsuario) : null;

if (!$recibo) {
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Recibo no encontrado</title>
        <link rel="stylesheet" href="../css/styles.css"></head><body>
        <main style="max-width: 760px; margin: 40px auto; padding: 0 16px;">
        <div class="card"><h2>Recibo no encontrado</h2>
        <p class="text-muted">El recibo no existe o no pertenece a tu cuenta.</p>
        <p><a href="../usuario.php?view=recibos" class="btn-primary" style="text-decoration:none;">Volver a Mis Recibos</a></p>
        </div></main></body></html>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo #<?php echo (int)$recibo['id_alquiler']; ?> — BiciJardín</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        body { background: #0d2318; }
        .recibo-wrap { max-width: 720px; margin: 30px auto; padding: 0 14px; }
        .recibo-card { background: var(--bg-60, #12281e); border: 1px solid var(--card-border, rgba(34,197,94,.2)); border-radius: 16px; padding: 30px; }
        .recibo-head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px dashed rgba(34,197,94,.3); padding-bottom: 16px; margin-bottom: 20px; }
        .recibo-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 22px; margin: 16px 0; }
        .recibo-grid div b { display: block; font-size: 12px; color: var(--text-30, #94a3b8); text-transform: uppercase; letter-spacing: .4px; }
        .recibo-grid div span { font-size: 15px; color: var(--text-30, #e2f1e8); }
        .recibo-tot { display: flex; justify-content: space-between; align-items: center; border-top: 2px dashed rgba(34,197,94,.3); margin-top: 18px; padding-top: 16px; }
        .recibo-tot .monto { font-size: 30px; font-weight: 900; color: #22c55e; }
        .state-badge { padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 800; }
        .state-finalizado { background: rgba(34,197,94,.15); color: #4ade80; border: 1px solid rgba(34,197,94,.4); }
        .state-en_curso { background: rgba(59,130,246,.15); color: #60a5fa; border: 1px solid rgba(59,130,246,.4); }
        .barra-acciones { display: flex; gap: 10px; margin: 18px 0; flex-wrap: wrap; }
        .no-print { }
        @media print {
            body { background: #fff !important; }
            .recibo-card { background: #fff !important; border-color: #ddd !important; box-shadow: none !important; }
            .no-print { display: none !important; }
            .recibo-wrap { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="recibo-wrap">
        <div class="barra-acciones no-print">
            <button class="btn-primary" onclick="window.print()">🖨️ Imprimir / Guardar como PDF</button>
            <a href="../usuario.php?view=recibos" class="btn-secondary" style="text-decoration:none; align-self:center;">← Volver a Mis Recibos</a>
        </div>

        <div class="recibo-card" id="recibo">
            <div class="recibo-head">
                <div>
                    <h1 style="font-size: 22px; font-weight: 900; margin: 0;">BiciJardín</h1>
                    <p style="margin: 2px 0 0; font-size: 13px;" class="text-muted">Jardín, Antioquia · Eco-Movilidad Urbana 🚴</p>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 20px; font-weight: 900;">Recibo de Alquiler</div>
                    <div style="font-size: 13px;" class="text-muted">#<?php echo (int)$recibo['id_alquiler']; ?></div>
                    <div style="margin-top: 6px;">
                        <span class="state-badge <?php echo $recibo['estado'] === 'finalizado' ? 'state-finalizado' : 'state-en_curso'; ?>">
                            <?php echo strtoupper(htmlspecialchars((string)$recibo['estado'], ENT_QUOTES, 'UTF-8')); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="recibo-grid">
                <div><b>Cliente</b><span><?php echo htmlspecialchars((string)$recibo['cliente'], ENT_QUOTES, 'UTF-8'); ?></span></div>
                <div><b>Correo</b><span><?php echo htmlspecialchars((string)$recibo['email'], ENT_QUOTES, 'UTF-8'); ?></span></div>
                <div><b>Teléfono</b><span><?php echo htmlspecialchars((string)($recibo['telefono'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></span></div>
                <div><b>Bicicleta</b><span><?php echo htmlspecialchars($recibo['bici_modelo'] . ' (' . $recibo['num_serie'] . ')', ENT_QUOTES, 'UTF-8'); ?></span></div>
                <div><b>Estación de Retiro</b><span><?php echo htmlspecialchars((string)$recibo['estacion_origen'], ENT_QUOTES, 'UTF-8'); ?></span></div>
                <div><b>Estación de Entrega</b><span><?php echo htmlspecialchars((string)$recibo['estacion_destino'], ENT_QUOTES, 'UTF-8'); ?></span></div>
                <div><b>Fecha / Hora de Inicio</b><span><?php echo htmlspecialchars((string)$recibo['fecha_inicio'], ENT_QUOTES, 'UTF-8'); ?></span></div>
                <div><b>Fecha / Hora de Fin</b><span><?php echo htmlspecialchars((string)($recibo['fecha_fin'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></span></div>
                <div><b>Duración</b><span><?php echo (int)$recibo['minutos_totales']; ?> min</span></div>
                <div><b>Batería (inicio → fin)</b>
                    <span><?php echo $recibo['bateria_inicio'] !== null ? (int)$recibo['bateria_inicio'] . '%' : '—'; ?> → <?php echo $recibo['bateria_fin'] !== null ? (int)$recibo['bateria_fin'] . '%' : '—'; ?></span>
                </div>
            </div>

            <div class="recibo-tot">
                <div>
                    <div style="font-size: 12px;" class="text-muted">Costo total del viaje</div>
                    <div style="font-size: 13px;" class="text-muted">Total pagado: $<?php echo number_format((float)$recibo['total_pagado'], 2); ?></div>
                    <?php
                    $costo = (float)$recibo['costo_total'];
                    $pagado = (float)$recibo['total_pagado'];
                    $restante = $costo - $pagado;
                    ?>
                    <div style="font-size: 12px; color: <?php echo $restante > 0 ? '#f59e0b' : '#4ade80'; ?>;">
                        <?php echo $restante > 0 ? 'Saldo pendiente: $' . number_format($restante, 2) : 'Pago al día'; ?>
                    </div>
                </div>
                <div class="monto">$<?php echo number_format($costo, 2); ?></div>
            </div>

            <div style="margin-top: 26px; padding-top: 14px; border-top: 1px dashed rgba(34,197,94,.2); text-align:center;">
                <p style="margin: 0; font-size: 12px;" class="text-muted">Gracias por elegir movilidad sostenible 🌱 — Este recibo es VÁLIDO y generado por el sistema BiciJardín.</p>
            </div>
        </div>
    </div>
</body>
</html>