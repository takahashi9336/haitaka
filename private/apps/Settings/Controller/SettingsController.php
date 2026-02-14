<?php

namespace App\Settings\Controller;

use Core\Auth;
use Core\UserModel;

class SettingsController {
    
    public function index(): void {
        $auth = new Auth();
        $auth->requireLogin();

        $user = $_SESSION['user'];

        require_once __DIR__ . '/../Views/index.php';
    }

    public function updateSelf(): void {
        header('Content-Type: application/json');

        try {
            $auth = new Auth();
            if (!$auth->check()) {
                echo json_encode(['status' => 'error', 'message' => 'セッション切れ']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $currentPass = $input['current_password'] ?? '';
            $newPass = $input['new_password'] ?? '';

            if (!$currentPass || !$newPass) {
                echo json_encode(['status' => 'error', 'message' => '入力不足']);
                return;
            }

            $userModel = new UserModel();
            // セッションから確実にIDを取得
            $userId = $_SESSION['user']['id']; 
            $me = $userModel->findById($userId);

            if (!$me || !password_verify($currentPass, $me['password'])) {
                echo json_encode(['status' => 'error', 'message' => '現在のパスワードが違います']);
                return;
            }

            $userModel->updatePassword($userId, password_hash($newPass, PASSWORD_DEFAULT));
            echo json_encode(['status' => 'success']);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function adminReset(): void {
        header('Content-Type: application/json');
        $auth = new Auth();
        if (!$auth->check() || !$auth->isAdmin()) {
            echo json_encode(['status' => 'error', 'message' => '権限なし']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $targetId = $input['target_id'] ?? null;
        $newPass = $input['new_password'] ?? '';

        if (!$targetId || !$newPass) {
            echo json_encode(['status' => 'error', 'message' => '入力不足']);
            return;
        }

        $userModel = new UserModel();
        $userModel->updatePassword($targetId, password_hash($newPass, PASSWORD_DEFAULT));
        echo json_encode(['status' => 'success']);
    }

    public function createUser(): void {
        header('Content-Type: application/json');
        $auth = new Auth();
        if (!$auth->check() || !$auth->isAdmin()) {
            echo json_encode(['status' => 'error', 'message' => '権限なし']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $idName = $input['id_name'] ?? '';
        $password = $input['password'] ?? '';
        $role = $input['role'] ?? 'user';

        if (!$idName || !$password) {
            echo json_encode(['status' => 'error', 'message' => 'ID/パスワード必須']);
            return;
        }

        if ($role === 'admin') {
            echo json_encode(['status' => 'error', 'message' => 'Admin作成禁止']);
            return;
        }

        $userModel = new UserModel();
        $existing = $userModel->findByIdName($idName);
        if ($existing) {
            echo json_encode(['status' => 'error', 'message' => 'このユーザーIDは既に登録されています']);
            return;
        }
        $success = $userModel->createUser($idName, password_hash($password, PASSWORD_DEFAULT), $role);

        if ($success) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => '作成失敗']);
        }
    }
}