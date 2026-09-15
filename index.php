<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/DashboardController.php';

$action = $_GET['action'] ?? 'dashboard';

switch ($action) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $error = 'La solicitud no es válida. Vuelve a intentarlo.';

            if (csrf_verify($_POST['csrf_token'] ?? null)) {
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';

                $user = AuthController::login($email, $password);
                if ($user !== null) {
                    // Redirigir por rol: admin al panel, resto al portal de usuario
                    if (AuthController::esAdmin()) {
                        header('Location: ' . BASE_URL . 'index2.php');
                    } else {
                        header('Location: ' . BASE_URL . 'usuario.php');
                    }
                    exit;
                } else {
                    $error = 'Credenciales incorrectas. Inténtalo de nuevo.';
                }
            }

            require __DIR__ . '/views/login.php';
        } else {
            if (AuthController::isAuthenticated()) {
                header('Location: ' . BASE_URL);
                exit;
            }
            $error = null;
            require __DIR__ . '/views/login.php';
        }
        break;

    case 'logout':
        AuthController::logout();
        header('Location: ' . BASE_URL . '?action=login');
        exit;
        break;

    case 'dashboard':
    default:
        $user = AuthController::requireLogin();
        require __DIR__ . '/views/dashboard.php';
        break;
}