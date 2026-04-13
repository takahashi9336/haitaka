<?php

namespace App\LiveTrip\Model;

use Core\BaseModel;

/**
 * ユーザー別の場所（自宅など）
 */
class UserPlaceModel extends BaseModel {
    protected string $table = 'lt_user_places';
    protected array $fields = [
        'id', 'user_id', 'place_key',
        'label', 'address', 'place_id', 'latitude', 'longitude',
        'created_at', 'updated_at'
    ];
    protected bool $isUserIsolated = false;

    public function getByUserAndKey(int $userId, string $placeKey): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :uid AND place_key = :k LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $userId, 'k' => $placeKey]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function upsertHome(int $userId, array $data): void {
        $payload = [
            'user_id' => $userId,
            'place_key' => 'home',
            'label' => $data['label'] ?? '自宅',
            'address' => $data['address'] ?? null,
            'place_id' => $data['place_id'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
        ];

        $sql = "INSERT INTO {$this->table} (user_id, place_key, label, address, place_id, latitude, longitude)
                VALUES (:user_id, :place_key, :label, :address, :place_id, :latitude, :longitude)
                ON DUPLICATE KEY UPDATE
                    label = VALUES(label),
                    address = VALUES(address),
                    place_id = VALUES(place_id),
                    latitude = VALUES(latitude),
                    longitude = VALUES(longitude),
                    updated_at = CURRENT_TIMESTAMP";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($payload);
    }
}

