<?php
// controllers/PagoController.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';

// [T-06-09] Validar canal seguro (HTTPS) — solo obligatorio en producción.
// En desarrollo (APP_ENV=dev) se permite HTTP para poder probar localmente.
if (APP_ENV === 'prod') {
    if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado. Se requiere conexión HTTPS segura.']);
        exit;
    }
}

header('Content-Type: application/json');

$pdo = Database::getConnection();

function requireAuthPago(): void
{
    if (!AuthController::isAuthenticated()) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'No autorizado. Inicia sesión.']);
        exit;
    }
}

function requireCsrfPago(): void
{
    if (!csrf_verify($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido.']);
        exit;
    }
}

// [T-06-04] Validación estricta del comprobante subido
function validarComprobante(array $file): ?string
{
    $maxBytes = 2 * 1024 * 1024; // 2 MB
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
    $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null; // No se adjuntó archivo
    }
    if ($file['size'] > $maxBytes) {
        throw new RuntimeException('El comprobante supera el tamaño máximo de 2 MB.');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        throw new RuntimeException('Extensión no permitida. Usa imágenes (jpg/png/gif/webp) o PDF.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowedMime, true)) {
        throw new RuntimeException('El tipo de archivo no es válido.');
    }

    $filename = 'comprobante_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $upload_dir = __DIR__ . '/../uploads/comprobantes/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
        throw new RuntimeException('No se pudo guardar el comprobante.');
    }

    return 'uploads/comprobantes/' . $filename;
}

// ============================================================
// Dispatcher de acciones
// ============================================================
$action = $_GET['action'] ?? '';

switch ($action) {
    // [T-06-03, T-06-04, T-07-03, T-07-04, T-07-05, T-07-06]
    case 'registrar_pago':
        requireAuthPago();
        requireCsrfPago();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
            exit;
        }

        $id_alquiler = $_POST['id_alquiler'] ?? null;
        $monto = (float)($_POST['monto'] ?? 0);
        $ref_externa = $_POST['referencia_externa'] ?? null;
        $metodo = strtoupper($_POST['metodo'] ?? 'TARJETA');

        // [T-07-05] Validación de referencia obligatoria
        if (!$id_alquiler || $monto <= 0 || !$ref_externa) {
            echo json_encode(['status' => 'error', 'message' => 'Faltan parámetros obligatorios.']);
            exit;
        }

        // [T-07-06] Control de duplicidad de transacción
        $checkStmt = $pdo->prepare("SELECT id_transaccion FROM transacciones WHERE referencia_externa = ?");
        $checkStmt->execute([$ref_externa]);
        if ($checkStmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Transacción duplicada detectada.']);
            exit;
        }

        // Validar que el alquiler exista antes de insertar (evita error silencioso por FK)
        $alquilerStmt = $pdo->prepare("SELECT id_alquiler FROM alquileres WHERE id_alquiler = ?");
        $alquilerStmt->execute([$id_alquiler]);
        if (!$alquilerStmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'El alquiler indicado no existe.']);
            exit;
        }

        // [T-06-04] Carga de evidencia fotográfica (validada)
        try {
            $comprobante_path = isset($_FILES['evidencia']) ? validarComprobante($_FILES['evidencia']) : null;
        } catch (RuntimeException $re) {
            echo json_encode(['status' => 'error', 'message' => $re->getMessage()]);
            exit;
        }

        // Método de pago: TARJETA al instante (APROBADO); TRANSFERENCIA/QR quedan PENDIENTES
        // hasta que el administrador los compruebe (lógica del README).
        $metodoValido = in_array($metodo, ['TARJETA', 'TRANSFERENCIA', 'QR'], true) ? $metodo : 'TARJETA';
        $estadoInicial = $metodoValido === 'TARJETA' ? 'APROBADO' : 'PENDIENTE';

        // [T-07-04] Insertar transacción
        $stmt = $pdo->prepare(
            "INSERT INTO transacciones
                (id_alquiler, id_metodo, referencia_externa, monto, metodo, estado, comprobante_url, valor, fecha_pago)
             VALUES (?, NULL, ?, ?, ?, ?, ?, ?, NOW())"
        );
        try {
            $success = $stmt->execute([$id_alquiler, $ref_externa, $monto, $metodoValido, $estadoInicial, $comprobante_path, $monto]);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo guardar la transacción. Verifica los datos e inténtalo de nuevo.']);
            exit;
        }

        if ($success) {
            echo json_encode(['status' => 'success', 'message' => $estadoInicial === 'PENDIENTE'
                ? 'Pago registrado en estado PENDIENTE. El administrador deberá aprobarlo para conciliarlo.'
                : 'Pago registrado y verificado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar la transacción.']);
        }
        exit;

    // [T-06-07] Eliminación lógica del token de pasarela
    case 'revocar_token':
        requireAuthPago();
        requireCsrfPago();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $id_token = $data['id_token'] ?? null;

        if ($id_token) {
            try {
                $stmt = $pdo->prepare("UPDATE tokens_pasarela SET activo = 0 WHERE id_token = ?");
                $stmt->execute([$id_token]);
                echo json_encode(['status' => 'success', 'message' => 'Token revocado correctamente.']);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo revocar el token.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ID de token no provisto.']);
        }
        exit;

    // Obtener historial de transacciones para la vista
    case 'listar_transacciones':
        requireAuthPago();

        try {
            $stmt = $pdo->query("SELECT t.*, COALESCE(mp.tipo, '—') AS metodo_nombre
                                 FROM transacciones t
                                 LEFT JOIN metodos_pago mp ON mp.id_metodo = t.id_metodo
                                 ORDER BY t.fecha_pago DESC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error al consultar las transacciones.']);
        }
        exit;

    // Aprobar pago pendiente (transferencia/QR) — solo administrador
    case 'aprobar_pago':
        requireAuthPago();
        requireCsrfPago();
        if (!AuthController::esAdmin()) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Requiere rol administrador.']);
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $idTx = (int)($data['id_transaccion'] ?? 0);
        if ($idTx <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Transacción no válida.']);
            exit;
        }
        try {
            $stmt = $pdo->prepare("UPDATE transacciones SET estado = 'APROBADO' WHERE id_transaccion = ?");
            $stmt->execute([$idTx]);
            echo json_encode(['status' => 'success', 'message' => 'Pago aprobado y conciliado correctamente.']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo aprobar el pago.']);
        }
        exit;

    // Rechazar pago pendiente — solo administrador
    case 'rechazar_pago':
        requireAuthPago();
        requireCsrfPago();
        if (!AuthController::esAdmin()) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Requiere rol administrador.']);
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $idTx = (int)($data['id_transaccion'] ?? 0);
        if ($idTx <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Transacción no válida.']);
            exit;
        }
        try {
            $stmt = $pdo->prepare("UPDATE transacciones SET estado = 'RECHAZADO' WHERE id_transaccion = ?");
            $stmt->execute([$idTx]);
            echo json_encode(['status' => 'success', 'message' => 'Pago rechazado. No se conciliará.']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo rechazar el pago.']);
        }
        exit;

    // Auditoría SQL: conciliación financiera de usuarios que ya pagaron su servicio
    case 'conciliacion':
        requireAuthPago();

        try {
            $stmt = $pdo->query(
                "SELECT c.id_alquiler, c.id_usuario, c.cliente, c.monto_alquiler,
                        c.monto_pagado, c.diferencia, c.estado_conciliacion
                 FROM vw_conciliacion_financiera c
                 ORDER BY c.cliente ASC, c.id_alquiler ASC"
            );
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Resumen de los indicadores de la vista completa (tarjetas de Auditoría Financiera)
            $resumenStmt = $pdo->query(
                "SELECT
                    ROUND(COALESCE(SUM(CASE WHEN c.estado_conciliacion = 'Conciliado' COLLATE utf8mb4_general_ci THEN c.monto_pagado ELSE 0 END), 0), 2) AS total_conciliado,
                    SUM(CASE WHEN c.estado_conciliacion = 'Conciliado' COLLATE utf8mb4_general_ci THEN 1 ELSE 0 END) AS conciliados,
                    SUM(CASE WHEN c.estado_conciliacion <> 'Conciliado' COLLATE utf8mb4_general_ci THEN 1 ELSE 0 END) AS descuadres,
                    ROUND(COALESCE(SUM(CASE WHEN c.estado_conciliacion <> 'Conciliado' COLLATE utf8mb4_general_ci THEN ABS(c.diferencia) ELSE 0 END), 0), 2) AS monto_descuadres
                 FROM vw_conciliacion_financiera c"
            );
            $resumen = $resumenStmt->fetch(PDO::FETCH_ASSOC);

            $huerfStmt = $pdo->query("SELECT COUNT(*) AS n FROM vw_transacciones_huerfanas");
            $huerfanas = (int)$huerfStmt->fetch(PDO::FETCH_ASSOC)['n'];

            echo json_encode([
                'rows' => $rows,
                'resumen' => [
                    'total_conciliado' => (float)$resumen['total_conciliado'],
                    'conciliados' => (int)$resumen['conciliados'],
                    'descuadres' => (int)$resumen['descuadres'],
                    'monto_descuadres' => (float)$resumen['monto_descuadres'],
                    'huerfanas' => $huerfanas
                ]
            ]);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error al consultar la conciliación financiera.']);
        }
        exit;

    default:
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
        exit;
}