<?php

namespace App\LiveTrip\Model;

use Core\BaseModel;

/**
 * 汎用イベントモデル
 */
class LtEventModel extends BaseModel {
    protected string $table = 'lt_events';
    protected array $fields = [
        'id', 'user_id', 'event_name', 'event_date', 'event_place', 'event_info',
        'event_place_address', 'latitude', 'longitude', 'place_id',
        'created_at', 'updated_at'
    ];
    protected bool $isUserIsolated = true;

    public function getForSelect(): array {
        $sql = "SELECT id, event_name, event_date, event_place FROM {$this->table}
                WHERE user_id = :uid ORDER BY event_date DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId]);
        return $stmt->fetchAll();
    }
}
