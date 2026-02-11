<?php

namespace App\TaskManager\Model;

use Core\BaseModel;

class CategoryModel extends BaseModel {
    protected string $table = 'tm_categories';
    protected array $fields = ['id', 'user_id', 'name', 'color', 'created_at'];

    /**
     * 名前でカテゴリを検索または新規作成してIDを返す
     */
    public function getOrCreate(string $name, string $color): int {
        $sql = "SELECT id FROM {$this->table} WHERE user_id = :uid AND name = :name LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId, 'name' => $name]);
        $row = $stmt->fetch();

        if ($row) {
            return (int)$row['id'];
        }

        $this->create([
            'name' => $name,
            'color' => $color,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return (int)$this->lastInsertId();
    }
}