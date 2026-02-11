<?php

namespace App\Hinata\Controller;

use App\Hinata\Model\MemberModel;
use App\Hinata\Model\NetaModel;
use Core\Auth;
use Core\Database;

/**
 * ミーグリネタ帳 コントローラ
 * 物理パス: haitaka/private/apps/Hinata/Controller/TalkController.php
 */
class TalkController {

    /**
     * 一覧表示
     */
    public function index(): void {
        $auth = new Auth();
        if (!$auth->check()) { header('Location: /login.php'); exit; }
        
        $memberModel = new MemberModel();
        $netaModel = new NetaModel();
        
        $members = $memberModel->getActiveMembersWithColors();
        $groupedNeta = $netaModel->getGroupedNeta();
        
        require_once __DIR__ . '/../Views/index.php';
    }

    /**
     * 新規保存・更新 (save_neta.php用)
     */
    public function store(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $model = new NetaModel();
            
            $data = [
                'member_id' => $input['member_id'],
                'content'   => $input['content'],
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if (!empty($input['id'])) {
                $model->update((int)$input['id'], $data);
            } else {
                $data['status'] = 'stock';
                $data['created_at'] = date('Y-m-d H:i:s');
                $model->create($data);
            }
            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * ネタの更新専用 (update_neta.php用)
     */
    public function update(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['id']) || empty($input['content'])) {
                throw new \Exception('必要なパラメータが不足しています');
            }

            $model = new NetaModel();
            $model->update((int)$input['id'], [
                'content'    => $input['content'],
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * 完了・未完了の切り替え (update_neta_status.php用)
     */
    public function updateStatus(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $model = new NetaModel();
            $model->update((int)$input['id'], ['status' => $input['status']]);
            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * 推し（お気に入り）登録の切り替え (toggle_favorite.php用)
     */
    public function toggleFavorite(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $memberId = $input['member_id'] ?? null;
            if (!$memberId) throw new \Exception('メンバーIDが指定されていません');

            $db = Database::connect();
            $userId = $_SESSION['user']['id'];

            // 現在の状態を確認
            $stmt = $db->prepare("SELECT id FROM hn_favorites WHERE user_id = ? AND member_id = ?");
            $stmt->execute([$userId, $memberId]);
            $fav = $stmt->fetch();

            if ($fav) {
                // 登録済みなら解除
                $db->prepare("DELETE FROM hn_favorites WHERE id = ?")->execute([$fav['id']]);
                $resStatus = 'removed';
            } else {
                // 未登録なら登録
                $db->prepare("INSERT INTO hn_favorites (user_id, member_id, created_at) VALUES (?, ?, NOW())")
                   ->execute([$userId, $memberId]);
                $resStatus = 'added';
            }

            echo json_encode(['status' => 'success', 'favorite_status' => $resStatus]);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * 削除
     */
    public function delete(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            (new NetaModel())->delete((int)$input['id']);
            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}