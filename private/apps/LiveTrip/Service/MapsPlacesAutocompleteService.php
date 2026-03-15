<?php

namespace App\LiveTrip\Service;

use App\LiveTrip\Model\MapsApiUsageModel;

/**
 * Google Places API (Legacy) Place Autocomplete 呼び出し
 * 住所・施設名のサジェストを取得。90% 制限を適用。
 */
class MapsPlacesAutocompleteService {
    private string $apiKey = '';
    private const AUTOCOMPLETE_URL = 'https://maps.googleapis.com/maps/api/place/autocomplete/json';

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
     * 入力文字列から候補を取得
     * @return array<array{description: string, place_id: string}>
     */
    public function getSuggestions(string $input): array {
        $input = trim($input);
        if (mb_strlen($input) < 2) return [];
        if (!$this->isConfigured()) return [];

        try {
            $usageModel = new MapsApiUsageModel();
            if (!$usageModel->incrementAndCheck('autocomplete', 1)) {
                return [];
            }

            $params = [
                'input' => $input,
                'language' => 'ja',
                'region' => 'jp',
                'key' => $this->apiKey,
            ];
            $url = self::AUTOCOMPLETE_URL . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

            $ctx = stream_context_create([
                'http' => ['timeout' => 5],
            ]);
            $json = @file_get_contents($url, false, $ctx);
            if ($json === false) return [];

            $data = json_decode($json, true);
            $status = $data['status'] ?? '';
            if ($status !== 'OK' && $status !== 'ZERO_RESULTS') return [];

            $predictions = $data['predictions'] ?? [];
            $result = [];
            foreach ($predictions as $p) {
                $desc = $p['description'] ?? '';
                $placeId = $p['place_id'] ?? '';
                if ($desc !== '' && $placeId !== '') {
                    $result[] = ['description' => $desc, 'place_id' => $placeId];
                }
            }
            return $result;
        } catch (\Throwable $e) {
            \Core\Logger::errorWithContext('Places Autocomplete failed', $e);
            return [];
        }
    }
}
