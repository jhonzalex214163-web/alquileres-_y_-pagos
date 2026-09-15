<?php
// controllers/RutasController.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../models/RutaT.php';

header('Content-Type: application/json');

$controller = new RutasController();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar':
        // Rutas activas para el usuario final (requiere sesión como el resto de APIs)
        $controller->requireAuth();
        $controller->listarRutas();
        break;

    case 'listar_todas':
        $controller->requireAuth();
        $controller->requireAdmin();
        $controller->listarRutas(true);
        break;

    case 'guardar':
        $controller->requireAuth();
        $controller->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
            exit;
        }
        if (!csrf_verify($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido.']);
            exit;
        }
        $controller->guardarRuta();
        break;

    case 'cambiar_estado':
        $controller->requireAuth();
        $controller->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
            exit;
        }
        if (!csrf_verify($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido.']);
            exit;
        }
        $controller->cambiarEstado();
        break;

    default:
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
        break;
}

class RutasController {
    private $modelo;

    public function __construct() {
        $this->modelo = new RutaT();
    }

    public function requireAuth(): void
    {
        if (!AuthController::isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'No autorizado. Inicia sesión.']);
            exit;
        }
    }

    public function requireAdmin(): void
    {
        if (!AuthController::esAdmin()) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Requiere rol administrador.']);
            exit;
        }
    }

    public function listarRutas(bool $todas = false): void
    {
        try {
            $rutas = $todas ? $this->modelo->listarTodas() : $this->modelo->listarActivas();
            echo json_encode(['status' => 'success', 'success' => true, 'rutas' => $rutas]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Error al consultar las rutas turísticas.']);
        }
    }

    public function guardarRuta(): void
    {
        $datos = $_POST;
        $nombre = trim($datos['nombre'] ?? '');
        $descripcion = trim($datos['descripcion'] ?? '');
        $dificultad = in_array($datos['dificultad'] ?? '', ['facil', 'media', 'alta'], true) ? $datos['dificultad'] : 'media';
        $lat = $datos['lat'] ?? '';
        $lng = $datos['lng'] ?? '';

        if ($nombre === '' || $lat === '' || $lng === '' || !is_numeric($lat) || !is_numeric($lng)) {
            echo json_encode(['status' => 'error', 'message' => 'Nombre, latitud y longitud son obligatorios.']);
            return;
        }

        try {
            $id = $this->modelo->crear([
                'nombre'       => $nombre,
                'descripcion'  => $descripcion,
                'dificultad'   => $dificultad,
                'distancia_km' => trim((string)($datos['distancia_km'] ?? '')),
                'duracion_est' => trim((string)($datos['duracion_est'] ?? '')),
                'lat'          => $lat,
                'lng'          => $lng,
                'activa'       => (int)($datos['activa'] ?? 1)
            ]);
            echo json_encode(['status' => 'success', 'success' => true, 'id_ruta' => $id, 'message' => 'Ruta turística registrada.']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'success' => false, 'message' => 'No se pudo registrar la ruta.']);
        }
    }

    public function cambiarEstado(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $id_ruta = (int)($data['id_ruta'] ?? 0);
        $activa = (int)($data['activa'] ?? 1);

        if ($id_ruta <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Ruta no válida.']);
            return;
        }

        try {
            $this->modelo->cambiarEstado($id_ruta, $activa ? 1 : 0);
            echo json_encode(['status' => 'success', 'success' => true, 'message' => $activa ? 'Ruta activada.' : 'Ruta desactivada.']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'success' => false, 'message' => 'No se pudo actualizar la ruta.']);
        }
    }
}