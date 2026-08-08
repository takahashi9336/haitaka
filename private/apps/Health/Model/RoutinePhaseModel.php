<?php

namespace App\Health\Model;

use Core\BaseModel;

class RoutinePhaseModel extends BaseModel {
    protected string $table = 'hl_routine_phases';
    protected bool $isUserIsolated = true;

    protected array $fields = [
        'id',
        'user_id',
        'routine_id',
        'label',
        'sort_order',
        'created_at',
        'updated_at',
    ];

    public function getByRoutineId(int $routineId): array {
        $stmt = $this->pdo->prepare(
            "SELECT id, routine_id, label, sort_order
             FROM {$this->table}
             WHERE routine_id = :rid AND user_id = :uid
             ORDER BY sort_order ASC, id ASC"
        );
        $stmt->execute(['rid' => $routineId, 'uid' => $this->userId]);
        return $stmt->fetchAll();
    }

    public function deleteByRoutineId(int $routineId): bool {
        $stmt = $this->pdo->prepare(
            "DELETE FROM {$this->table} WHERE routine_id = :rid AND user_id = :uid"
        );
        return $stmt->execute(['rid' => $routineId, 'uid' => $this->userId]);
    }
}
