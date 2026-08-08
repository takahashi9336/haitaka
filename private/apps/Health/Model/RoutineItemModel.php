<?php

namespace App\Health\Model;

use Core\BaseModel;

class RoutineItemModel extends BaseModel {
    protected string $table = 'hl_routine_items';
    protected bool $isUserIsolated = true;

    protected array $fields = [
        'id',
        'user_id',
        'phase_id',
        'content',
        'sort_order',
        'created_at',
        'updated_at',
    ];

    public function getByPhaseId(int $phaseId): array {
        $stmt = $this->pdo->prepare(
            "SELECT id, phase_id, content, sort_order
             FROM {$this->table}
             WHERE phase_id = :pid AND user_id = :uid
             ORDER BY sort_order ASC, id ASC"
        );
        $stmt->execute(['pid' => $phaseId, 'uid' => $this->userId]);
        return $stmt->fetchAll();
    }

    public function deleteByPhaseId(int $phaseId): bool {
        $stmt = $this->pdo->prepare(
            "DELETE FROM {$this->table} WHERE phase_id = :pid AND user_id = :uid"
        );
        return $stmt->execute(['pid' => $phaseId, 'uid' => $this->userId]);
    }
}
