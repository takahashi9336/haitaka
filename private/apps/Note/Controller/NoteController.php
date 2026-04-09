<?php

namespace App\Note\Controller;

use App\Note\Model\NoteListEntryModel;
use App\Note\Model\NoteModel;
use Core\Auth;
use Core\Logger;

/**
 * メモ管理コントローラ
 * 物理パス: haitaka/private/apps/Note/Controller/NoteController.php
 */
class NoteController {
    private Auth $auth;

    public function __construct() {
        $this->auth = new Auth();
    }

    /**
     * メモ一覧・管理画面
     */
    public function index(): void {
        $this->auth->requireLogin();

        try {
            $noteModel = new NoteModel();
            $notes = $noteModel->getActiveNotes();
            $archivedNotes = $noteModel->getArchivedNotes();

            $listEntryModel = new NoteListEntryModel();
            $listKinds = NoteListEntryModel::LIST_KINDS;
            $listEntries = [];
            $archivedListEntries = [];
            foreach (array_keys($listKinds) as $kind) {
                $listEntries[$kind] = $listEntryModel->getActiveByKind($kind);
                $archivedListEntries[$kind] = $listEntryModel->getArchivedByKind($kind);
            }
        } catch (\Exception $e) {
            // テーブルが存在しない場合など、エラーが発生した場合は空配列を返す
            Logger::errorWithContext('Note error', $e);
            $notes = [];
            $archivedNotes = [];
            $listKinds = NoteListEntryModel::LIST_KINDS;
            $listEntries = [];
            $archivedListEntries = [];
            foreach (array_keys($listKinds) as $kind) {
                $listEntries[$kind] = [];
                $archivedListEntries[$kind] = [];
            }
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
                $id = (int) $noteModel->lastInsertId();
                $note = $noteModel->find($id);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'メモを保存しました',
                    'id' => $id,
                    'note' => $note ?: null
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

    /**
     * リストエントリ新規保存
     */
    public function listStore(): void {
        header('Content-Type: application/json');

        try {
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON: ' . json_last_error_msg());
            }
            if (!is_array($input)) {
                throw new \Exception('Invalid input format');
            }

            $model = new NoteListEntryModel();
            $kind = (string)($input['list_kind'] ?? '');
            if (!$model->isValidListKind($kind)) {
                throw new \Exception('list_kind が不正です');
            }

            $payload = $input['payload'] ?? [];
            if (!is_array($payload)) $payload = [];

            $ok = $model->createEntry([
                'list_kind' => $kind,
                'payload' => $payload,
                'bg_color' => $input['bg_color'] ?? '#ffffff',
                'is_pinned' => $input['is_pinned'] ?? 0,
                'status' => 'active',
            ]);
            if (!$ok) {
                throw new \Exception('保存に失敗しました');
            }

            $id = (int)$model->lastInsertId();
            $row = $model->find($id);
            if (is_array($row) && isset($row['payload']) && is_string($row['payload'])) {
                $decoded = json_decode($row['payload'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $row['payload'] = $decoded;
                } else {
                    $row['payload'] = [];
                }
            }
            echo json_encode([
                'status' => 'success',
                'message' => '保存しました',
                'id' => $id,
                'entry' => $row,
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * リストエントリ更新
     */
    public function listUpdate(): void {
        header('Content-Type: application/json');

        try {
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON: ' . json_last_error_msg());
            }
            if (empty($input['id'])) {
                throw new \Exception('IDが指定されていません');
            }

            $model = new NoteListEntryModel();
            $update = [];
            if (array_key_exists('payload', $input)) $update['payload'] = $input['payload'];
            if (array_key_exists('bg_color', $input)) $update['bg_color'] = $input['bg_color'];
            if (array_key_exists('is_pinned', $input)) $update['is_pinned'] = $input['is_pinned'];
            if (array_key_exists('status', $input)) $update['status'] = $input['status'];

            $ok = $model->updateEntry((int)$input['id'], $update);
            if (!$ok) {
                throw new \Exception('更新に失敗しました');
            }

            echo json_encode([
                'status' => 'success',
                'message' => '更新しました',
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * リストエントリ削除
     */
    public function listDelete(): void {
        header('Content-Type: application/json');

        try {
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON: ' . json_last_error_msg());
            }
            if (empty($input['id'])) {
                throw new \Exception('IDが指定されていません');
            }

            $model = new NoteListEntryModel();
            $ok = $model->delete((int)$input['id']);
            if (!$ok) {
                throw new \Exception('削除に失敗しました');
            }

            echo json_encode([
                'status' => 'success',
                'message' => '削除しました',
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * リストエントリ ピン留めトグル
     */
    public function listTogglePin(): void {
        header('Content-Type: application/json');

        try {
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON: ' . json_last_error_msg());
            }
            if (empty($input['id'])) {
                throw new \Exception('IDが指定されていません');
            }

            $model = new NoteListEntryModel();
            $ok = $model->togglePin((int)$input['id']);
            if (!$ok) {
                throw new \Exception('ピン留めの変更に失敗しました');
            }

            echo json_encode([
                'status' => 'success',
                'message' => 'ピン留めを変更しました',
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}
