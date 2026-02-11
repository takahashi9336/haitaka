<?php

namespace App\TaskManager\Model;

use Core\BaseModel;

class TaskModel extends BaseModel {
    protected string $table = 'tm_tasks';
    protected array $fields = [
        'id', 'user_id', 'category_id', 'title', 'description', 'status', 
        'priority', 'start_date', 'due_date', 'created_at', 'updated_at'
    ];

    public function getActiveTasks(): array {
        // カテゴリ名を結合して取得するようにSQLを強化
        $sql = "SELECT t.*, c.name as category_name, c.color as category_color 
                FROM {$this->table} t
                LEFT JOIN tm_categories c ON t.category_id = c.id
                WHERE t.user_id = :uid 
                AND (t.start_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR) OR t.start_date IS NULL)
                ORDER BY t.status ASC, t.due_date ASC, t.priority DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId]);
        return $stmt->fetchAll();
    }
}