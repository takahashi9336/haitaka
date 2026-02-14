<?php

namespace App\Note\Controller;

use App\Note\Model\NoteModel;
use Core\Auth;

/**
 * メモ管理コントローラ
 * 物理パス: haitaka/private/apps/Note/Controller/NoteController.php
 */
class NoteController {

    /**
     * メモ一覧・管理画面
     */
    public function index(): void {
        $auth = new Auth();
        $auth->requireLogin();

        try {
            $noteModel = new NoteModel();
            $notes = $noteModel->getActiveNotes();
        } catch (\Exception $e) {
            // テーブルが存在しない場合など、エラーが発生した場合は空配列を返す
            error_log('Note error: ' . $e->getMessage());
            $notes = [];
        }
        
        $user = $_SESSION['user'];

        require_once __DIR__ . '/../Views/note_index.php';
    }

    /**
     * メモ新規保存（ダッシュボードのクイックメモから呼ばれる）
     */
    public function store(): void {
        header('Content-Type: application/json');
        
        try {
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);
            
            // JSONデコードエラーチェック
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON: ' . json_last_error_msg());
            }
            
            if (!is_array($input)) {
                throw new \Exception('Invalid input format');
            }
            
            if (empty($input['content'])) {
                throw new \Exception('メモの内容を入力してください');
            }

            $noteModel = new NoteModel();
            
            $noteData = [
                'title' => $input['title'] ?? '',
                'content' => $input['content'],
                'bg_color' => $input['bg_color'] ?? '#ffffff',
                'is_pinned' => $input['is_pinned'] ?? 0,
                'status' => 'active'
            ];

            $result = $noteModel->createNote($noteData);
            
            if ($result) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'メモを保存しました',
                    'id' => $noteModel->lastInsertId()
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new \Exception('メモの保存に失敗しました');
            }
            
        } catch (\Exception $e) {
            echo json_encode([
                'status' => 'error', 
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * メモ更新
     */
    public function update(): void {
        header('Content-Type: application/json');
        
        try {
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON: ' . json_last_error_msg());
            }
            
            if (empty($input['id'])) {
                throw new \Exception('メモIDが指定されていません');
            }

            $noteModel = new NoteModel();
            
            $updateData = [];
            if (isset($input['title'])) $updateData['title'] = $input['title'];
            if (isset($input['content'])) $updateData['content'] = $input['content'];
            if (isset($input['bg_color'])) $updateData['bg_color'] = $input['bg_color'];
            if (isset($input['is_pinned'])) $updateData['is_pinned'] = $input['is_pinned'];
            if (isset($input['status'])) $updateData['status'] = $input['status'];

            $result = $noteModel->update((int)$input['id'], $updateData);
            
            if ($result) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'メモを更新しました'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new \Exception('メモの更新に失敗しました');
            }
            
        } catch (\Exception $e) {
            echo json_encode([
                'status' => 'error', 
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * メモ削除
     */
    public function delete(): void {
        header('Content-Type: application/json');
        
        try {
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON: ' . json_last_error_msg());
            }
            
            if (empty($input['id'])) {
                throw new \Exception('メモIDが指定されていません');
            }

            $noteModel = new NoteModel();
            $result = $noteModel->delete((int)$input['id']);
            
            if ($result) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'メモを削除しました'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new \Exception('メモの削除に失敗しました');
            }
            
        } catch (\Exception $e) {
            echo json_encode([
                'status' => 'error', 
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * ピン留めトグル
     */
    public function togglePin(): void {
        header('Content-Type: application/json');
        
        try {
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON: ' . json_last_error_msg());
            }
            
            if (empty($input['id'])) {
                throw new \Exception('メモIDが指定されていません');
            }

            $noteModel = new NoteModel();
            $result = $noteModel->togglePin((int)$input['id']);
            
            if ($result) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'ピン留めを変更しました'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new \Exception('ピン留めの変更に失敗しました');
            }
            
        } catch (\Exception $e) {
            echo json_encode([
                'status' => 'error', 
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}
