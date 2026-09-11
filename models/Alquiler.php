<?php
// models/Alquiler.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Bicicleta.php';

class Alquiler {
    private $conn;
    private $bicicletaModel;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->bicicletaModel = new Bicicleta();
    }

    // [T-05-03] & [T-05-04] Iniciar viaje y cambiar estado a 'alquilada'
    public function iniciarViaje($id_usuario, $id_bicicleta, $estacion_origen) {
        // Verificar si la bicicleta está disponible
        $bici = $this->bicicletaModel->obtenerEstadoDetallado($id_bicicleta);
        if (!$bici || $bici['estado'] !== 'Disponible') {
            throw new Exception("La bicicleta no está disponible para su alquiler.");
        }

        $this->conn->beginTransaction();
        try {
            // Crear registro de alquiler
            $query = "INSERT INTO alquileres (id_usuario, id_bicicleta, estacion_origen, fecha_inicio, estado) 
                      VALUES (:id_usuario, :id_bicicleta, :estacion_origen, NOW(), 'en_curso')";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':id_usuario' => $id_usuario,
                ':id_bicicleta' => $id_bicicleta,
                ':estacion_origen' => $estacion_origen
            ]);

            $id_alquiler = $this->conn->lastInsertId();

            // [T-05-04] Actualizar estado bicicleta -> 'alquilada'
            $this->bicicletaModel->actualizarEstado($id_bicicleta, 'Alquilada');

            $this->conn->commit();
            return $id_alquiler;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    // [T-05-05], [T-05-06] & [T-05-07] Cierre del viaje y cálculo de costo
    public function cerrarViaje($id_alquiler, $estacion_destino) {
        // [T-05-07] Validar estación destino al cierre
        $stmtEst = $this->conn->prepare("SELECT id_estacion, estado FROM estaciones WHERE id_estacion = :est");
        $stmtEst->execute([':est' => $estacion_destino]);
        $estacion = $stmtEst->fetch(PDO::FETCH_ASSOC);

        if (!$estacion || $estacion['estado'] !== 'operativa') {
            throw new Exception("La estación de destino seleccionada no está operativa o no existe.");
        }

        // Obtener alquiler
        $stmtAlq = $this->conn->prepare("SELECT * FROM alquileres WHERE id_alquiler = :id AND estado = 'en_curso'");
        $stmtAlq->execute([':id' => $id_alquiler]);
        $alquiler = $stmtAlq->fetch(PDO::FETCH_ASSOC);

        if (!$alquiler) {
            throw new Exception("El viaje no existe o ya ha sido finalizado.");
        }

        $this->conn->beginTransaction();
        try {
            // [T-05-06] Calcular tiempo transcurrido y tarifa
            $fecha_inicio = new DateTime($alquiler['fecha_inicio']);
            $fecha_fin = new DateTime();
            $diferencia = $fecha_inicio->diff($fecha_fin);
            $minutos = max(1, ($diferencia->days * 24 * 60) + ($diferencia->h * 60) + $diferencia->i);

            // Obtener precio por minuto estándar (Tarifa id=1)
            $stmtTarifa = $this->conn->query("SELECT precio_por_minuto FROM tarifas LIMIT 1");
            $tarifa = $stmtTarifa->fetch(PDO::FETCH_ASSOC);
            $precio_minuto = $tarifa ? $tarifa['precio_por_minuto'] : 0.50;

            $costo_total = $minutos * $precio_minuto;

            // Actualizar registro de alquiler
            $queryUpd = "UPDATE alquileres 
                         SET estacion_destino = :estacion_destino, 
                             fecha_fin = NOW(), 
                             minutos_totales = :minutos, 
                             costo_total = :costo, 
                             estado = 'finalizado' 
                         WHERE id_alquiler = :id";
            $stmtUpd = $this->conn->prepare($queryUpd);
            $stmtUpd->execute([
                ':estacion_destino' => $estacion_destino,
                ':minutos' => $minutos,
                ':costo' => $costo_total,
                ':id' => $id_alquiler
            ]);

            // Liberar bicicleta -> 'Disponible' y asignar a nueva estación
            $stmtBici = $this->conn->prepare("UPDATE bicicletas SET estado = 'Disponible', id_estacion = :est WHERE id_bicicleta = :id_bici");
            $stmtBici->execute([
                ':est' => $estacion_destino,
                ':id_bici' => $alquiler['id_bicicleta']
            ]);

            $this->conn->commit();
            return [
                'minutos' => $minutos,
                'costo_total' => $costo_total,
                'fecha_fin' => $fecha_fin->format('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}