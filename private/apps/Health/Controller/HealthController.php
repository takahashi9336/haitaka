<?php

namespace App\Health\Controller;

use Core\Auth;

class HealthController {
    public function portal(): void {
        $auth = new Auth();
        $auth->requireLogin();

        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/portal.php';
    }

    public function kitchenStock(): void {
        $auth = new Auth();
        $auth->requireLogin();

        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/kitchen_stock.php';
    }
}

