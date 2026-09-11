<?php
require_once __DIR__ . '/../config/database.php';

class Role
{
    public int $id_rol;
    public string $codigo_rol;
    public string $nombre_rol;
    public int $nivel_acceso;

    public function __construct(int $id_rol, string $codigo_rol, string $nombre_rol, int $nivel_acceso)
    {
        $this->id_rol = $id_rol;
        $this->codigo_rol = $codigo_rol;
        $this->nombre_rol = $nombre_rol;
        $this->nivel_acceso = $nivel_acceso;
    }

    public static function findById(int $id_rol): ?self
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM roles WHERE id_rol = ?');
        $stmt->execute([$id_rol]);
        $row = $stmt->fetch();
        if (!$row) return null;
        return new self(
            (int)$row['id_rol'],
            $row['codigo_rol'],
            $row['nombre_rol'],
            (int)$row['nivel_acceso']
        );
    }
}
