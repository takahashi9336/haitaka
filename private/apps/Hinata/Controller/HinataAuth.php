<?php

namespace App\Hinata\Controller;

use Core\Auth;

class HinataAuth {
    private Auth $auth;

    public function __construct(Auth $auth) {
        $this->auth = $auth;
    }

    public function isHinataAdmin(): bool {
        $role = $_SESSION['user']['role'] ?? '';
        return $role === 'admin' || $role === 'hinata_admin';
    }

    public function requireHinataAdmin(string $redirectUrl = '/index.php'): void {
        if (!$this->auth->check()) {
            header('Location: /login.php');
            exit;
        }
        if (!$this->isHinataAdmin()) {
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
}

