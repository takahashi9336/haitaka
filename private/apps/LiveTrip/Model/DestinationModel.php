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

    /**
     * 一覧用: 各遠征の「メイン目的地日付（visit_date）」を返す
     * - destination_type='main' を最優先、無ければ他種別の最も早い visit_date
     *
     * @param array<int,int> $tripPlanIds
     * @return array<int,string> trip_plan_id => visit_date (YYYY-MM-DD)
     */
    public function getPrimaryVisitDatesByTripPlanIds(array $tripPlanIds): array {
        $tripPlanIds = array_values(array_unique(array_map('intval', $tripPlanIds)));
        if (empty($tripPlanIds)) return [];

        $in = implode(',', array_fill(0, count($tripPlanIds), '?'));
        $sql = "SELECT trip_plan_id, destination_type, visit_date, sort_order, id
                FROM {$this->table}
                WHERE trip_plan_id IN ({$in}) AND visit_date IS NOT NULL
                ORDER BY trip_plan_id ASC,
                         (destination_type = 'main') DESC,
                         visit_date ASC,
                         sort_order ASC,
                         id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($tripPlanIds);
        $rows = $stmt->fetchAll();

        $out = [];
        foreach ($rows as $r) {
            $tid = (int)($r['trip_plan_id'] ?? 0);
            if ($tid <= 0) continue;
            if (isset($out[$tid])) continue;
            $d = trim((string)($r['visit_date'] ?? ''));
            if ($d === '') continue;
            $out[$tid] = $d;
        }
        return $out;
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
