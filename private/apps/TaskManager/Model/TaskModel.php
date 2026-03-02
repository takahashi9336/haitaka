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
        $sql = "SELECT t.*, c.name as category_name, c.color as category_color 
                FROM {$this->table} t
                LEFT JOIN tm_categories c ON t.category_id = c.id
                WHERE t.user_id = :uid 
                AND t.status != 'done'
                AND (t.start_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR) OR t.start_date IS NULL)
                ORDER BY t.due_date ASC, t.priority DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId]);
        return $stmt->fetchAll();
    }

    public function getAllTasks(): array {
        $sql = "SELECT t.*, c.name as category_name, c.color as category_color 
                FROM {$this->table} t
                LEFT JOIN tm_categories c ON t.category_id = c.id
                WHERE t.user_id = :uid
                ORDER BY t.priority DESC, t.due_date ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId]);
        return $stmt->fetchAll();
    }

    public function getTaskWithCategory(int $id): ?array {
        $sql = "SELECT t.*, c.name as category_name, c.color as category_color 
                FROM {$this->table} t
                LEFT JOIN tm_categories c ON t.category_id = c.id
                WHERE t.id = :id AND t.user_id = :uid LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'uid' => $this->userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}