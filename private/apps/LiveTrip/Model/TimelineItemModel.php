<?php

namespace App\LiveTrip\Model;

use Core\BaseModel;

/**
 * タイムライン項目モデル
 */
class TimelineItemModel extends BaseModel {
    protected string $table = 'lt_timeline_items';
    protected array $fields = [
        'id', 'trip_plan_id', 'scheduled_date', 'label', 'scheduled_time', 'duration_min', 'memo', 'sort_order',
        'created_at', 'updated_at'
    ];
    protected bool $isUserIsolated = false;

    public function getByTripPlanId(int $tripPlanId): array {
        $sql = "SELECT * FROM {$this->table} WHERE trip_plan_id = :tid ORDER BY sort_order, scheduled_time, id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['tid' => $tripPlanId]);
        return $stmt->fetchAll();
    }
}
