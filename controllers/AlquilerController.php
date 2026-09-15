<?php
// controllers/AlquilerController.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../models/Alquiler.php';
require_once __DIR__ . '/../models/Bicicleta.php';

class AlquilerController {
    private $alquilerModel;
    private $bicicletaModel;

    public function __construct() {
        $this->alquilerModel = new Alquiler();
        $this->bicicletaModel = new Bicicleta();
    }

    // Requiere sesión autenticada para cualquier operación
    private function requireAuth(): void
    {
        if (!AuthController::isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado. Inicia sesión.']);
            exit;
        }
    }

    private function requirePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }
    }

    // Devuelve el id del usuario autenticado desde la sesión (nunca del request)
    private function requireUsuarioSesion(): int
    {
        $id = (int)($_SESSION['user_id'] ?? 0);
        if ($id <= 0) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado. Inicia sesión.']);
            exit;
        }
        return $id;
    }

    // [T-05-03] Endpoint: Iniciar Viaje
    public function iniciar() {
        $this->requireAuth();
        $this->requirePost();

        if (!csrf_verify($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['id_usuario']) || empty($data['id_bicicleta']) || empty($data['estacion_origen'])) {
            echo json_encode(['success' => false, 'message' => 'Faltan parámetros obligatorios.']);
            return;
        }

        try {
            $id = $this->alquilerModel->iniciarViaje($data['id_usuario'], $data['id_bicicleta'], $data['estacion_origen']);
            echo json_encode(['success' => true, 'id_alquiler' => $id, 'message' => 'Viaje iniciado con éxito.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // [T-05-05] Endpoint: Cierre del Viaje
    public function cerrar() {
        $this->requireAuth();
        $this->requirePost();

        if (!csrf_verify($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['id_alquiler']) || empty($data['estacion_destino'])) {
            echo json_encode(['success' => false, 'message' => 'Faltan parámetros obligatorios.']);
            return;
        }

        try {
            $res = $this->alquilerModel->cerrarViaje($data['id_alquiler'], $data['estacion_destino']);
            echo json_encode(['success' => true, 'datos' => $res, 'message' => 'Viaje finalizado con éxito.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // Conforma la alerta de mantenimiento/batería para el contenedor "Alerta de Mantenimiento"
    private function armarAlerta(array $info): string
    {
        $partes = [];
        if (!empty($info['proxima_mantenimiento'])) {
            $partes[] = '⚠️ Próxima a mantenimiento: revisar ' . $info['num_serie'] . '.';
        }
        if ((int)($info['bateria_actual'] ?? 100) < 15) {
            $partes[] = '⚠️ Batería crítica, busca una estación cercana.';
        }
        return $partes ? implode('  ', $partes) : 'Normal (Sin Alertas)';
    }

    // [T-05-08] Endpoint: Monitoreo en Tiempo Real (viaje activo: adquirido vs actual/entrega)
    public function monitorear($id_bicicleta = 0) {
        $this->requireAuth();

        try {
            $info = $this->alquilerModel->obtenerMonitoreoActivo();
            if (!$info) {
                echo json_encode(['success' => false, 'message' => 'No hay viajes para monitorear.']);
                return;
            }

            $alerta = $this->armarAlerta($info);

            echo json_encode([
                'success' => true,
                'telemetria' => $info,
                'alerta' => $alerta,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // Catálogo inicial para la gestión de viajes (usuarios, bicicletas, estaciones, viajes activos)
    public function datosIniciales() {
        $this->requireAuth();
        echo json_encode($this->alquilerModel->listarCatalogo());
    }

    // Rutas recorridas por un usuario para el panel de monitoreo
    public function rutasUsuario($id_usuario) {
        $this->requireAuth();

        if ($id_usuario <= 0) {
            echo json_encode(['success' => false, 'message' => 'Usuario no válido.']);
            return;
        }

        try {
            $rutas = $this->alquilerModel->rutasPorUsuario($id_usuario);
            echo json_encode(['success' => true, 'rutas' => $rutas]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // [T-05-08] Batería en tiempo real de la bicicleta de un usuario (al ser usada)
    public function bateriaUsuario($id_usuario) {
        $this->requireAuth();

        if ($id_usuario <= 0) {
            echo json_encode(['success' => false, 'message' => 'Usuario no válido.']);
            return;
        }

        try {
            $info = $this->alquilerModel->obtenerMonitoreoActivo($id_usuario);
            if (!$info) {
                echo json_encode(['success' => false, 'message' => 'El usuario aún no registra viajes.']);
                return;
            }

            $alerta = $this->armarAlerta($info);

            echo json_encode([
                'success' => true,
                'telemetria' => $info,
                'alerta' => $alerta,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ================= Portal del usuario final =================

    // Catálogo para el portal del usuario logueado (bicis, estaciones, su viaje activo)
    public function catalogoUsuario() {
        $this->requireAuth();
        $idUsuario = $this->requireUsuarioSesion();
        try {
            echo json_encode(['success' => true, 'catalogo' => $this->alquilerModel->listarCatalogoUsuario($idUsuario)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // Mis recibos: alquileres del usuario logueado (perfil del usuario / imprimibles)
    public function misRecibos() {
        $this->requireAuth();
        $idUsuario = $this->requireUsuarioSesion();
        try {
            echo json_encode(['success' => true, 'recibos' => $this->alquilerModel->listarRecibosUsuario($idUsuario)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // Datos de un recibo concreto (valida que el alquiler sea del usuario en sesión)
    public function recibo($id_alquiler) {
        $this->requireAuth();
        $idUsuario = $this->requireUsuarioSesion();

        if ($id_alquiler <= 0) {
            echo json_encode(['success' => false, 'message' => 'Alquiler no válido.']);
            return;
        }

        try {
            $recibo = $this->alquilerModel->obtenerRecibo($id_alquiler, $idUsuario);
            if (!$recibo) {
                echo json_encode(['success' => false, 'message' => 'Recibo no encontrado o no te pertenece.']);
                return;
            }
            echo json_encode(['success' => true, 'recibo' => $recibo]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // Estaciones con coordenadas para el mapa interactivo del usuario
    public function estacionesMapa() {
        $this->requireAuth();
        try {
            echo json_encode(['success' => true, 'estaciones' => $this->alquilerModel->listarEstacionesConCoordenadas()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // Iniciar viaje para el usuario logueado (el id_usuario sale de la sesión, no del request)
    public function iniciarUsuario() {
        $this->requireAuth();
        $this->requirePost();
        if (!csrf_verify($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']);
            return;
        }

        $idUsuario = $this->requireUsuarioSesion();
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['id_bicicleta']) || empty($data['estacion_origen'])) {
            echo json_encode(['success' => false, 'message' => 'Faltan parámetros obligatorios.']);
            return;
        }

        try {
            $id = $this->alquilerModel->iniciarViaje($idUsuario, $data['id_bicicleta'], $data['estacion_origen']);
            echo json_encode(['success' => true, 'id_alquiler' => $id, 'message' => 'Viaje iniciado con éxito.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // Cerrar viaje de una de las bicicletas del usuario logueado
    public function cerrarUsuario() {
        $this->requireAuth();
        $this->requirePost();
        if (!csrf_verify($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']);
            return;
        }

        $idUsuario = $this->requireUsuarioSesion();
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['id_alquiler']) || empty($data['estacion_destino'])) {
            echo json_encode(['success' => false, 'message' => 'Faltan parámetros obligatorios.']);
            return;
        }

        try {
            $res = $this->alquilerModel->cerrarViaje($data['id_alquiler'], $data['estacion_destino'], $idUsuario);
            echo json_encode(['success' => true, 'datos' => $res, 'message' => 'Viaje finalizado con éxito.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

// ============================================================
// Dispatcher: /bicicletas-compartidas/controllers/AlquilerController.php?action=...
// ============================================================
header('Content-Type: application/json');
$controller = new AlquilerController();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'iniciar':
        $controller->iniciar();
        break;
    case 'cerrar':
        $controller->cerrar();
        break;
    case 'monitorear':
        $controller->monitorear((int)($_GET['id_bicicleta'] ?? 0));
        break;
    case 'datos_iniciales':
        $controller->datosIniciales();
        break;
    case 'rutas_usuario':
        $controller->rutasUsuario((int)($_GET['id_usuario'] ?? 0));
        break;
    case 'bateria_usuario':
        $controller->bateriaUsuario((int)($_GET['id_usuario'] ?? 0));
        break;
    case 'catalogo_usuario':
        $controller->catalogoUsuario();
        break;
    case 'mis_recibos':
        $controller->misRecibos();
        break;
    case 'recibo':
        $controller->recibo((int)($_GET['id_alquiler'] ?? 0));
        break;
    case 'estaciones_mapa':
        $controller->estacionesMapa();
        break;
    case 'iniciar_usuario':
        $controller->iniciarUsuario();
        break;
    case 'cerrar_usuario':
        $controller->cerrarUsuario();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
        break;
}