<?php
// models/Bicicleta.php
require_once __DIR__ . '/../config/database.php';

class Bicicleta {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // [T-05-04] Actualizar estado de bicicleta
    public function actualizarEstado($id_bicicleta, $estado) {
        $query = "UPDATE bicicletas SET estado = :estado WHERE id_bicicleta = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':estado' => $estado, ':id' => $id_bicicleta]);
    }

    // [T-05-08] Monitoreo en tiempo real
    public function obtenerEstadoDetallado($id_bicicleta) {
        $query = "SELECT id_bicicleta, modelo, num_serie, nivel_bateria, estado, kilometraje, id_estacion 
                  FROM bicicletas WHERE id_bicicleta = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id_bicicleta]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}