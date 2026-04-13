<?php

namespace App\LiveTrip\Service;

use App\LiveTrip\Model\MapsApiUsageModel;

/**
 * Google Directions API 呼び出し
 * 車・電車・徒歩・自転車の経路候補を取得。90% 制限を適用。
 */
class MapsDirectionsService {
    private string $apiKey = '';
    private const DIRECTIONS_URL = 'https://maps.googleapis.com/maps/api/directions/json';

    private const MODES = [
        'driving' => '車',
        'transit' => '電車',
        'walking' => '徒歩',
        'bicycling' => '自転車',
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
     * 発・着・モードから所要時間（分）を1回だけ取得（ルートリンク反映用）
     * @param string      $origin        出発地
     * @param string      $destination   目的地
     * @param string      $mode          driving|transit|walking|bicycling
     * @param string|null $departureDate 出発日 Y-m-d。transit 用。null のとき now
     * @return int|null 所要時間（分）。取得失敗時は null
     */
    public function getRouteDuration(string $origin, string $destination, string $mode = 'transit', ?string $departureDate = null): ?int {
        $origin = trim($origin);
        $destination = trim($destination);
        if ($origin === '' || $destination === '') return null;
        if (!$this->isConfigured()) return null;
        if (!isset(self::MODES[$mode])) $mode = 'transit';

        $departureTime = $this->resolveDepartureTime($departureDate);

        try {
            $usageModel = new MapsApiUsageModel();
            if (!$usageModel->incrementAndCheck('directions', 1)) {
                return null;
            }
            $result = $this->fetchDirection($origin, $destination, $mode, $departureTime);
            return $result !== null ? $result['duration_min'] : null;
        } catch (\Throwable $e) {
            \Core\Logger::errorWithContext('Directions failed', $e);
            return null;
        }
    }

    /**
     * 発・着・モードから overview_polyline を取得（地図描画用）
     * @param string      $origin        出発地（文字列）
     * @param string      $destination   目的地（文字列）
     * @param string      $mode          driving|transit|walking|bicycling
     * @param string|null $departureDate 出発日 Y-m-d。transit 用。null のとき now
     * @return array{polyline: string, bounds: array{ne: array{lat: float, lng: float}, sw: array{lat: float, lng: float}}, duration_min: int, duration: string, distance: string}|null
     */
    public function getOverviewPolyline(string $origin, string $destination, string $mode = 'transit', ?string $departureDate = null): ?array {
        $origin = trim($origin);
        $destination = trim($destination);
        if ($origin === '' || $destination === '') return null;
        if (!$this->isConfigured()) return null;
        if (!isset(self::MODES[$mode])) $mode = 'transit';

        $departureTime = $this->resolveDepartureTime($departureDate);

        try {
            $usageModel = new MapsApiUsageModel();
            if (!$usageModel->incrementAndCheck('directions', 1)) {
                return null;
            }
            $route = $this->fetchRoute($origin, $destination, $mode, $departureTime);
            return $route;
        } catch (\Throwable $e) {
            \Core\Logger::errorWithContext('Directions polyline failed', $e);
            return null;
        }
    }

    /**
     * 出発日から Directions API 用の departure_time を決定（transit 用）
     * @param string|null $departureDate Y-m-d または null
     * @return int|null Unix 秒。null のときは「now」として扱う
     */
    private function resolveDepartureTime(?string $departureDate): ?int {
        if ($departureDate === null || $departureDate === '') {
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', trim($departureDate));
        if ($date === false) {
            return null;
        }
        // その日の 9:00 JST の Unix 秒（transit の departure_time 用）
        $nineJst = new \DateTimeImmutable($date->format('Y-m-d') . ' 09:00:00', new \DateTimeZone('Asia/Tokyo'));
        return $nineJst->getTimestamp();
    }

    /**
     * @param int|null $departureTime Unix 秒。transit のときのみ使用。null のときは now
     * @return array{mode: string, label: string, duration: string, distance: string, duration_min: int}|null
     */
    private function fetchDirection(string $origin, string $destination, string $mode, ?int $departureTime = null): ?array {
        $params = [
            'origin' => $origin,
            'destination' => $destination,
            'mode' => $mode,
            'language' => 'ja',
            'region' => 'jp',
            'key' => $this->apiKey,
        ];
        if ($mode === 'transit' && $departureTime !== null) {
            $params['departure_time'] = (string) $departureTime;
        } elseif ($mode === 'transit') {
            $params['departure_time'] = 'now';
        }
        $url = self::DIRECTIONS_URL . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

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

    /**
     * @param int|null $departureTime Unix 秒。transit のときのみ使用。null のときは now
     * @return array{polyline: string, bounds: array{ne: array{lat: float, lng: float}, sw: array{lat: float, lng: float}}, duration_min: int, duration: string, distance: string}|null
     */
    private function fetchRoute(string $origin, string $destination, string $mode, ?int $departureTime = null): ?array {
        $params = [
            'origin' => $origin,
            'destination' => $destination,
            'mode' => $mode,
            'language' => 'ja',
            'region' => 'jp',
            'key' => $this->apiKey,
        ];
        if ($mode === 'transit' && $departureTime !== null) {
            $params['departure_time'] = (string) $departureTime;
        } elseif ($mode === 'transit') {
            $params['departure_time'] = 'now';
        }
        $url = self::DIRECTIONS_URL . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

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

        $polyline = $route['overview_polyline']['points'] ?? '';
        if ($polyline === '') return null;

        $b = $route['bounds'] ?? null;
        $ne = $b['northeast'] ?? null;
        $sw = $b['southwest'] ?? null;
        if (!$ne || !$sw) return null;

        $leg = $route['legs'][0] ?? null;
        if (!$leg) return null;

        $duration = $leg['duration']['text'] ?? '';
        $distance = $leg['distance']['text'] ?? '';
        $durationValue = (int) ($leg['duration']['value'] ?? 0);
        $durationMin = (int) round($durationValue / 60);

        return [
            'polyline' => (string) $polyline,
            'bounds' => [
                'ne' => ['lat' => (float)($ne['lat'] ?? 0), 'lng' => (float)($ne['lng'] ?? 0)],
                'sw' => ['lat' => (float)($sw['lat'] ?? 0), 'lng' => (float)($sw['lng'] ?? 0)],
            ],
            'duration_min' => $durationMin,
            'duration' => (string) $duration,
            'distance' => (string) $distance,
        ];
    }
}
