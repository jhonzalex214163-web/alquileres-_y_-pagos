<?php
// models/Alquiler.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Bicicleta.php';

class Alquiler {
    private $conn;
    private $bicicletaModel;

    public function __construct() {
        // Conexión singleton (sin instanciar Database)
        $this->conn = Database::getConnection();
        $this->bicicletaModel = new Bicicleta();
    }

    // Catálogo para la interfaz de gestión de viajes (usuarios, bicis, estaciones y viajes activos)
    public function listarCatalogo(): array
    {
        $usuarios = $this->conn->query(
            "SELECT id_usuario, nombre, apellido, email, telefono FROM usuarios ORDER BY nombre, apellido"
        )->fetchAll(PDO::FETCH_ASSOC);

        $bicicletas = $this->conn->query(
            "SELECT b.id_bicicleta, b.modelo, b.num_serie, b.nivel_bateria, b.estado,
                    COALESCE(e.nombre, 'Sin estación') AS estacion_nombre
             FROM bicicletas b
             LEFT JOIN estaciones e ON e.id_estacion = b.id_estacion
             ORDER BY b.id_bicicleta"
        )->fetchAll(PDO::FETCH_ASSOC);

        $estaciones = $this->conn->query(
            "SELECT id_estacion, codigo, nombre, estado FROM estaciones ORDER BY codigo"
        )->fetchAll(PDO::FETCH_ASSOC);

        $viajes = $this->conn->query(
            "SELECT a.id_alquiler, a.id_bicicleta, a.fecha_inicio,
                    CONCAT(u.nombre, ' ', u.apellido) AS cliente,
                    b.num_serie
             FROM alquileres a
             JOIN usuarios u ON u.id_usuario = a.id_usuario
             JOIN bicicletas b ON b.id_bicicleta = a.id_bicicleta
             WHERE a.estado = 'en_curso'
             ORDER BY a.id_alquiler"
        )->fetchAll(PDO::FETCH_ASSOC);

        return [
            'usuarios'   => $usuarios,
            'bicicletas' => $bicicletas,
            'estaciones' => $estaciones,
            'viajes'     => $viajes
        ];
    }

    // Catálogo para el portal del usuario final (bicicletas, estaciones y sus viajes activos)
    public function listarCatalogoUsuario(int $id_usuario): array
    {
        $bicicletas = $this->conn->query(
            "SELECT b.id_bicicleta, b.modelo, b.num_serie, b.nivel_bateria, b.estado,
                    COALESCE(e.nombre, 'Sin estación') AS estacion_nombre
             FROM bicicletas b
             LEFT JOIN estaciones e ON e.id_estacion = b.id_estacion
             ORDER BY b.id_bicicleta"
        )->fetchAll(PDO::FETCH_ASSOC);

        $estaciones = $this->conn->query(
            "SELECT id_estacion, codigo, nombre, estado FROM estaciones ORDER BY codigo"
        )->fetchAll(PDO::FETCH_ASSOC);

        $stmtViajes = $this->conn->prepare(
            "SELECT a.id_alquiler, a.id_bicicleta, a.fecha_inicio,
                    b.num_serie, e.nombre AS estacion_nombre
             FROM alquileres a
             JOIN bicicletas b ON b.id_bicicleta = a.id_bicicleta
             LEFT JOIN estaciones e ON e.id_estacion = a.estacion_origen
             WHERE a.estado = 'en_curso' AND a.id_usuario = :id_usuario
             ORDER BY a.id_alquiler"
        );
        $stmtViajes->execute([':id_usuario' => $id_usuario]);
        $viajes = $stmtViajes->fetchAll(PDO::FETCH_ASSOC);

        $tarifa = $this->conn->query("SELECT precio_por_minuto FROM tarifas ORDER BY id_tarifa LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        return [
            'bicicletas'  => $bicicletas,
            'estaciones'  => $estaciones,
            'viajes'      => $viajes,
            'tarifa'      => $tarifa ? (float)$tarifa['precio_por_minuto'] : 0.50
        ];
    }

    // Recibos de alquiler desde el perfil del usuario (solo sus viajes)
    public function listarRecibosUsuario(int $id_usuario): array
    {
        $stmt = $this->conn->prepare(
            "SELECT a.id_alquiler, a.fecha_inicio, a.fecha_fin, a.minutos_totales,
                    a.costo_total, a.estado, a.bateria_inicio, a.bateria_fin,
                    b.modelo AS bici_modelo, b.num_serie,
                    eo.nombre AS estacion_origen,
                    COALESCE(ed.nombre, '—') AS estacion_destino,
                    COALESCE((SELECT SUM(t.valor)
                              FROM transacciones t
                              WHERE t.id_alquiler = a.id_alquiler
                                AND t.estado IN ('EXITOSO','APROBADO')), 0) AS total_pagado
             FROM alquileres a
             JOIN bicicletas b ON b.id_bicicleta = a.id_bicicleta
             JOIN estaciones eo ON eo.id_estacion = a.estacion_origen
             LEFT JOIN estaciones ed ON ed.id_estacion = a.estacion_destino
             WHERE a.id_usuario = :id_usuario
             ORDER BY a.fecha_inicio DESC"
        );
        $stmt->execute([':id_usuario' => $id_usuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Datos completos para el recibo imprimible de un viaje (valida propietario)
    public function obtenerRecibo(int $id_alquiler, int $id_usuario = 0): ?array
    {
        $filtro = $id_usuario > 0 ? " AND a.id_usuario = " . (int)$id_usuario : "";
        $stmt = $this->conn->query(
            "SELECT a.id_alquiler, a.id_usuario, a.fecha_inicio, a.fecha_fin, a.minutos_totales,
                    a.costo_total, a.estado, a.bateria_inicio, a.bateria_fin,
                    CONCAT(u.nombre, ' ', u.apellido) AS cliente, u.email, u.telefono,
                    b.modelo AS bici_modelo, b.num_serie, b.nivel_bateria,
                    eo.nombre AS estacion_origen,
                    COALESCE(ed.nombre, '—') AS estacion_destino,
                    COALESCE((SELECT SUM(t.valor)
                              FROM transacciones t
                              WHERE t.id_alquiler = a.id_alquiler
                                AND t.estado IN ('EXITOSO','APROBADO')), 0) AS total_pagado
             FROM alquileres a
             JOIN usuarios u ON u.id_usuario = a.id_usuario
             JOIN bicicletas b ON b.id_bicicleta = a.id_bicicleta
             JOIN estaciones eo ON eo.id_estacion = a.estacion_origen
             LEFT JOIN estaciones ed ON ed.id_estacion = a.estacion_destino
             WHERE a.id_alquiler = " . (int)$id_alquiler . $filtro
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // Estaciones con coordenadas para el mapa interactivo (puntos de carga y retiro)
    public function listarEstacionesConCoordenadas(): array
    {
        $stmt = $this->conn->query(
            "SELECT id_estacion, codigo, nombre, direccion,
                    capacidad, energia_disp, estado,
                    ST_Y(coordenadas) AS lat, ST_X(coordenadas) AS lng
             FROM estaciones
             ORDER BY codigo"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Rutas recorridas por un usuario: trazo entre estaciones, duración y monto pagado
    public function rutasPorUsuario($id_usuario): array
    {
        $stmt = $this->conn->prepare(
            "SELECT a.id_alquiler,
                    CONCAT(u.nombre, ' ', u.apellido) AS cliente,
                    a.fecha_inicio, a.fecha_fin, a.minutos_totales, a.costo_total, a.estado,
                    eo.id_estacion AS origen_id, eo.nombre AS origen_nombre,
                    ST_Y(eo.coordenadas) AS origen_lat, ST_X(eo.coordenadas) AS origen_lng,
                    ed.id_estacion AS destino_id, ed.nombre AS destino_nombre,
                    ST_Y(ed.coordenadas) AS destino_lat, ST_X(ed.coordenadas) AS destino_lng,
                    COALESCE((SELECT SUM(t.valor) FROM transacciones t WHERE t.id_alquiler = a.id_alquiler), 0) AS total_pagado
             FROM alquileres a
             JOIN usuarios u ON u.id_usuario = a.id_usuario
             JOIN estaciones eo ON eo.id_estacion = a.estacion_origen
             LEFT JOIN estaciones ed ON ed.id_estacion = a.estacion_destino
             WHERE a.id_usuario = :id_usuario
             ORDER BY a.fecha_inicio ASC"
        );
        $stmt->execute([':id_usuario' => $id_usuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // [T-05-03] & [T-05-04] Iniciar viaje y cambiar estado a 'alquilada'
    public function iniciarViaje($id_usuario, $id_bicicleta, $estacion_origen) {
        // Verificar si la bicicleta está disponible (estados reales en minúsculas)
        $bici = $this->bicicletaModel->obtenerEstadoDetallado($id_bicicleta);
        if (!$bici || strtolower((string)$bici['estado']) !== 'disponible') {
            throw new Exception("La bicicleta no está disponible para su alquiler.");
        }

        $this->conn->beginTransaction();
        try {
            // Registrar el nivel de batería con el que se adquiere la bicicleta
            $bateria_inicio = isset($bici['nivel_bateria']) && $bici['nivel_bateria'] !== null
                ? (int)$bici['nivel_bateria']
                : null;

            // Crear registro de alquiler
            $query = "INSERT INTO alquileres (id_usuario, id_bicicleta, estacion_origen, fecha_inicio, estado, bateria_inicio) 
                      VALUES (:id_usuario, :id_bicicleta, :estacion_origen, NOW(), 'en_curso', :bateria_inicio)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':id_usuario' => $id_usuario,
                ':id_bicicleta' => $id_bicicleta,
                ':estacion_origen' => $estacion_origen,
                ':bateria_inicio' => $bateria_inicio
            ]);

            $id_alquiler = $this->conn->lastInsertId();

            // [T-05-04] Actualizar estado bicicleta -> 'alquilada'
            $this->bicicletaModel->actualizarEstado($id_bicicleta, 'alquilada');

            $this->conn->commit();
            return $id_alquiler;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    // [T-05-05], [T-05-06] & [T-05-07] Cierre del viaje y cálculo de costo
    // $id_usuario_opcional: si viene definido, solo permite cerrar el viaje de ese
    // usuario (evita que un cliente cierre el viaje de otro).
    public function cerrarViaje($id_alquiler, $estacion_destino, $id_usuario_opcional = null) {
        // [T-05-07] Validar estación destino al cierre
        $stmtEst = $this->conn->prepare("SELECT id_estacion, estado FROM estaciones WHERE id_estacion = :est");
        $stmtEst->execute([':est' => $estacion_destino]);
        $estacion = $stmtEst->fetch(PDO::FETCH_ASSOC);

        if (!$estacion || strtolower((string)$estacion['estado']) !== 'operativa') {
            throw new Exception("La estación de destino seleccionada no está operativa o no existe.");
        }

        // Obtener alquiler
        $stmtAlq = $this->conn->prepare("SELECT * FROM alquileres WHERE id_alquiler = :id AND estado = 'en_curso'");
        $stmtAlq->execute([':id' => $id_alquiler]);
        $alquiler = $stmtAlq->fetch(PDO::FETCH_ASSOC);

        if (!$alquiler) {
            throw new Exception("El viaje no existe o ya ha sido finalizado.");
        }

        // [Autorización] Un cliente solo puede cerrar sus propios viajes
        if ($id_usuario_opcional !== null && (int)$alquiler['id_usuario'] !== (int)$id_usuario_opcional) {
            throw new Exception("Este viaje pertenece a otro usuario. No se puede cerrar.");
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
            $precio_minuto = $tarifa ? (float)$tarifa['precio_por_minuto'] : 0.50;

            $costo_total = round($minutos * $precio_minuto, 2);

            // [Batería de entrega] Descuento simulado por uso: ~4% cada hora
            $bateria_inicio = isset($alquiler['bateria_inicio']) && $alquiler['bateria_inicio'] !== null
                ? (int)$alquiler['bateria_inicio']
                : null;
            $bateria_fin = $bateria_inicio !== null
                ? max(0, $bateria_inicio - (int)ceil($minutos / 15))
                : null;

            // Actualizar registro de alquiler
            $queryUpd = "UPDATE alquileres 
                         SET estacion_destino = :estacion_destino, 
                             fecha_fin = NOW(), 
                             minutos_totales = :minutos, 
                             costo_total = :costo, 
                             bateria_fin = :bateria_fin,
                             estado = 'finalizado' 
                         WHERE id_alquiler = :id";
            $stmtUpd = $this->conn->prepare($queryUpd);
            $stmtUpd->execute([
                ':estacion_destino' => $estacion_destino,
                ':minutos' => $minutos,
                ':costo' => $costo_total,
                ':bateria_fin' => $bateria_fin,
                ':id' => $id_alquiler
            ]);

            // Liberar bicicleta -> 'disponible', asignar a nueva estación y reflejar batería de entrega
            $stmtBici = $this->conn->prepare("UPDATE bicicletas SET estado = 'disponible', id_estacion = :est, nivel_bateria = COALESCE(:bateria, nivel_bateria) WHERE id_bicicleta = :id_bici");
            $stmtBici->execute([
                ':est' => $estacion_destino,
                ':bateria' => $bateria_fin,
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

    // [T-05-08] Monitoreo inteligente: viaje activo más reciente (o último finalizado)
    // con batería y estado al adquirir la bicicleta vs el estado actual/de entrega.
    // Si $id_usuario viene definido, se filtra al viaje de ese usuario (batería de su bicicleta en tiempo real).
    public function obtenerMonitoreoActivo(?int $id_usuario = null): ?array
    {
        $filtro = $id_usuario !== null
            ? "WHERE a.id_usuario = " . (int)$id_usuario . " "
            : "";

        $row = $this->conn->query(
            "SELECT a.id_alquiler, a.fecha_inicio, a.fecha_fin, a.minutos_totales,
                    a.bateria_inicio, a.bateria_fin, a.estado,
                    CONCAT(u.nombre, ' ', u.apellido) AS cliente,
                    b.id_bicicleta, b.num_serie, b.modelo, b.nivel_bateria AS bateria_bd, b.estado AS estado_bici,
                    b.kilometraje,
                    eo.nombre AS estacion_retiro, COALESCE(ed.nombre, '') AS estacion_destino
             FROM alquileres a
             JOIN usuarios u ON u.id_usuario = a.id_usuario
             JOIN bicicletas b ON b.id_bicicleta = a.id_bicicleta
             LEFT JOIN estaciones eo ON eo.id_estacion = a.estacion_origen
             LEFT JOIN estaciones ed ON ed.id_estacion = a.estacion_destino
             {$filtro}
             ORDER BY (a.estado = 'en_curso') DESC, a.fecha_inicio DESC
             LIMIT 1"
        )->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $activo = ($row['estado'] === 'en_curso');
        $elapsedMin = $activo
            ? max(0, (int)round(((new DateTime('now'))->getTimestamp() - (new DateTime($row['fecha_inicio']))->getTimestamp()) / 60))
            : (int)($row['minutos_totales'] ?? 0);

        $bateriaInicio = ($row['bateria_inicio'] !== null && $row['bateria_inicio'] !== '')
            ? (int)$row['bateria_inicio']
            : (int)($row['bateria_bd'] ?? 80);

        // Desgaste simulado ~4% por hora transcurrida durante el viaje
        $bateriaActual = $activo
            ? max(0, $bateriaInicio - (int)ceil($elapsedMin / 15))
            : (int)($row['bateria_fin'] ?? $bateriaInicio);

        // Estado de la bicicleta en tiempo real:
        //   mantenimiento  -> En Mantenimiento
        //   viaje en curso -> En Ruta
        //   km >= 500 o bateria < 30% -> Próxima a Mantenimiento
        //   resto          -> Disponible
        $kilometraje = (float)($row['kilometraje'] ?? 0);
        $enMantenimiento = strtolower((string)($row['estado_bici'] ?? '')) === 'mantenimiento';
        $proximaMantenimiento = !$enMantenimiento && ($kilometraje >= 500 || $bateriaActual < 30);

        $estadoActual = $activo
            ? 'En Ruta'
            : ($enMantenimiento
                ? 'En Mantenimiento'
                : ($proximaMantenimiento ? 'Próxima a Mantenimiento' : 'Disponible'));

        return [
            'id_alquiler' => (int)$row['id_alquiler'],
            'cliente' => $row['cliente'],
            'id_bicicleta' => (int)$row['id_bicicleta'],
            'num_serie' => $row['num_serie'],
            'modelo' => $row['modelo'],
            'bateria_inicio' => $bateriaInicio,
            'bateria_actual' => $bateriaActual,
            'estado_inicio' => 'Disponible',
            'estado_actual' => $estadoActual,
            'proxima_mantenimiento' => $proximaMantenimiento,
            'en_mantenimiento' => $enMantenimiento,
            'kilometraje' => $kilometraje,
            'estacion_retiro' => $row['estacion_retiro'],
            'estacion_destino' => $row['estacion_destino'],
            'viaje_activo' => $activo,
            'minutos_transcurridos' => $elapsedMin
        ];
    }
}