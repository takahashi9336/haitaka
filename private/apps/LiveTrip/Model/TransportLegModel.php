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
}
