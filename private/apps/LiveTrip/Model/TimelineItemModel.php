<?php

namespace App\LiveTrip\Model;

use Core\BaseModel;

/**
 * タイムライン項目モデル
 */
class TimelineItemModel extends BaseModel {
    protected string $table = 'lt_timeline_items';
    protected array $fields = [
        'id', 'trip_plan_id', 'scheduled_date', 'label', 'scheduled_time', 'duration_min', 'memo',
        'place_id', 'latitude', 'longitude', 'location_label', 'location_address',
        'sort_order',
        'created_at', 'updated_at'
    ];
    protected bool $isUserIsolated = false;

    public function getByTripPlanId(int $tripPlanId): array {
        $sql = "SELECT * FROM {$this->table} WHERE trip_plan_id = :tid ORDER BY sort_order, scheduled_time, id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['tid' => $tripPlanId]);
        return $stmt->fetchAll();
    }

    /**
     * 一覧用: 各遠征の「先頭の予定ラベル」を返す
     * @param array<int,int> $tripPlanIds
     * @return array<int,string> trip_plan_id => label
     */
    public function getFirstLabelsByTripPlanIds(array $tripPlanIds): array {
        $tripPlanIds = array_values(array_unique(array_map('intval', $tripPlanIds)));
        if (empty($tripPlanIds)) return [];

        $in = implode(',', array_fill(0, count($tripPlanIds), '?'));
        $sql = "SELECT trip_plan_id, label, scheduled_date, scheduled_time, sort_order, id
                FROM {$this->table}
                WHERE trip_plan_id IN ({$in})
                ORDER BY trip_plan_id ASC,
                         (scheduled_date IS NULL) ASC, scheduled_date ASC,
                         (scheduled_time IS NULL) ASC, scheduled_time ASC,
                         sort_order ASC, id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($tripPlanIds);
        $rows = $stmt->fetchAll();

        $out = [];
        foreach ($rows as $r) {
            $tid = (int)($r['trip_plan_id'] ?? 0);
            if ($tid <= 0) continue;
            if (isset($out[$tid])) continue;
            $label = trim((string)($r['label'] ?? ''));
            if ($label === '') continue;
            $out[$tid] = $label;
        }
        return $out;
    }
}
