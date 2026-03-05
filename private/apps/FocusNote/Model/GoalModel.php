<?php

namespace App\FocusNote\Model;

use Core\BaseModel;

/**
 * 目標モデル（WOOP: Wish / Outcome / Obstacle / Plan / Being）
 */
class GoalModel extends BaseModel {
    protected string $table = 'fn_goals';
    protected bool $isUserIsolated = true;

    protected array $fields = [
        'id', 'user_id', 'wish', 'outcome', 'obstacle', 'plan', 'being',
        'is_active', 'created_at', 'updated_at',
    ];

    /**
     * 現在の目標（is_active=1）を取得
     */
    public function findActive(): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :uid AND is_active = 1 LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * 全目標を取得（新しい順）
     */
    public function getAll(): array {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :uid ORDER BY updated_at DESC, id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 目標作成。既存の active を非活性化してから作成
     */
    public function createGoal(array $data): int {
        $this->pdo->prepare("UPDATE {$this->table} SET is_active = 0 WHERE user_id = :uid")
            ->execute(['uid' => $this->userId]);

        $filtered = array_intersect_key($data, array_flip([
            'wish', 'outcome', 'obstacle', 'plan', 'being', 'is_active',
        ]));
        $filtered['user_id'] = $this->userId;
        $filtered['is_active'] = $filtered['is_active'] ?? 1;

        $this->create($filtered);
        return (int) $this->lastInsertId();
    }

    /**
     * 目標更新
     */
    public function updateGoal(int $id, array $data): bool {
        $allowed = ['wish', 'outcome', 'obstacle', 'plan', 'being', 'is_active'];
        $filtered = array_intersect_key($data, array_flip($allowed));
        if (empty($filtered)) return false;
        return $this->update($id, $filtered);
    }

    /**
     * 目標をアクティブに切り替え（他を非活性化）
     */
    public function setActive(int $id): bool {
        $goal = $this->find($id);
        if (!$goal) return false;

        $this->pdo->prepare("UPDATE {$this->table} SET is_active = 0 WHERE user_id = :uid")
            ->execute(['uid' => $this->userId]);
        return $this->update($id, ['is_active' => 1]);
    }
}
