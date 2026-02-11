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
            
            return true;
        }
        return false;
    }

    public function check(): bool {
        return isset($_SESSION['user']['id']);
    }

    public function logout(): void {
        $_SESSION = [];
        session_destroy();
    }
}