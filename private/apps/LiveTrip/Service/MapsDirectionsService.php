<?php

namespace App\LiveTrip\Service;

use App\LiveTrip\Model\MapsApiUsageModel;

/**
 * Google Directions API 呼び出し
 * 車・電車・徒歩の経路候補を取得。90% 制限を適用。
 */
class MapsDirectionsService {
    private string $apiKey = '';
    private const DIRECTIONS_URL = 'https://maps.googleapis.com/maps/api/directions/json';

    private const MODES = [
        'driving' => '車',
        'transit' => '電車',
        'walking' => '徒歩',
    ];

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
     * 発・着から経路候補（車/電車/徒歩）を取得
     * @param string $origin      出発地（住所・駅名など）
     * @param string $destination 目的地
     * @return array<array{mode: string, label: string, duration: string, distance: string, duration_min: int}>
     */
    public function getRouteOptions(string $origin, string $destination): array {
        $origin = trim($origin);
        $destination = trim($destination);
        if ($origin === '' || $destination === '') return [];
        if (!$this->isConfigured()) return [];

        $options = [];

        try {
            foreach (self::MODES as $mode => $label) {
                $usageModel = new MapsApiUsageModel();
                if (!$usageModel->incrementAndCheck('directions', 1)) {
                    continue;
                }

                $result = $this->fetchDirection($origin, $destination, $mode);
                if ($result !== null) {
                    $options[] = $result;
                }
            }
        } catch (\Throwable $e) {
            \Core\Logger::errorWithContext('Directions failed', $e);
        }

        return $options;
    }

    /**
     * @return array{mode: string, label: string, duration: string, distance: string, duration_min: int}|null
     */
    private function fetchDirection(string $origin, string $destination, string $mode): ?array {
        $url = self::DIRECTIONS_URL . '?origin=' . rawurlencode($origin)
            . '&destination=' . rawurlencode($destination)
            . '&mode=' . rawurlencode($mode)
            . '&language=ja'
            . '&key=' . rawurlencode($this->apiKey);

        $ctx = stream_context_create([
            'http' => ['timeout' => 10],
        ]);
        $json = @file_get_contents($url, false, $ctx);
        if ($json === false) return null;

        $data = json_decode($json, true);
        $status = $data['status'] ?? '';
        if ($status !== 'OK') return null;

        $route = $data['routes'][0] ?? null;
        if (!$route) return null;

        $leg = $route['legs'][0] ?? null;
        if (!$leg) return null;

        $duration = $leg['duration']['text'] ?? '';
        $distance = $leg['distance']['text'] ?? '';
        $durationValue = (int) ($leg['duration']['value'] ?? 0);
        $durationMin = (int) round($durationValue / 60);

        if ($duration === '') return null;

        return [
            'mode' => $mode,
            'label' => self::MODES[$mode],
            'duration' => $duration,
            'distance' => $distance,
            'duration_min' => $durationMin,
        ];
    }
}
