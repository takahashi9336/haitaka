<?php

namespace Core;

class Auth {
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            $handler = new SessionManager();
            session_set_save_handler($handler, true);
            
            $lifetime = 2592000; // 30日
            ini_set('session.gc_maxlifetime', $lifetime);
            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            session_start();
        }
    }

    /**
     * ログイン実行
     * 設計書通りの多次元配列セッションを構築する
     */
    public function login(string $idName, string $password): bool {
        $db = Database::connect();
        
        // 1. ユーザ基本情報の取得
        $stmt = $db->prepare("SELECT * FROM sys_users WHERE id_name = :name LIMIT 1");
        $stmt->execute(['name' => $idName]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            // 2. 権限・アプリ情報の取得 (設計書に基づきJOIN)
            // ここでは簡易的に構造化するが、本来は sys_apps 等をJOINして取得する
            // ※検証用として固定値を混ぜつつ構造を定義
            $_SESSION['user'] = [
                'id' => $user['id'],
                'id_name' => $user['id_name'],
                'role' => $user['role'],
                'apps' => [
                    'task_manager' => ['read', 'write'],
                    'focus_note'   => ['read', 'write', 'admin'],
                    'hinata'       => ['read']
                ],
                'permissions' => [
                    'view_dashboard',
                    'manage_users'
                ]
            ];

            Logger::info("login success id_name={$user['id_name']} user_id={$user['id']}");
            return true;
        }

        Logger::info("login failed id_name=" . substr($idName, 0, 20));
        return false;
    }

    public function check(): bool {
        return isset($_SESSION['user']['id']);
    }

    /**
     * ログイン必須。未ログインならリダイレクトして終了。
     */
    public function requireLogin(string $redirectUrl = '/login.php'): void {
        if (!$this->check()) {
            header('Location: ' . $redirectUrl);
            exit;
        }
    }

    /**
     * 管理者必須。未ログインなら /login.php、非管理者なら指定URLへリダイレクトして終了。
     */
    public function requireAdmin(string $redirectUrl = '/index.php'): void {
        if (!$this->check()) {
            header('Location: /login.php');
            exit;
        }
        if (($_SESSION['user']['role'] ?? '') !== 'admin') {
            header('Location: ' . $redirectUrl);
            exit;
        }
    }

    /**
     * 現在ユーザーが管理者かどうか
     */
    public function isAdmin(): bool {
        return ($_SESSION['user']['role'] ?? '') === 'admin';
    }

    public function logout(): void {
        $idName = $_SESSION['user']['id_name'] ?? '';
        Logger::info("logout id_name=$idName");
        $_SESSION = [];
        session_destroy();
    }
}