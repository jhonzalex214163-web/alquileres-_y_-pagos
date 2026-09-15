<?php
// controllers/UsuarioController.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/AuthController.php';

header('Content-Type: application/json');

if (!AuthController::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado. Inicia sesión.']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    // Listado de usuarios para la gestión de viajes
    case 'listar':
        try {
            echo json_encode(User::listAll());
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error al consultar los usuarios.']);
        }
        exit;

    // Ranking de fidelización (vw_clientes_frecuentes) para el portal eco
    case 'frecuentes':
        try {
            echo json_encode(User::listarFrecuentes());
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error al consultar el ranking de fidelización.']);
        }
        exit;

    // Registro de un nuevo usuario cliente desde el panel
    case 'registrar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
            exit;
        }
        if (!csrf_verify($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido.']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $nombre   = trim($data['nombre'] ?? '');
        $apellido = trim($data['apellido'] ?? '');
        $email    = strtolower(trim($data['email'] ?? ''));
        $telefono = trim($data['telefono'] ?? '');

        if ($nombre === '' || $apellido === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status' => 'error', 'message' => 'Nombre, apellidos y un email válido son obligatorios.']);
            exit;
        }

        if (User::existeEmail($email)) {
            echo json_encode(['status' => 'error', 'message' => 'El email ya está registrado.']);
            exit;
        }

        try {
            $id = User::crearCliente($nombre, $apellido, $email, $telefono === '' ? null : $telefono);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo registrar el usuario. Inténtalo de nuevo.']);
            exit;
        }

        echo json_encode([
            'status'        => 'success',
            'id_usuario'    => $id,
            'password_temp' => 'Bici2026',
            'message'       => 'Usuario registrado correctamente.'
        ]);
        exit;

    default:
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
        exit;
}