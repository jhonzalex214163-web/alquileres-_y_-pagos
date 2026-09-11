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
