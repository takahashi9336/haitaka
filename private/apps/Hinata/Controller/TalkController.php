<?php

namespace App\Hinata\Controller;

use App\Hinata\Model\MemberModel;
use App\Hinata\Model\NetaModel;
use App\Hinata\Model\FavoriteModel;
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
        $auth->requireLogin();
        
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
     * level 7-9 は排他制御付き（ユーザーにつき各1名のみ）
     */
    public function toggleFavorite(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $memberId = $input['member_id'] ?? null;
            if (!$memberId) throw new \Exception('メンバーIDが指定されていません');
            $level = isset($input['level']) ? (int)$input['level'] : null;

            $favModel = new FavoriteModel();

            if ($level === null) {
                $current = $favModel->getMemberLevel((int)$memberId);
                $newLevel = $current > 0 ? 0 : 1;
                $result = $favModel->setLevel((int)$memberId, $newLevel);
            } else {
                $result = $favModel->setLevel((int)$memberId, $level);
            }

            echo json_encode($result, JSON_UNESCAPED_UNICODE);
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