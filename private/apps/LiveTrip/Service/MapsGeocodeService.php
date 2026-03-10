<?php

namespace App\LiveTrip\Service;

use App\LiveTrip\Model\MapsApiUsageModel;

/**
 * Google Geocoding API 呼び出し
 * 住所・施設名から緯度経度・place_id を取得。90% 制限を適用。
 */
class MapsGeocodeService {
    private string $apiKey = '';
    private const GEOCODE_URL = 'https://maps.googleapis.com/maps/api/geocode/json';

    public function __construct() {
        $this->apiKey = $_ENV['GOOGLE_MAPS_API_KEY'] ?? '';
        if (empty($this->apiKey)) {
            $this->loadApiKeyFromEnv();
        }
    }

    private function loadApiKeyFromEnv(): void {
        $envPath = dirname(__DIR__, 3) . '/.env';
        if (is_file($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '#')) continue;
                if (str_contains($line, '=')) {
                    [$key, $val] = explode('=', $line, 2);
                    if (trim($key) === 'GOOGLE_MAPS_API_KEY') {
                        $this->apiKey = trim($val);
                        break;
                    }
                }
            }
        }
    }

    public function isConfigured(): bool {
        return !empty($this->apiKey);
    }

    /**
     * 住所・施設名から緯度経度・place_id を取得
     * 制限超過時・API 未設定・エラー時は null を返す
     * @return array{latitude: string, longitude: string, place_id: string|null, formatted_address: string|null}|null
     */
    public function geocode(string $address): ?array {
        $address = trim($address);
        if ($address === '') return null;
        if (!$this->isConfigured()) return null;

        try {
            $usageModel = new MapsApiUsageModel();
            if (!$usageModel->incrementAndCheck('geocoding', 1)) {
                return null;
            }

            $url = self::GEOCODE_URL . '?address=' . rawurlencode($address)
                . '&key=' . rawurlencode($this->apiKey)
                . '&language=ja';

            $ctx = stream_context_create([
                'http' => ['timeout' => 5],
            ]);
            $json = @file_get_contents($url, false, $ctx);
            if ($json === false) return null;

            $data = json_decode($json, true);
            if (!isset($data['results'][0])) return null;

            $r = $data['results'][0];
            $loc = $r['geometry']['location'] ?? null;
            if (!$loc || !isset($loc['lat'], $loc['lng'])) return null;

            return [
                'latitude' => (string) $loc['lat'],
                'longitude' => (string) $loc['lng'],
                'place_id' => $r['place_id'] ?? null,
                'formatted_address' => $r['formatted_address'] ?? null,
            ];
        } catch (\Throwable $e) {
            \Core\Logger::errorWithContext('Geocoding failed', $e);
            return null;
        }
    }
}
