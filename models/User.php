<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Role.php';

class User
{
    public int $id_usuario;
    public string $nombre;
    public string $apellido;
    public string $email;
    public ?string $telefono;
    public int $id_rol;
    public string $password;

    public ?Role $rol = null;

    public function __construct(
        int $id_usuario,
        string $nombre,
        string $apellido,
        string $email,
        ?string $telefono,
        int $id_rol,
        string $password
    ) {
        $this->id_usuario = $id_usuario;
        $this->nombre = $nombre;
        $this->apellido = $apellido;
        $this->email = $email;
        $this->telefono = $telefono;
        $this->id_rol = $id_rol;
        $this->password = $password;
    }

    public static function findByEmail(string $email): ?self
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $user = new self(
            (int)$row['id_usuario'],
            $row['nombre'],
            $row['apellido'],
            $row['email'],
            $row['telefono'],
            (int)$row['id_rol'],
            $row['password'] ?? ''
        );
        $user->rol = Role::findById($user->id_rol);
        return $user;
    }

    public static function listAll(): array
    {
        $pdo = Database::getConnection();
        return $pdo->query(
            "SELECT id_usuario, nombre, apellido, email, telefono FROM usuarios ORDER BY nombre, apellido"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ranking de fidelidad: usuarios con más alquileres del mes (descendente)
    public static function listarFrecuentes(): array
    {
        $pdo = Database::getConnection();
        return $pdo->query("SELECT * FROM vw_clientes_frecuentes")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function existeEmail(string $email): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id_usuario FROM usuarios WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return (bool)$stmt->fetch();
    }

    public static function crearCliente(string $nombre, string $apellido, string $email, ?string $telefono, string $passwordPlano = 'Bici2026'): int
    {
        $pdo = Database::getConnection();

        $rolStmt = $pdo->prepare("SELECT id_rol FROM roles WHERE codigo_rol = 'CLI' LIMIT 1");
        $rolStmt->execute();
        $idRol = (int)$rolStmt->fetchColumn();

        $stmt = $pdo->prepare(
            "INSERT INTO usuarios (nombre, apellido, email, telefono, id_rol, password, fecha_registro)
             VALUES (?, ?, ?, ?, ?, ?, CURDATE())"
        );
        $stmt->execute([$nombre, $apellido, $email, $telefono, $idRol, password_hash($passwordPlano, PASSWORD_BCRYPT)]);

        return (int)$pdo->lastInsertId();
    }

    public function verificarPassword(string $passwordPlano): bool
    {
        return password_verify($passwordPlano, $this->password);
    }

    public function esAdmin(): bool
    {
        return $this->rol !== null && $this->rol->codigo_rol === ROL_ADMIN;
    }

    public function esUsuario(): bool
    {
        return $this->rol !== null && $this->rol->codigo_rol === ROL_USER;
    }
}
