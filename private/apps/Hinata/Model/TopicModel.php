<?php

namespace App\Hinata\Model;

use Core\BaseModel;

class TopicModel extends BaseModel {
    protected string $table = 'hn_neta';
    protected array $fields = [
        'id', 'user_id', 'member_name', 'event_name', 'title', 
        'content', 'status', 'memo', 'created_at', 'updated_at'
    ];

    /**
     * ユーザーに紐づくネタ一覧を取得
     */
    public function getTopics(): array {
        $sql = "SELECT {$this->getSelectFields()} 
                FROM {$this->table} 
                WHERE user_id = :uid 
                ORDER BY status DESC, updated_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId]);
        return $stmt->fetchAll();
    }
}