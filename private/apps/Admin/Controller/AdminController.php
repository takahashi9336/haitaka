<?php

namespace App\Admin\Controller;

use Core\Auth;
use Core\UserModel;
use Core\AppModel;
use Core\RoleModel;
use Core\RoleAppModel;
use Core\SessionManager;

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
        $roleModel = new RoleModel();
        $roles = $roleModel->getAll();
        require_once __DIR__ . '/../Views/users.php';
    }

    public function apps(): void {
        $auth = new Auth();
        $auth->requireAdmin();

        $user = $_SESSION['user'];
        $appModel = new AppModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'create') {
                $appModel->create([
                    'app_key' => $_POST['app_key'] ?? '',
                    'name' => $_POST['name'] ?? '',
                    'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
                    'route_prefix' => $_POST['route_prefix'] ?? '',
                    'path' => $_POST['path'] ?? null,
                    'icon_class' => $_POST['icon_class'] ?? null,
                    'theme_primary' => $_POST['theme_primary'] ?? null,
                    'theme_light' => $_POST['theme_light'] ?? null,
                    'default_route' => $_POST['default_route'] ?? null,
                    'description' => $_POST['description'] ?? null,
                    'is_system' => isset($_POST['is_system']) ? 1 : 0,
                    'sort_order' => (int)($_POST['sort_order'] ?? 0),
                    'is_visible' => isset($_POST['is_visible']) ? 1 : 0,
                    'admin_only' => isset($_POST['admin_only']) ? 1 : 0,
                ]);
                SessionManager::invalidateAllSessions();
            } elseif ($action === 'update' && isset($_POST['id'])) {
                $appModel->update((int)$_POST['id'], [
                    'app_key' => $_POST['app_key'] ?? '',
                    'name' => $_POST['name'] ?? '',
                    'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
                    'route_prefix' => $_POST['route_prefix'] ?? '',
                    'path' => $_POST['path'] ?? null,
                    'icon_class' => $_POST['icon_class'] ?? null,
                    'theme_primary' => $_POST['theme_primary'] ?? null,
                    'theme_light' => $_POST['theme_light'] ?? null,
                    'default_route' => $_POST['default_route'] ?? null,
                    'description' => $_POST['description'] ?? null,
                    'is_system' => isset($_POST['is_system']) ? 1 : 0,
                    'sort_order' => (int)($_POST['sort_order'] ?? 0),
                    'is_visible' => isset($_POST['is_visible']) ? 1 : 0,
                    'admin_only' => isset($_POST['admin_only']) ? 1 : 0,
                ]);
                SessionManager::invalidateAllSessions();
            } elseif ($action === 'delete' && isset($_POST['id'])) {
                $id = (int)$_POST['id'];
                $app = $appModel->findById($id);
                if ($app) {
                    if (!empty($app['is_system'])) {
                        $_SESSION['admin_error'] = 'システム固定のアプリは削除できません。';
                    } elseif ($appModel->hasChildren($id)) {
                        $_SESSION['admin_error'] = '子画面があるアプリは先に子を削除してください。';
                    } else {
                        $appModel->delete($id);
                        SessionManager::invalidateAllSessions();
                    }
                }
            }
            header('Location: /admin/apps.php');
            exit;
        }

        $allApps = $appModel->getAll();
        $appsTree = $appModel->buildTree($allApps, null);
        require_once __DIR__ . '/../Views/apps.php';
    }

    public function roles(): void {
        $auth = new Auth();
        $auth->requireAdmin();

        $user = $_SESSION['user'];
        $roleModel = new RoleModel();
        $appModel = new AppModel();
        $roleAppModel = new RoleAppModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'create') {
                $newId = $roleModel->create([
                    'role_key' => $_POST['role_key'] ?? '',
                    'name' => $_POST['name'] ?? '',
                    'description' => $_POST['description'] ?? null,
                    'default_route' => $_POST['default_route'] ?? '/index.php',
                    'logo_text' => $_POST['logo_text'] ?? null,
                    'sidebar_mode' => $_POST['sidebar_mode'] ?? 'full',
                ]);
                if ($newId && ($_POST['sidebar_mode'] ?? '') === 'restricted' && !empty($_POST['app_ids']) && is_array($_POST['app_ids'])) {
                    $roleAppModel->setForRole($newId, $_POST['app_ids']);
                }
                SessionManager::invalidateAllSessions();
            } elseif ($action === 'update' && isset($_POST['id'])) {
                $roleModel->update((int)$_POST['id'], [
                    'role_key' => $_POST['role_key'] ?? '',
                    'name' => $_POST['name'] ?? '',
                    'description' => $_POST['description'] ?? null,
                    'default_route' => $_POST['default_route'] ?? '/index.php',
                    'logo_text' => $_POST['logo_text'] ?? null,
                    'sidebar_mode' => $_POST['sidebar_mode'] ?? 'full',
                ]);
                $roleId = (int)$_POST['id'];
                $appIds = ($_POST['sidebar_mode'] ?? '') === 'restricted' && isset($_POST['app_ids']) && is_array($_POST['app_ids']) ? $_POST['app_ids'] : [];
                $roleAppModel->setForRole($roleId, $appIds);
                SessionManager::invalidateAllSessions();
            } elseif ($action === 'delete' && isset($_POST['id'])) {
                $roleModel->delete((int)$_POST['id']);
                SessionManager::invalidateAllSessions();
            }
            header('Location: /admin/roles.php');
            exit;
        }

        $allRoles = $roleModel->getAll();
        $allApps = $appModel->getAll();
        $roleAppIds = [];
        foreach ($allRoles as $r) {
            $roleAppIds[(int)$r['id']] = $roleAppModel->getAppIdsByRoleId((int)$r['id']);
        }
        require_once __DIR__ . '/../Views/roles.php';
    }
}
