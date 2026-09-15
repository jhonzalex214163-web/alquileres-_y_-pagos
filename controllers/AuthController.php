<?php
require_once __DIR__ . '/../models/User.php';

class AuthController
{
    public static function login(string $email, string $password): ?User
    {
        $user = User::findByEmail($email);
        if ($user === null || !$user->verificarPassword($password)) {
            return null;
        }

        // Prevención de session fixation
        session_regenerate_id(true);

        $rolCodigo = $user->rol ? $user->rol->codigo_rol : null;

        $_SESSION['user_id'] = $user->id_usuario;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_nombre'] = $user->nombre . ' ' . $user->apellido;
        $_SESSION['user_rol'] = $rolCodigo;
        $_SESSION['user_rol_nombre'] = $user->rol ? $user->rol->nombre_rol : null;
        $_SESSION['user_nivel'] = $user->rol ? $user->rol->nivel_acceso : 0;

        // Claves compatibles con los paneles standalone (index2.php / usuario.php)
        // Se guardan en minúsculas para que los compare igual que antes.
        $_SESSION['usuario_email'] = $user->email;
        $_SESSION['usuario_nombre'] = $user->nombre . ' ' . $user->apellido;
        $_SESSION['usuario_rol'] = $rolCodigo ? strtolower($rolCodigo) : null;
        $_SESSION['usuario_rol_nombre'] = $user->rol ? $user->rol->nombre_rol : null;

        return $user;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']);
        }
        session_unset();
        session_destroy();
    }

    public static function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']) && $_SESSION['user_id'] !== null;
    }

    public static function requireLogin(): ?User
    {
        if (!self::isAuthenticated()) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }
        return self::getUserFromSession();
    }

    public static function getUserFromSession(): ?User
    {
        if (!self::isAuthenticated()) return null;
        $user = User::findByEmail($_SESSION['user_email']);
        return $user;
    }

    public static function getRol(): ?string
    {
        return $_SESSION['user_rol'] ?? null;
    }

    public static function getRolNombre(): ?string
    {
        return $_SESSION['user_rol_nombre'] ?? null;
    }

    /** Es admin si el rol en sesión es ADMIN (case-insensitive). */
    public static function esAdmin(): bool
    {
        return strcasecmp((string)self::getRol(), ROL_ADMIN) === 0;
    }
}