<?php
// Constantes globales de BiciJardín
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'bicicletas_compartidas');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Entorno de ejecución: 'dev' (local, sin HTTPS obligatorio) o 'prod'
define('APP_ENV', 'dev');

define('BASE_URL', '/bicicletas-compartidas/');

// Zona horaria de la BD y del negocio (evita desfases al comparar NOW() con PHP)
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('America/Bogota');
}

// Códigos de rol reales en la base de datos
// (roles: ADMIN, OPER, MANT, CLI, SUP — ver migración 004)
define('ROL_ADMIN', 'ADMIN');
define('ROL_USER', 'CLI');

// Sesión única para toda la aplicación
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => APP_ENV === 'prod',
    ]);
    session_start();
}

// ---- Utilidades CSRF ----
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(?string $token): bool
{
    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}