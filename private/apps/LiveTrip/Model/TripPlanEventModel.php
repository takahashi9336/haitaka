<?php

namespace App\LiveTrip\Model;

use Core\BaseModel;

/**
 * 遠征とイベントの紐づけモデル
 * 1遠征に対して複数イベント（日向坂/汎用の混合可）
 */
class TripPlanEventModel extends BaseModel {
    protected string $table = 'lt_trip_plan_events';
    protected array $fields = [
        'id', 'trip_plan_id', 'event_type', 'hn_event_id', 'lt_event_id',
        'sort_order', 'seat_info', 'impression', 'created_at', 'updated_at'
    ];
    protected bool $isUserIsolated = false;

    /**
     * 遠征に紐づくイベント一覧を取得（event_name, event_date, event_place 付き）
     * 日向坂イベントの座席・感想は hn_user_events_status から取得
     */
    public function getByTripPlanId(int $tripPlanId, int $userId): array {
        $sql = "SELECT tpe.id, tpe.trip_plan_id, tpe.event_type, tpe.hn_event_id, tpe.lt_event_id, tpe.sort_order,
                       tpe.created_at, tpe.updated_at,
                       COALESCE(he.event_name, le.event_name) AS event_name,
                       COALESCE(he.event_date, le.event_date) AS event_date,
                       COALESCE(he.event_place, le.event_place) AS event_place,
                       he.event_place AS hn_event_place,
                       CASE WHEN tpe.event_type = 'hinata' THEN ues.seat_info ELSE tpe.seat_info END AS seat_info,
                       CASE WHEN tpe.event_type = 'hinata' THEN ues.impression ELSE tpe.impression END AS impression
                FROM {$this->table} tpe
                LEFT JOIN hn_events he ON tpe.event_type = 'hinata' AND tpe.hn_event_id = he.id
                LEFT JOIN lt_events le ON tpe.event_type = 'generic' AND tpe.lt_event_id = le.id
                LEFT JOIN hn_user_events_status ues ON tpe.event_type = 'hinata' AND tpe.hn_event_id = ues.event_id AND ues.user_id = :uid
                WHERE tpe.trip_plan_id = :trip_plan_id
                ORDER BY tpe.sort_order ASC, COALESCE(he.event_date, le.event_date) ASC, tpe.id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['trip_plan_id' => $tripPlanId, 'uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * 遠征に紐づくイベントID一覧（日付情報なし、軽量取得）
     */
    public function getIdsByTripPlanId(int $tripPlanId): array {
        $sql = "SELECT id FROM {$this->table} WHERE trip_plan_id = :trip_plan_id ORDER BY sort_order, id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['trip_plan_id' => $tripPlanId]);
        return array_column($stmt->fetchAll(), 'id');
    }

    public function create(array $data): bool {
        $filtered = $this->filterFields($data);
        $cols = implode(', ', array_keys($filtered));
        $placeholders = ':' . implode(', :', array_keys($filtered));
        $sql = "INSERT INTO {$this->table} ({$cols}) VALUES ({$placeholders})";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($filtered);
    }

    public function update($id, array $data): bool {
        $filtered = $this->filterFields($data);
        if (empty($filtered)) return false;
        $set = implode(', ', array_map(fn($k) => "{$k} = :{$k}", array_keys($filtered)));
        $filtered['id'] = $id;
        $sql = "UPDATE {$this->table} SET {$set} WHERE id = :id";
        return $this->pdo->prepare($sql)->execute($filtered);
    }

    public function deleteByTripPlanId(int $tripPlanId): void {
        $sql = "DELETE FROM {$this->table} WHERE trip_plan_id = :trip_plan_id";
        $this->pdo->prepare($sql)->execute(['trip_plan_id' => $tripPlanId]);
    }
}
