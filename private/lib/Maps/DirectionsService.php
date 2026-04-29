<?php

namespace Core\Maps;

/**
 * Google Directions API 呼び出し
 */
class DirectionsService {
    private string $apiKey = '';
    private UsageLimiterInterface $limiter;
    /** @var array{status?:string,error_message?:string,mode?:string}|null */
    private ?array $lastError = null;
    private const DIRECTIONS_URL = 'https://maps.googleapis.com/maps/api/directions/json';

    private const MODES = [
        'driving' => '車',
        'transit' => '電車',
        'walking' => '徒歩',
        'bicycling' => '自転車',
    ];

    public function __construct(?UsageLimiterInterface $limiter = null) {
        $this->limiter = $limiter ?? new NoopUsageLimiter();
        $this->apiKey = ApiKeyProvider::getGoogleMapsApiKey();
    }

    public function isConfigured(): bool {
        return $this->apiKey !== '';
    }

    /** @return array{status?:string,error_message?:string,mode?:string}|null */
    public function getLastError(): ?array {
        return $this->lastError;
    }

    public function getRouteDuration(string $origin, string $destination, string $mode = 'transit', ?string $departureDate = null): ?int {
        $origin = trim($origin);
        $destination = trim($destination);
        if ($origin === '' || $destination === '') return null;
        if (!$this->isConfigured()) return null;
        if (!isset(self::MODES[$mode])) $mode = 'transit';
        if (!$this->limiter->incrementAndCheck('directions', 1)) return null;

        $departureTime = $this->resolveDepartureTime($departureDate);
        try {
            $result = $this->fetchDirection($origin, $destination, $mode, $departureTime);
            return $result !== null ? $result['duration_min'] : null;
        } catch (\Throwable $e) {
            \Core\Logger::errorWithContext('Directions failed', $e);
            return null;
        }
    }

    /**
     * @return array{polyline: string, bounds: array{ne: array{lat: float, lng: float}, sw: array{lat: float, lng: float}}, duration_min: int, duration: string, distance: string}|null
     */
    public function getOverviewPolyline(string $origin, string $destination, string $mode = 'transit', ?string $departureDate = null): ?array {
        $origin = trim($origin);
        $destination = trim($destination);
        if ($origin === '' || $destination === '') return null;
        if (!$this->isConfigured()) return null;
        if (!isset(self::MODES[$mode])) $mode = 'transit';
        if (!$this->limiter->incrementAndCheck('directions', 1)) return null;

        $departureTime = $this->resolveDepartureTime($departureDate);
        try {
            return $this->fetchRoute($origin, $destination, $mode, $departureTime);
        } catch (\Throwable $e) {
            \Core\Logger::errorWithContext('Directions polyline failed', $e);
            return null;
        }
    }

    private function resolveDepartureTime(?string $departureDate): ?int {
        if ($departureDate === null || $departureDate === '') return null;
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', trim($departureDate));
        if ($date === false) return null;
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
            $params['departure_time'] = (string)$departureTime;
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
        $durationValue = (int)($leg['duration']['value'] ?? 0);
        $durationMin = (int)round($durationValue / 60);
        if ($duration === '') return null;

        return [
            'mode' => $mode,
            'label' => self::MODES[$mode],
            'duration' => (string)$duration,
            'distance' => (string)$distance,
            'duration_min' => $durationMin,
        ];
    }

    /**
     * @param int|null $departureTime Unix 秒。transit のときのみ使用。null のときは now
     * @return array{polyline: string, bounds: array{ne: array{lat: float, lng: float}, sw: array{lat: float, lng: float}}, duration_min: int, duration: string, distance: string}|null
     */
    private function fetchRoute(string $origin, string $destination, string $mode, ?int $departureTime = null): ?array {
        $this->lastError = null;
        $params = [
            'origin' => $origin,
            'destination' => $destination,
            'mode' => $mode,
            'language' => 'ja',
            'region' => 'jp',
            'key' => $this->apiKey,
        ];
        if ($mode === 'transit' && $departureTime !== null) {
            $params['departure_time'] = (string)$departureTime;
        } elseif ($mode === 'transit') {
            $params['departure_time'] = 'now';
        }
        $url = self::DIRECTIONS_URL . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        $ctx = stream_context_create([
            'http' => ['timeout' => 10],
        ]);
        $json = @file_get_contents($url, false, $ctx);
        if ($json === false) {
            $this->lastError = ['status' => 'HTTP_ERROR', 'error_message' => 'file_get_contents failed', 'mode' => $mode];
            return null;
        }

        $data = json_decode($json, true);
        $status = $data['status'] ?? '';
        if ($status !== 'OK') {
            $this->lastError = [
                'status' => (string)$status,
                'error_message' => (string)($data['error_message'] ?? ''),
                'mode' => $mode,
            ];
            return null;
        }

        $route = $data['routes'][0] ?? null;
        if (!$route) {
            $this->lastError = ['status' => 'NO_ROUTE', 'error_message' => 'routes[0] missing', 'mode' => $mode];
            return null;
        }

        $polyline = $route['overview_polyline']['points'] ?? '';
        if ($polyline === '') {
            $this->lastError = ['status' => 'NO_POLYLINE', 'error_message' => 'overview_polyline missing', 'mode' => $mode];
            return null;
        }

        $b = $route['bounds'] ?? null;
        $ne = $b['northeast'] ?? null;
        $sw = $b['southwest'] ?? null;
        if (!$ne || !$sw) {
            $this->lastError = ['status' => 'NO_BOUNDS', 'error_message' => 'bounds missing', 'mode' => $mode];
            return null;
        }

        $leg = $route['legs'][0] ?? null;
        if (!$leg) {
            $this->lastError = ['status' => 'NO_LEG', 'error_message' => 'legs[0] missing', 'mode' => $mode];
            return null;
        }

        $duration = $leg['duration']['text'] ?? '';
        $distance = $leg['distance']['text'] ?? '';
        $durationValue = (int)($leg['duration']['value'] ?? 0);
        $durationMin = (int)round($durationValue / 60);

        return [
            'polyline' => (string)$polyline,
            'bounds' => [
                'ne' => ['lat' => (float)($ne['lat'] ?? 0), 'lng' => (float)($ne['lng'] ?? 0)],
                'sw' => ['lat' => (float)($sw['lat'] ?? 0), 'lng' => (float)($sw['lng'] ?? 0)],
            ],
            'duration_min' => $durationMin,
            'duration' => (string)$duration,
            'distance' => (string)$distance,
        ];
    }
}

