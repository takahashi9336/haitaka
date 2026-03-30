<?php

namespace App\Health\Controller;

use Core\Auth;

class HealthController {
    private Auth $auth;

    public function __construct() {
        $this->auth = new Auth();
    }

    public function portal(): void {
        $this->auth->requireLogin();

        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/portal.php';
    }

    public function kitchenStock(): void {
        $this->auth->requireLogin();

        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/kitchen_stock.php';
    }
}

