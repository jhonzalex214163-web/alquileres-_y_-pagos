<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/DashboardController.php';

$action = $_GET['action'] ?? 'dashboard';

switch ($action) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $error = null;

            $user = AuthController::login($email, $password);
            if ($user !== null) {
                header('Location: ' . BASE_URL);
                exit;
            } else {
                $error = 'Credenciales incorrectas. Inténtalo de nuevo.';
                require __DIR__ . '/views/login.php';
            }
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
