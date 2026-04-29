<?php

namespace App\LiveTrip\Model;

use Core\BaseModel;

/**
 * 遠征の目的地モデル（メイン・コラボ店・観光・その他）
 */
class DestinationModel extends BaseModel {
    protected string $table = 'lt_destinations';
    protected array $fields = [
        'id', 'trip_plan_id', 'name', 'destination_type', 'address',
        'visit_date', 'visit_time', 'memo',
        'latitude', 'longitude', 'place_id', 'sort_order', 'created_at', 'updated_at'
    ];
    protected bool $isUserIsolated = false;

    /** 種別: メイン / コラボ店 / 観光 / その他 */
    public static array $types = [
        'main' => 'メイン',
        'collab' => 'コラボ店',
        'sightseeing' => '観光',
        'other' => 'その他',
    ];

    public function getByTripPlanId(int $tripPlanId): array {
        $sql = "SELECT * FROM {$this->table} WHERE trip_plan_id = :tid ORDER BY sort_order, visit_date, id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['tid' => $tripPlanId]);
        return $stmt->fetchAll();
    }

    public function getGoogleMapsUrl(array $row): string {
        $lat = trim($row['latitude'] ?? '');
        $lng = trim($row['longitude'] ?? '');
        if ($lat !== '' && $lng !== '') {
            return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($lat . ',' . $lng);
        }
        $q = $row['address'] ?? $row['name'] ?? '';
        if (empty($q)) return '#';
        return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($q);
    }
}
