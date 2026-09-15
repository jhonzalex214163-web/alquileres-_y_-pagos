<?php
// models/RutaT.php
require_once __DIR__ . '/../config/database.php';

class RutaT {
    private $conn;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    // Rutas turísticas activas (para el usuario final)
    public function listarActivas(): array
    {
        $stmt = $this->conn->query(
            "SELECT id_ruta, nombre, descripcion, dificultad, distancia_km, duracion_est,
                    lat, lng
             FROM rutas_turisticas
             WHERE activa = 1
             ORDER BY id_ruta"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Todas las rutas (admin)
    public function listarTodas(): array
    {
        $stmt = $this->conn->query(
            "SELECT id_ruta, nombre, descripcion, dificultad, distancia_km, duracion_est,
                    lat, lng, activa
             FROM rutas_turisticas
             ORDER BY id_ruta"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Crear una nueva ruta turística (admin)
    public function crear(array $datos): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO rutas_turisticas (nombre, descripcion, dificultad, distancia_km, duracion_est, lat, lng, activa)
             VALUES (:nombre, :descripcion, :dificultad, :distancia_km, :duracion_est, :lat, :lng, :activa)"
        );
        $stmt->execute([
            ':nombre'       => $datos['nombre'],
            ':descripcion'  => $datos['descripcion'],
            ':dificultad'   => $datos['dificultad'],
            ':distancia_km' => $datos['distancia_km'] !== '' ? $datos['distancia_km'] : null,
            ':duracion_est' => $datos['duracion_est'] !== '' ? $datos['duracion_est'] : null,
            ':lat'          => $datos['lat'],
            ':lng'          => $datos['lng'],
            ':activa'       => (int)($datos['activa'] ?? 1)
        ]);
        return (int)$this->conn->lastInsertId();
    }

    // Activar / desactivar ruta (admin)
    public function cambiarEstado(int $id_ruta, int $activa): bool
    {
        $stmt = $this->conn->prepare("UPDATE rutas_turisticas SET activa = :activa WHERE id_ruta = :id");
        return $stmt->execute([':activa' => $activa, ':id' => $id_ruta]);
    }
}