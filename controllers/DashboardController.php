<?php
require_once __DIR__ . '/../controllers/AuthController.php';

class DashboardController
{
    public static function show()
    {
        $user = AuthController::requireLogin();
        require_once __DIR__ . '/../views/dashboard.php';
    }
}
