<?php
// controllers/AlquilerController.php
header('Content-Type: application/json');
require_once __DIR__ . '/../models/Alquiler.php';
require_once __DIR__ . '/../models/Bicicleta.php';

class AlquilerController {
    private $alquilerModel;
    private $bicicletaModel;

    public function __construct() {
        $this->alquilerModel = new Alquiler();
        $this->bicicletaModel = new Bicicleta();
    }

    // [T-05-03] Endpoint: Iniciar Viaje
    public function iniciar() {
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

    // [T-05-08] Endpoint: Monitoreo en Tiempo Real
    public function monitorear($id_bicicleta) {
        try {
            $info = $this->bicicletaModel->obtenerEstadoDetallado($id_bicicleta);
            $alerta_mantenimiento = ($info['nivel_bateria'] < 15) ? 'Batería Crítica' : 'Normal';

            echo json_encode([
                'success' => true,
                'telemetria' => $info,
                'alerta' => $alerta_mantenimiento,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}