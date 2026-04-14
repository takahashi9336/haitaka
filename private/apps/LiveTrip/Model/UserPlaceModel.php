<?php

namespace App\LiveTrip\Model;

use Core\BaseModel;
use Core\Encryption;

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
    protected array $encryptedFields = ['address', 'place_id'];
    protected bool $isUserIsolated = false;

    public function getByUserAndKey(int $userId, string $placeKey): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :uid AND place_key = :k LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $userId, 'k' => $placeKey]);
        $row = $stmt->fetch();
        return $row ? $this->decryptFields($row) : null;
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

        // NOTE: This method uses raw SQL (ON DUPLICATE KEY), so BaseModel's automatic encryption won't run.
        // Encrypt here to avoid storing plaintext.
        foreach ($this->encryptedFields as $field) {
            if (array_key_exists($field, $payload) && $payload[$field] !== null && $payload[$field] !== '') {
                $v = (string) $payload[$field];
                $payload[$field] = Encryption::isEncrypted($v) ? $v : Encryption::encrypt($v);
            }
        }

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

    private function decryptFields(array $row): array {
        foreach ($this->encryptedFields as $field) {
            if (array_key_exists($field, $row) && $row[$field] !== null && $row[$field] !== '') {
                $row[$field] = Encryption::decrypt((string) $row[$field]);
            }
        }
        return $row;
    }
}

