<?php

namespace App\LiveTrip\Model;

use Core\BaseModel;

/**
 * trip参加メンバーモデル
 */
class TripMemberModel extends BaseModel {
    protected string $table = 'lt_trip_members';
    protected array $fields = ['id', 'trip_plan_id', 'user_id', 'role', 'created_at'];
    protected bool $isUserIsolated = false;

    public function addMember(int $tripPlanId, int $userId, string $role = 'owner'): bool {
        $sql = "INSERT INTO {$this->table} (trip_plan_id, user_id, role) VALUES (:tid, :uid, :role)
                ON DUPLICATE KEY UPDATE role = VALUES(role)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'tid' => $tripPlanId,
            'uid' => $userId,
            'role' => $role
        ]);
    }

    public function isMember(int $tripPlanId, int $userId): bool {
        $sql = "SELECT 1 FROM {$this->table} WHERE trip_plan_id = :tid AND user_id = :uid";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['tid' => $tripPlanId, 'uid' => $userId]);
        return (bool) $stmt->fetch();
    }
}
