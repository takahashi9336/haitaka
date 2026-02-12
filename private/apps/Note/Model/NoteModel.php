<?php

namespace App\Note\Model;

use Core\BaseModel;

/**
 * メモ管理モデル
 * 物理パス: haitaka/private/apps/Note/Model/NoteModel.php
 */
class NoteModel extends BaseModel {
    protected string $table = 'nt_notes';
    protected bool $isUserIsolated = true;  // ユーザー隔離を有効化
    
    protected array $fields = [
        'id', 'user_id', 'title', 'content', 'bg_color', 
        'is_pinned', 'status', 'created_at', 'updated_at'
    ];

    /**
     * アクティブなメモを取得（ピン留め優先、作成日時降順）
     */
    public function getActiveNotes(): array {
        $sql = "SELECT " . implode(', ', $this->fields) . " 
                FROM {$this->table} 
                WHERE user_id = :uid AND status = 'active'
                ORDER BY is_pinned DESC, created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId]);
        return $stmt->fetchAll();
    }

    /**
     * メモ作成（タイトル自動生成機能付き）
     * タイトルが空の場合、本文の先頭30文字を自動でタイトルに設定
     */
    public function createNote(array $data): bool {
        // タイトルが空の場合、本文から自動生成
        if (empty($data['title']) && !empty($data['content'])) {
            $content = strip_tags($data['content']);
            $data['title'] = mb_substr($content, 0, 30, 'UTF-8');
            if (mb_strlen($content, 'UTF-8') > 30) {
                $data['title'] .= '...';
            }
        }
        
        return $this->create($data);
    }

    /**
     * ピン留めトグル
     */
    public function togglePin(int $id): bool {
        $note = $this->find($id);
        if (!$note) return false;
        
        $newPinStatus = $note['is_pinned'] ? 0 : 1;
        return $this->update($id, ['is_pinned' => $newPinStatus]);
    }

    /**
     * ステータス変更
     */
    public function changeStatus(int $id, string $status): bool {
        if (!in_array($status, ['active', 'archived', 'trash'])) {
            return false;
        }
        return $this->update($id, ['status' => $status]);
    }

    /**
     * 背景色変更
     */
    public function changeColor(int $id, string $color): bool {
        return $this->update($id, ['bg_color' => $color]);
    }
}
