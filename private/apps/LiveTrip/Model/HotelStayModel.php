<?php

namespace App\LiveTrip\Model;

use Core\BaseModel;

/**
 * 宿泊モデル
 */
class HotelStayModel extends BaseModel {
    protected string $table = 'lt_hotel_stays';
    protected array $fields = [
        'id', 'trip_plan_id', 'hotel_name', 'address',
        'distance_from_home', 'time_from_home', 'distance_from_venue', 'time_from_venue',
        'check_in', 'check_out', 'reservation_no', 'price', 'num_guests', 'memo',
        'latitude', 'longitude', 'place_id', 'created_at', 'updated_at'
    ];
    protected bool $isUserIsolated = false;

    public function getByTripPlanId(int $tripPlanId): array {
        $sql = "SELECT * FROM {$this->table} WHERE trip_plan_id = :tid ORDER BY check_in, id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['tid' => $tripPlanId]);
        return $stmt->fetchAll();
    }

    public function getGoogleMapsUrl(array $stay): string {
        $lat = trim($stay['latitude'] ?? '');
        $lng = trim($stay['longitude'] ?? '');
        if ($lat !== '' && $lng !== '') {
            return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($lat . ',' . $lng);
        }
        $q = $stay['address'] ?: $stay['hotel_name'];
        if (empty($q)) return '#';
        return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($q);
    }
}
