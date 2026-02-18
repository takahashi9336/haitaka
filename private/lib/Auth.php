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
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            $roleKey = $user['role'] ?? '';
            $roleModel = new RoleModel();
            $appModel = new AppModel();
            $roleAppModel = new RoleAppModel();

            $role = $roleModel->getByRoleKey($roleKey);
            $isAdmin = ($roleKey === 'admin');
            $sidebarMode = $role ? ($role['sidebar_mode'] ?? 'full') : 'full';
            $defaultRoute = $role ? ($role['default_route'] ?? '/index.php') : '/index.php';
            $logoText = $role ? ($role['logo_text'] ?? 'MyPlatform') : 'MyPlatform';

            $flat = $appModel->getAllVisible();
            $allowedAppIds = null;
            if ($role && $sidebarMode === 'restricted') {
                $allowedAppIds = $roleAppModel->getAppIdsByRoleId((int)$role['id']);
            }
            $filtered = [];
            foreach ($flat as $row) {
                if (!empty($row['admin_only']) && !$isAdmin) {
                    continue;
                }
                if ($allowedAppIds !== null && !in_array((int)$row['id'], $allowedAppIds, true)) {
                    continue;
                }
                $filtered[] = $row;
            }
            $appsTree = $appModel->buildTree($filtered, null);

            $_SESSION['user'] = [
                'id' => $user['id'],
                'id_name' => $user['id_name'],
                'role' => $roleKey,
                'apps' => $appsTree,
                'logo_text' => $logoText,
                'default_route' => $defaultRoute,
                'sidebar_mode' => $sidebarMode,
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

    /**
     * 日向坂ポータルの管理権限（admin / hinata_admin）を持っているかどうか
     * - システム全体管理者: admin
     * - 日向坂ポータル専用管理者: hinata_admin
     */
    public function isHinataAdmin(): bool {
        $role = $_SESSION['user']['role'] ?? '';
        return $role === 'admin' || $role === 'hinata_admin';
    }

    /**
     * 日向坂ポータル管理者必須。
     * - 未ログインなら /login.php へ
     * - admin / hinata_admin 以外なら指定URLへリダイレクト
     */
    public function requireHinataAdmin(string $redirectUrl = '/index.php'): void {
        if (!$this->check()) {
            header('Location: /login.php');
            exit;
        }
        if (!$this->isHinataAdmin()) {
            header('Location: ' . $redirectUrl);
            exit;
        }
    }

    public function logout(): void {
        $idName = $_SESSION['user']['id_name'] ?? '';
        Logger::info("logout id_name=$idName");
        $_SESSION = [];
        session_destroy();
    }
}