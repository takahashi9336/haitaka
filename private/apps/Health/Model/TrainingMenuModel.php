<?php

namespace App\Health\Model;

use Core\BaseModel;

/**
 * Health: トレーニングメニュー
 */
class TrainingMenuModel extends BaseModel {
    protected string $table = 'hl_training_menu_items';
    protected bool $isUserIsolated = true;

    protected array $fields = [
        'id',
        'user_id',
        'name',
        'reps',
        'created_at',
        'updated_at',
    ];

    public function getAllItems(): array {
        $sql = "SELECT " . implode(', ', $this->fields) . "
                FROM {$this->table}
                WHERE user_id = :uid
                ORDER BY id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId]);
        return $stmt->fetchAll();
    }
}
