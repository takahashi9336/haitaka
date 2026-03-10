<?php

namespace App\LiveTrip\Service;

use App\LiveTrip\Model\MapsApiUsageModel;

/**
 * Google Maps Static API による地図画像 URL 生成
 * 90% 制限を適用。制限超過時は null を返す。
 */
class MapsStaticMapService {
    private string $apiKey = '';
    private const BASE_URL = 'https://maps.googleapis.com/maps/api/staticmap';

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
     * 緯度経度を中心とした地図画像の URL を取得
     * 制限超過・未設定時は null
     * @param string $lat  緯度
     * @param string $lng  経度
     * @param int    $w    幅（px、最大640）
     * @param int    $h    高さ（px、最大640）
     */
    public function getStaticMapUrl(string $lat, string $lng, int $w = 320, int $h = 120): ?string {
        $lat = trim($lat);
        $lng = trim($lng);
        if ($lat === '' || $lng === '') return null;
        if (!$this->isConfigured()) return null;

        try {
            $usageModel = new MapsApiUsageModel();
            if (!$usageModel->incrementAndCheck('static_maps', 1)) {
                return null;
            }

            $w = max(100, min(640, $w));
            $h = max(60, min(640, $h));

            $center = $lat . ',' . $lng;
            $params = [
                'center' => $center,
                'zoom' => 15,
                'size' => $w . 'x' . $h,
                'maptype' => 'roadmap',
                'markers' => 'color:red|' . $center,
                'key' => $this->apiKey,
            ];

            return self::BASE_URL . '?' . http_build_query($params);
        } catch (\Throwable $e) {
            \Core\Logger::errorWithContext('Static Map URL failed', $e);
            return null;
        }
    }
}
