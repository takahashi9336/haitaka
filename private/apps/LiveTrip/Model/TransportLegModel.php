<?php

namespace App\LiveTrip\Model;

use Core\BaseModel;

/**
 * 移動区間モデル
 */
class TransportLegModel extends BaseModel {
    protected string $table = 'lt_transport_legs';
    protected array $fields = [
        'id', 'trip_plan_id', 'direction', 'departure_date', 'transport_type', 'route_memo',
        'departure', 'arrival', 'duration_min', 'scheduled_time', 'amount',
        'sort_order', 'created_at', 'updated_at'
    ];
    protected bool $isUserIsolated = false;

    public static array $directions = [
        'outbound' => '往路',
        'return' => '復路'
    ];

    public function getByTripPlanId(int $tripPlanId): array {
        $sql = "SELECT * FROM {$this->table} WHERE trip_plan_id = :tid
                ORDER BY FIELD(direction, 'outbound', 'return'), sort_order, id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['tid' => $tripPlanId]);
        return $stmt->fetchAll();
    }

    /**
     * 複数 trip の交通費合計を取得
     * @return array<int, int> trip_plan_id => total amount
     */
    public function getAmountTotalsByTripPlanIds(array $tripPlanIds): array {
        if (empty($tripPlanIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($tripPlanIds), '?'));
        $sql = "SELECT trip_plan_id, COALESCE(SUM(amount), 0) AS total FROM {$this->table}
                WHERE trip_plan_id IN ({$placeholders}) AND amount IS NOT NULL AND amount > 0
                GROUP BY trip_plan_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($tripPlanIds));
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(int)$row['trip_plan_id']] = (int)$row['total'];
        }
        return $result;
    }
}
