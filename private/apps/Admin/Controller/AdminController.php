<?php

namespace App\Admin\Controller;

use Core\Auth;
use Core\UserModel;

class AdminController {

    public function index(): void {
        $auth = new Auth();
        $auth->requireAdmin();

        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/portal.php';
    }

    public function users(): void {
        $auth = new Auth();
        $auth->requireAdmin();

        $user = $_SESSION['user'];
        $userModel = new UserModel();
        $allUsers = $userModel->getAllUsers();
        require_once __DIR__ . '/../Views/users.php';
    }
}
