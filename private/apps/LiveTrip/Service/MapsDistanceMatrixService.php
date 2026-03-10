<?php

namespace App\LiveTrip\Service;

use App\LiveTrip\Model\MapsApiUsageModel;

/**
 * Google Distance Matrix API 呼び出し
 * 2点間の距離・所要時間を取得。90% 制限を適用。
 */
class MapsDistanceMatrixService {
    private string $apiKey = '';
    private const DM_URL = 'https://maps.googleapis.com/maps/api/distancematrix/json';

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
     * 2点間の車での距離・所要時間を取得
     * @param string $originLat  出発地の緯度
     * @param string $originLng  出発地の経度
     * @param string $destLat    目的地の緯度
     * @param string $destLng    目的地の経度
     * @return array{distance: string, duration: string}|null 距離（例: 2.3 km）、所要時間（例: 15 分）
     */
    public function getDistanceAndDuration(string $originLat, string $originLng, string $destLat, string $destLng): ?array {
        if (!$this->isConfigured()) return null;

        $origin = $originLat . ',' . $originLng;
        $dest = $destLat . ',' . $destLng;

        try {
            $usageModel = new MapsApiUsageModel();
            // Distance Matrix は 1 origin x 1 destination = 1 要素
            if (!$usageModel->incrementAndCheck('distance_matrix', 1)) {
                return null;
            }

            $url = self::DM_URL . '?origins=' . rawurlencode($origin)
                . '&destinations=' . rawurlencode($dest)
                . '&mode=driving'
                . '&language=ja'
                . '&key=' . rawurlencode($this->apiKey);

            $ctx = stream_context_create([
                'http' => ['timeout' => 8],
            ]);
            $json = @file_get_contents($url, false, $ctx);
            if ($json === false) return null;

            $data = json_decode($json, true);
            $status = $data['status'] ?? '';
            if ($status !== 'OK') return null;

            $element = $data['rows'][0]['elements'][0] ?? null;
            if (!$element) return null;

            $status = $element['status'] ?? '';
            if ($status !== 'OK') return null;

            $distance = $element['distance']['text'] ?? null;
            $duration = $element['duration']['text'] ?? null;
            if (!$distance || !$duration) return null;

            return [
                'distance' => $distance,
                'duration' => $duration,
            ];
        } catch (\Throwable $e) {
            \Core\Logger::errorWithContext('Distance Matrix failed', $e);
            return null;
        }
    }
}
