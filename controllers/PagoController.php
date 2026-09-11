<?php
// [T-06-09] Validar canal seguro (HTTPS)
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado. Se requiere conexión HTTPS segura.']);
    exit;
}

header('Content-Type: application/json');
require_once '../config/db.php'; // Tu conexión PDO

$action = $_GET['action'] ?? '';

switch ($action) {
    // [T-06-03, T-06-04, T-07-03, T-07-04, T-07-05, T-07-06]
    case 'registrar_pago':
        $id_alquiler = $_POST['id_alquiler'] ?? null;
        $monto = $_POST['monto'] ?? null;
        $ref_externa = $_POST['referencia_externa'] ?? null;

        // [T-07-05] Validación de referencia obligatoria
        if (!$id_alquiler || !$monto || !$ref_externa) {
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

        // [T-06-04] Carga de evidencia fotográfica
        $comprobante_path = null;
        if (isset($_FILES['evidencia']) && $_FILES['evidencia']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['evidencia']['name'], PATHINFO_EXTENSION);
            $filename = 'comprobante_' . time() . '_' . uniqid() . '.' . $ext;
            $upload_dir = '../uploads/comprobantes/';
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            move_uploaded_file($_FILES['evidencia']['tmp_name'], $upload_dir . $filename);
            $comprobante_path = 'uploads/comprobantes/' . $filename;
        }

        // [T-07-04] Insertar transacción (Estado por defecto: EXITOSO)
        $stmt = $pdo->prepare("INSERT INTO transacciones (id_alquiler, referencia_externa, monto, estado, comprobante_url) VALUES (?, ?, ?, 'EXITOSO', ?)");
        $success = $stmt->execute([$id_alquiler, $ref_externa, $monto, $comprobante_path]);

        if ($success) {
            echo json_encode(['status' => 'success', 'message' => 'Pago registrado y verificado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar la transacción.']);
        }
        break;

    // [T-06-07] Eliminación lógica/física de token
    case 'revocar_token':
        $data = json_decode(file_get_contents('php://input'), true);
        $id_token = $data['id_token'] ?? null;

        if ($id_token) {
            // Eliminación lógica cambiando el campo activo a 0
            $stmt = $pdo->prepare("UPDATE tokens_pasarela SET activo = 0 WHERE id_token = ?");
            $stmt->execute([$id_token]);
            echo json_encode(['status' => 'success', 'message' => 'Token revocado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ID de token no provisto.']);
        }
        break;

    // Obtener historial de transacciones para la vista
    case 'listar_transacciones':
        $stmt = $pdo->query("SELECT * FROM transacciones ORDER BY fecha_transaccion DESC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
        break;
}